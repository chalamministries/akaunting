<?php

namespace Modules\FluidPay\Listeners\Document;

use App\Events\Document\DocumentRecurring as Event;
use App\Events\Document\PaymentReceived;
use App\Models\Setting\EmailTemplate;
use App\Traits\Modules;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\FluidPay\Models\FluidPayVault;
use Modules\FluidPay\Notifications\RecurringPaymentFailed;
use Modules\FluidPay\Support\Config;

class ProcessRecurringPayment
{
    use Modules;

    public function handle(Event $event): void
    {
        if (! $this->moduleIsEnabled('fluidpay')) {
            return;
        }

        $document = $event->document;

        if ($document->type !== 'invoice') {
            return;
        }

        if ($document->amount_due <= 0) {
            return;
        }

        $vault = FluidPayVault::defaultForCustomer($document->company_id, $document->contact_id)->first();

        if (! $vault) {
            return;
        }

        $vaultDetails = $this->fetchVaultDefaults($vault->fluidpay_customer_id);
        $paymentMethodId = $vaultDetails['payment_method_id'] ?? $vault->payment_method_id;
        $paymentMethodType = $vaultDetails['payment_method_type'] ?? $vault->payment_method_type;

        if (! $paymentMethodId || ! $paymentMethodType) {
            return;
        }

        if ($vaultDetails && $paymentMethodId !== $vault->payment_method_id) {
            $this->syncDefaultVault($document->company_id, $document->contact_id, $vaultDetails);
        }

        $precision = currency($document->currency_code)->getPrecision();
        $multiplier = 10 ** $precision;
        $formattedAmount = (int) round($document->amount_due * $multiplier);

        $requestBody = [
            'type' => 'sale',
            'amount' => $formattedAmount,
            'currency' => $document->currency_code,
            'payment_method' => [
                'customer' => [
                    'id' => $vault->fluidpay_customer_id,
                    'payment_method_id' => $paymentMethodId,
                    'payment_method_type' => $paymentMethodType,
                ],
            ],
            'order' => [
                'invoice_number' => $document->document_number,
                'description' => __('Recurring invoice :number payment', ['number' => $document->document_number]),
            ],
        ];

        try {
            $endpoint = Config::baseUrl() . '/api/transaction';
            $response = $this->httpClient()->post($endpoint, $requestBody);
        } catch (\Throwable $exception) {
            Log::channel('daily')->error('FluidPay recurring charge request error', [
                'invoice_id' => $document->id,
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        $body = $response->json();

        if (! $response->successful()) {
            Log::channel('daily')->warning('FluidPay recurring charge declined', [
                'invoice_id' => $document->id,
                'status' => $response->status(),
                'body' => $body,
            ]);

            $this->notifyFailedRecurringPayment($document);

            return;
        }

        $responseStatus = data_get($body, 'data.response');

        if ($responseStatus !== 'approved') {
            Log::channel('daily')->info('FluidPay recurring charge not approved', [
                'invoice_id' => $document->id,
                'status' => $responseStatus,
                'body' => $body,
            ]);

            $this->notifyFailedRecurringPayment($document);

            return;
        }

        $transactionId = data_get($body, 'data.id');

        $paymentRequest = [
            'type' => 'income',
            'amount' => $document->amount_due,
            'currency_code' => $document->currency_code,
            'payment_method' => 'fluidpay.tokenizer',
            'reference' => $transactionId ?? $paymentMethodId,
            'description' => __('FluidPay recurring payment for invoice :number', ['number' => $document->document_number]),
            'account_id' => setting('fluidpay.account_id', setting('default.account')),
        ];

        event(new PaymentReceived($document, $paymentRequest));
    }

    protected function fetchVaultDefaults(string $customerId): ?array
    {
        try {
            $endpoint = Config::baseUrl() . "/api/vault/{$customerId}";
            $response = $this->httpClient()->get($endpoint);
        } catch (\Throwable $exception) {
            Log::channel('daily')->warning('FluidPay vault lookup failed', [
                'customer_id' => $customerId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::channel('daily')->warning('FluidPay vault lookup declined', [
                'customer_id' => $customerId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        }

        $payload = $response->json();
        $defaults = data_get($payload, 'data.data.customer.defaults', []);

        if (empty($defaults['payment_method_id'])) {
            return null;
        }

        $payments = data_get($payload, 'data.data.customer.payments', []);
        $paymentDetails = [];

        if (($defaults['payment_method_type'] ?? null) === 'card') {
            $paymentDetails = collect($payments['cards'] ?? [])->firstWhere('id', $defaults['payment_method_id']) ?? [];
        } elseif (($defaults['payment_method_type'] ?? null) === 'ach') {
            $paymentDetails = collect($payments['ach'] ?? [])->firstWhere('id', $defaults['payment_method_id']) ?? [];
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
            'payment_method_id' => $defaults['payment_method_id'],
            'payment_method_type' => $defaults['payment_method_type'] ?? '',
            'card_brand' => data_get($paymentDetails, 'card_type'),
            'masked_number' => data_get($paymentDetails, 'masked_number') ?? data_get($paymentDetails, 'masked_account'),
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
        ];
    }

    protected function syncDefaultVault(int $companyId, int $customerId, array $vaultData): void
    {
        if (! isset($vaultData['payment_method_id'])) {
            return;
        }

        FluidPayVault::forCustomer($companyId, $customerId)->update(['is_default' => false]);

        $existing = FluidPayVault::forCustomer($companyId, $customerId)
            ->where('payment_method_id', $vaultData['payment_method_id'])
            ->first();

        if ($existing) {
            $existing->fill($vaultData);
            $existing->is_default = true;
            $existing->save();

            return;
        }

        FluidPayVault::create(array_merge($vaultData, [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'is_default' => true,
        ]));
    }

    protected function notifyFailedRecurringPayment($document): void
    {
        if (! $document->contact || empty($document->contact_email)) {
            return;
        }

        $alias = 'fluidpay_recurring_failed_customer';

        if (! EmailTemplate::where('company_id', $document->company_id)->alias($alias)->exists()) {
            return;
        }

        try {
            $document->contact->notify(new RecurringPaymentFailed($document), true);
        } catch (\Throwable $exception) {
            Log::channel('daily')->warning('FluidPay recurring failure notification error', [
                'invoice_id' => $document->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function getPrivateKey(): ?string
    {
        $namespaced = setting('fluidpay.private_key');

        if ($namespaced !== null && $namespaced !== '') {
            return $namespaced;
        }

        return setting('fluidpay_private_key');
    }

    protected function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $privateKey = trim((string) $this->getPrivateKey());

        $http = Http::timeout(180)->withHeaders([
            'Accept' => 'application/json',
        ]);

        if ($privateKey === '') {
            return $http;
        }

        if (str_starts_with($privateKey, 'api_')) {
            return $http->withHeaders([
                'Authorization' => $privateKey,
            ]);
        }

        return $http->withBasicAuth($privateKey, '');
    }
}
