<?php

namespace Modules\FluidPay\Controllers\Portal;

use App\Abstracts\Http\Controller;
use App\Http\Requests\Portal\InvoiceShow as InvoiceShowRequest;
use App\Events\Document\PaymentReceived;
use App\Models\Document\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

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
            'url' => 'https://sandbox.fluidpay.com',
            'settings' => [
                'payment' => [
                    'types' => ['card', 'ach'],
                    'card' => [
                        'requireCVV' => true,
                        'strict_mode' => false,
                        'mask_number' => false,
                    ],
                    'ach' => [
                        'sec_code' => 'web',
                        'showSecCode' => false,
                        'verifyAccountRouting' => true,
                    ],
                ],
            ],
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
        ]);

        $privateKey = $this->getPrivateKey();

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
        $formattedAmount = number_format($amount, $precision, '.', '');

        $requestBody = [
            'type' => 'sale',
            'amount' => $formattedAmount,
            'currency' => $invoice->currency_code,
            'payment_method' => [
                'token' => $payload['token'],
            ],
            'order' => [
                'invoice_number' => $invoice->document_number,
                'description' => __('Invoice :number payment', ['number' => $invoice->document_number]),
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($privateKey . ':'),
                'Accept' => 'application/json',
            ])->post('https://sandbox.fluidpay.com/api/transaction/sale', $requestBody);
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

        $transactionStatus = data_get($body, 'status');
        $transactionId = data_get($body, 'id') ?? data_get($body, 'transaction_id');

        if ($transactionStatus !== 'approved') {
            Log::channel('daily')->info('FluidPay sale not approved', [
                'invoice_id' => $invoice->id,
                'status' => $transactionStatus,
                'body' => $body,
            ]);

            return response()->json([
                'success' => false,
                'error' => data_get($body, 'message', __('FluidPay did not approve the transaction.')),
                'details' => $body,
            ], 422);
        }

        $paymentRequest = [
            'amount' => $amount,
            'currency_code' => $invoice->currency_code,
            'payment_method' => 'fluidpay',
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
            return URL::signedRoute('signed.fluidpay.invoices.pay', [
                'company_id' => $companyId,
                'invoice' => $invoice->id,
            ]);
        }

        return route('portal.fluidpay.invoices.pay', [
            'company_id' => $companyId,
            'invoice' => $invoice->id,
        ]);
    }
}
