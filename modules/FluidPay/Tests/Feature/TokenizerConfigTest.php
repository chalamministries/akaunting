<?php

namespace Modules\FluidPay\Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\Feature\PaymentTestCase;

class TokenizerConfigTest extends PaymentTestCase
{
    public $alias = 'fluidPay';

    public $setting_request = [
        'name' => 'FluidPay',
        'public_key' => 'pub_test_123',
        'private_key' => 'api_test_123',
        'environment' => 'sandbox',
    ];

    public function testItReturnsTokenizerConfigForPortal(): void
    {
        $this->updateSetting();
        $this->loginAsCustomer();
        $this->createInvoice();

        $response = $this->loginAs($this->customer_user)
            ->get(route('portal.fluidPay.invoices.show', $this->invoice->id))
            ->assertOk()
            ->assertJsonStructure([
                'html',
                'meta' => [
                    'fluidpay' => [
                        'publicKey',
                        'invoiceId',
                        'amount',
                        'currency',
                        'tokenEndpoint',
                        'settings',
                    ],
                ],
            ]);

        $config = $response->json('meta.fluidpay');

        $this->assertSame('pub_test_123', $config['publicKey']);
        $this->assertSame((string) $this->invoice->amount_due, $config['amount']);
        $this->assertSame($this->invoice->currency_code, $config['currency']);
        $this->assertTrue((bool) data_get($config, 'settings.payment.calculateFees'));
        $this->assertStringContainsString(
            '/portal/fluidPay/invoices/' . $this->invoice->id . '/confirm',
            $config['tokenEndpoint']
        );
    }

    public function testItReturnsTokenizerConfigForSigned(): void
    {
        $this->updateSetting();
        $this->createInvoice();

        $response = $this->get(URL::signedRoute('signed.fluidPay.invoices.show', [$this->invoice->id]))
            ->assertOk();

        $config = $response->json('meta.fluidpay');

        $this->assertSame('pub_test_123', $config['publicKey']);
        $this->assertStringContainsString(
            '/signed/fluidPay/invoices/' . $this->invoice->id . '/confirm',
            $config['tokenEndpoint']
        );
        $this->assertStringContainsString('signature=', $config['tokenEndpoint']);
    }

    public function testItRendersTokenizerHtmlPayload(): void
    {
        $this->updateSetting();
        $this->loginAsCustomer();
        $this->createInvoice();

        $response = $this->loginAs($this->customer_user)
            ->get(route('portal.fluidPay.invoices.show', $this->invoice->id))
            ->assertOk();

        $html = $response->json('html');
        $containerId = 'fluidpay-tokenizer-' . $this->invoice->id;

        $this->assertStringContainsString('id="' . $containerId . '"', $html);
        $this->assertStringContainsString('data-fluidpay-config', $html);
        $this->assertStringContainsString('data-fluidpay-submit="' . $containerId . '"', $html);
        $this->assertStringContainsString('data-fluidpay-save-for="' . $containerId . '"', $html);
        $this->assertStringContainsString('data-fluidpay-disclosure-for="' . $containerId . '"', $html);
    }
}
