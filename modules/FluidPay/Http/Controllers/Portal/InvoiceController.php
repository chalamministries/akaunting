<?php

namespace Modules\FluidPay\Http\Controllers\Portal;

use App\Abstracts\Http\Controller;
use App\Http\Requests\Portal\InvoiceShow as InvoiceShowRequest;
use App\Events\Document\PaymentReceived;
use App\Models\Document\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Modules\FluidPay\Models\FluidPayVault;
use Modules\FluidPay\Support\Config;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function __construct()
    {
        // Skip parent constructor to avoid permission middleware for customer-facing routes.
    }

    public function show(InvoiceShowRequest $request, Document $invoice): JsonResponse
    {
        $publicKey = $this->getPublicKey();

        if (empty($publicKey)) {
            return response()->json([
                'html' => view('fluidpay::portal.message', [
                    'title' => __('FluidPay unavailable'),
                    'body' => __('The FluidPay payment method has not been configured yet. Please contact the business to enable online payments.'),
                ])->render(),
            ]);
        }

        if ($invoice->amount_due <= 0) {
            return response()->json([
                'html' => view('fluidpay::portal.message', [
                    'title' => __('Invoice already settled'),
                    'body' => __('This invoice does not have an outstanding balance, so no payment is required.'),
                ])->render(),
            ]);
        }

        $containerId = 'fluidpay-tokenizer-' . $invoice->id;
        $formattedAmount = (string) \money($invoice->amount_due, $invoice->currency_code);

        $tokenEndpoint = $this->resolveTokenEndpoint($invoice);
        $baseUrl = Config::baseUrl();

        $documentType = $invoice->type === 'retainer'
            ? Config::DOCUMENT_RETAINERS
            : Config::DOCUMENT_INVOICES;

        $settings = Config::get($documentType);
        $settings['payment']['types'] = $this->normalisePaymentTypes($settings['payment']['types'] ?? []);

        $config = [
            'containerId' => $containerId,
            'publicKey' => $publicKey,
            'invoiceId' => $invoice->id,
            'invoiceNumber' => $invoice->document_number,
            'amount' => (string) $invoice->amount_due,
            'currency' => $invoice->currency_code,
            'tokenEndpoint' => $tokenEndpoint,
            'csrfToken' => csrf_token(),
            'submitAmount' => (string) $invoice->amount_due,
            'submitLabel' => __('Pay :amount now', ['amount' => $formattedAmount]),
            'url' => $baseUrl,
            'settings' => $settings,
        ];

        $html = view('fluidpay::portal.tokenizer', [
            'invoice' => $invoice,
            'container_id' => $containerId,
            'config' => $config,
        ])->render();

        $meta = [
            'fluidpay' => $config,
        ];

        return response()->json([
            'html' => $html,
            'meta' => $meta,
        ]);
    }

    public function pay(InvoiceShowRequest $request, Document $invoice): JsonResponse
    {
        $payload = $request->validate([
            'token' => ['required', 'string'],
            'invoice_number' => ['nullable', 'string'],
            'amount' => ['nullable'],
            'save_payment_method' => ['nullable', 'boolean'],
        ]);

        $privateKey = trim((string) $this->getPrivateKey());

        if (empty($privateKey)) {
            return response()->json([
                'success' => false,
                'error' => __('FluidPay private API key is not configured. Please contact the business.'),
            ], 422);
        }

        if ($invoice->amount_due <= 0) {
            return response()->json([
                'success' => false,
                'error' => __('This invoice does not have an outstanding balance.'),
            ], 422);
        }

        $amount = $invoice->amount_due;
        $precision = currency($invoice->currency_code)->getPrecision();
        $multiplier = 10 ** $precision;
        $formattedAmount = (int) round($amount * $multiplier);

        $paymentMethod = [
            'token' => $payload['token'],
        ];

        $savePaymentMethod = (bool) ($payload['save_payment_method'] ?? false);

        if ($savePaymentMethod) {
            $vaultData = $this->createVaultCustomer($invoice, $payload['token']);

            if (! empty($vaultData)) {
                $this->storeVaultRecord($invoice, $vaultData);

                $paymentMethod = [
                    'customer' => [
                        'id' => $vaultData['fluidpay_customer_id'],
                        'payment_method_id' => $vaultData['payment_method_id'],
                        'payment_method_type' => $vaultData['payment_method_type'],
                    ],
                ];
            }
        }

        $requestBody = [
            'type' => 'sale',
            'amount' => $formattedAmount,
            'currency' => $invoice->currency_code,
            'payment_method' => $paymentMethod,
            'order' => [
                'invoice_number' => $invoice->document_number,
                'description' => __('Invoice :number payment', ['number' => $invoice->document_number]),
            ],
        ];

        try {
            $endpoint = Config::baseUrl() . '/api/transaction';

            if (config('app.debug')) {
                Log::channel('daily')->info('FluidPay auth debug', [
                    'endpoint' => $endpoint,
                    'key_prefix' => substr($privateKey, 0, 6),
                    'key_length' => strlen($privateKey),
                ]);
            }

            $http = Http::withHeaders([
                'Accept' => 'application/json',
            ]);

            if (str_starts_with($privateKey, 'api_')) {
                $http = $http->withHeaders([
                    'Authorization' => $privateKey,
                ]);
            } else {
                $http = $http->withBasicAuth($privateKey, '');
            }

            $response = $http->post($endpoint, $requestBody);
        } catch (\Throwable $exception) {
            Log::channel('daily')->error('FluidPay request error', [
                'invoice_id' => $invoice->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => __('We were unable to reach FluidPay. Please try again later.'),
            ], 502);
        }

        if (! $response->successful()) {
            $body = $response->json();

            Log::channel('daily')->warning('FluidPay sale declined', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return response()->json([
                'success' => false,
                'error' => data_get($body, 'message', __('FluidPay declined the transaction.')),
                'details' => $body,
            ], 422);
        }

        $body = $response->json();

        $transactionStatus = data_get($body, 'data.status')
            ?? data_get($body, 'status');
        $transactionResponse = data_get($body, 'data.response')
            ?? data_get($body, 'response');
        $transactionId = data_get($body, 'data.id')
            ?? data_get($body, 'id')
            ?? data_get($body, 'transaction_id');

        $isApproved = $transactionResponse === 'approved'
            || $transactionStatus === 'approved'
            || $transactionStatus === 'pending_settlement'
            || $transactionStatus === 'success';

        if (! $isApproved) {
            Log::channel('daily')->info('FluidPay sale not approved', [
                'invoice_id' => $invoice->id,
                'status' => $transactionStatus,
                'response' => $transactionResponse,
                'body' => $body,
            ]);

            return response()->json([
                'success' => false,
                'error' => data_get($body, 'message', __('FluidPay did not approve the transaction.')),
                'details' => $body,
            ], 422);
        }

        $paymentRequest = [
            'type' => 'income',
            'amount' => $amount,
            'currency_code' => $invoice->currency_code,
            'payment_method' => 'fluidpay.tokenizer',
            'reference' => $transactionId ?? $payload['token'],
            'description' => __('FluidPay payment for invoice :number', ['number' => $invoice->document_number]),
            'account_id' => setting('fluidpay.account_id', setting('default.account')),
        ];

        event(new PaymentReceived($invoice, $paymentRequest));

        return response()->json([
            'success' => true,
            'transaction_id' => $transactionId,
            'message' => __('Payment applied successfully.'),
            'redirect' => request()->isPortal($invoice->company_id)
                ? route('portal.invoices.show', $invoice->id)
                : URL::signedRoute('signed.invoices.show', [$invoice->id]),
        ]);
    }

    protected function normalisePaymentTypes(array $types): array
    {
        if ($types === []) {
            return ['card', 'ach'];
        }

        if (array_is_list($types)) {
            return $types;
        }

        return array_keys(array_filter($types));
    }

    protected function createVaultCustomer(Document $invoice, string $token): ?array
    {
        $contact = $invoice->contact;
        $contactName = $contact?->name ?: $invoice->contact_name ?: $invoice->document_number;
        $nameParts = preg_split('/\s+/', trim((string) $contactName)) ?: [];
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

        $requestBody = [
            'description' => $contactName,
            'default_payment' => [
                'token' => $token,
            ],
            'default_billing_address' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company' => $contact?->name ?: '',
                'email' => $contact?->email ?: '',
                'phone' => $contact?->phone ?: '',
            ],
        ];

        try {
            $privateKey = trim((string) $this->getPrivateKey());

            $endpoint = Config::baseUrl() . '/api/vault/customer';

            if (config('app.debug')) {
                Log::channel('daily')->info('FluidPay vault auth debug', [
                    'endpoint' => $endpoint,
                    'key_prefix' => substr($privateKey, 0, 6),
                    'key_length' => strlen($privateKey),
                ]);
            }

            $http = Http::withHeaders([
                'Accept' => 'application/json',
            ]);

            if (str_starts_with($privateKey, 'api_')) {
                $http = $http->withHeaders([
                    'Authorization' => $privateKey,
                ]);
            } else {
                $http = $http->withBasicAuth($privateKey, '');
            }

            $response = $http->post($endpoint, $requestBody);
        } catch (\Throwable $exception) {
            Log::channel('daily')->warning('FluidPay vault creation failed', [
                'invoice_id' => $invoice->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::channel('daily')->warning('FluidPay vault creation declined', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        return $this->extractVaultDetails($response->json());
    }

    protected function extractVaultDetails(array $payload): ?array
    {
        $customerId = data_get($payload, 'data.id');
        $defaults = data_get($payload, 'data.data.customer.defaults', []);
        $paymentMethodId = $defaults['payment_method_id'] ?? null;
        $paymentMethodType = $defaults['payment_method_type'] ?? null;

        if (! $customerId || ! $paymentMethodId || ! $paymentMethodType) {
            return null;
        }

        $payments = data_get($payload, 'data.data.customer.payments', []);
        $paymentDetails = [];

        if ($paymentMethodType === 'card') {
            $cards = $payments['cards'] ?? [];
            $paymentDetails = collect($cards)->firstWhere('id', $paymentMethodId) ?? [];
        } elseif ($paymentMethodType === 'ach') {
            $achs = $payments['ach'] ?? [];
            $paymentDetails = collect($achs)->firstWhere('id', $paymentMethodId) ?? [];
        }

        $expMonth = null;
        $expYear = null;
        $expiration = data_get($paymentDetails, 'expiration_date');

        if (! empty($expiration)) {
            try {
                $date = Carbon::parse($expiration);
                $expMonth = $date->format('m');
                $expYear = $date->format('Y');
            } catch (\Throwable $exception) {
                $expMonth = null;
                $expYear = null;
            }
        }

        return [
            'fluidpay_customer_id' => $customerId,
            'payment_method_id' => $paymentMethodId,
            'payment_method_type' => $paymentMethodType,
            'card_brand' => data_get($paymentDetails, 'card_type'),
            'masked_number' => data_get($paymentDetails, 'masked_number') ?? data_get($paymentDetails, 'masked_account'),
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
        ];
    }

    protected function storeVaultRecord(Document $invoice, array $vaultData): ?FluidPayVault
    {
        if (! isset($vaultData['payment_method_id'])) {
            return null;
        }

        $companyId = $invoice->company_id;
        $customerId = $invoice->contact_id;

        $existing = FluidPayVault::forCustomer($companyId, $customerId)
            ->where('payment_method_id', $vaultData['payment_method_id'])
            ->first();

        if ($existing) {
            $existing->fill($vaultData);
            $existing->save();

            return $existing;
        }

        $hasDefault = FluidPayVault::defaultForCustomer($companyId, $customerId)->exists();

        return FluidPayVault::create(array_merge($vaultData, [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'is_default' => ! $hasDefault,
        ]));
    }

    protected function getPublicKey(): ?string
    {
        $namespaced = setting('fluidpay.public_key');

        if ($namespaced !== null && $namespaced !== '') {
            return $namespaced;
        }

        return setting('fluidpay_public_key');
    }

    protected function getPrivateKey(): ?string
    {
        $namespaced = setting('fluidpay.private_key');

        if ($namespaced !== null && $namespaced !== '') {
            return $namespaced;
        }

        return setting('fluidpay_private_key');
    }

    protected function resolveTokenEndpoint(Document $invoice): string
    {
        $route = request()->route();

        $companyId = $invoice->company_id ?? company_id();

        if ($route && str_starts_with($route->getName(), 'signed.')) {
            return URL::signedRoute('signed.fluidPay.invoices.confirm', [
                'company_id' => $companyId,
                'invoice' => $invoice->id,
            ]);
        }

        return route('portal.fluidPay.invoices.confirm', [
            'company_id' => $companyId,
            'invoice' => $invoice->id,
        ]);
    }
}
