<?php

namespace Modules\FluidPay\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\PaymentTestCase;

class PaymentsTest extends PaymentTestCase
{
    public $alias = 'fluidPay';

    public $payment_request = [
        'token' => 'tok_test_123',
        'invoice_number' => 'INV-TEST-001',
        'amount' => '100',
    ];

    public $setting_request = [
        'name' => 'FluidPay',
        'public_key' => 'pub_test_123',
        'private_key' => 'api_test_123',
        'environment' => 'sandbox',
    ];

    public function testItShouldPayFromSigned(): void
    {
        $this->mockFluidPay();

        $this->assertPaymentFromSigned();
    }

    public function testItShouldPayFromPortal(): void
    {
        $this->mockFluidPay();

        $this->assertPaymentFromPortal();
    }

    protected function mockFluidPay(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'status' => 'approved',
                    'response' => 'approved',
                    'id' => 'txn_test_123',
                ],
            ], 200),
        ]);
    }
}
