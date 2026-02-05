<?php

namespace Modules\Stripe\Traits;

use App\Traits\Omnipay;
use Modules\Stripe\Models\RegisteredTransaction;

trait StripePayments
{
    use Omnipay;

    public function gatewayChoice($bool = false)
    {
        $this->create($bool ? '\Omnipay\Stripe\PaymentIntentsGateway' : 'Stripe');
        $this->gateway->setApiKey($this->setting['secret_key']);
    }

    public function createToken($request)
    {
        return $this->gateway->createToken([
            'card' => [
                'name' => $request->get('cardName'),
                'number' => $request->get('cardNumber'),
                'expiryMonth' => $request->get('cardMonth'),
                'expiryYear' => $request->get('cardYear'),
                'cvv' => $request->get('cardCvv'),
            ],
        ])->send();
    }

    public function createCustomer($data, $invoice)
    {
        return $this->gateway->createCustomer([
            'name'  => $invoice->contact->name,
            'email' => $invoice->contact->email,
            'token' => $data['id']
        ])->send();
    }

    public function getCard($request)
    {
        return $this->gateway->createCard(['card' => [
            'name' => $request->get('cardName'),
            'number' => $request->get('cardNumber'),
            'expiryMonth' => $request->get('cardMonth'),
            'expiryYear' => $request->get('cardYear'),
            'cvv' => $request->get('cardCvv'),
        ]])->send()->getCardReference();
    }

    public function createCard($data, $stripe_customer)
    {
        return $this->gateway->createCard([
            'customerReference' => $stripe_customer->stripe_customer_id,
            'source'            => $data['id']
        ])->send();
    }

    public function fetchCustomer($stripe_customer)
    {
        return $this->gateway->fetchCustomer([
            'customerReference' => $stripe_customer->stripe_customer_id,
        ])->send();
    }

    public function createRegisteredTransaction($registered)
    {
        RegisteredTransaction::create([
            'company_id'            => company_id(),
            'invoice_id'            => $registered[0],
            'stripe_customer_id'    => $registered[1],
            'stripe_card_id'        => $registered[2],
        ]);
    }
}
