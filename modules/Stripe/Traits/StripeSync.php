<?php

namespace Modules\Stripe\Traits;

use App\Jobs\Common\CreateContact;
use App\Jobs\Common\CreateItem;
use App\Jobs\Setting\CreateCurrency;
use App\Models\Common\Contact;
use App\Models\Common\Item;
use App\Models\Setting\Currency;

trait StripeSync
{
    protected function selectCurrency()
    {
        if (! empty($this->charge->currency)) {
            $this->currency = Currency::where('code', strtoupper($this->charge->currency) ?? setting('default.currency'))->first();

            if (empty($this->currency)) {
                $this->currency = $this->dispatch(new CreateCurrency([
                    'company_id'    => company_id(),
                    'name'          => strtoupper($this->charge->currency),
                    'code'          => strtoupper($this->charge->currency),
                    'rate'          => 1,
                    'decimal_mark'  => '.',
                    'created_from'  => source_name('stripe'),
                ]));

                config(['money.currencies.' . $this->currency->code . '.rate' => 1]);
            }
        }
    }

    protected function getContact()
    {
        $params = [
            'name' => ['name', 'customer', 'customer_name'],
            'email' => ['email', 'customer_email'],
            'phone' => ['phone', 'telephone'],
        ];

        // find customer info in charge
        foreach ($params as $variable => $param) {
            foreach ($param as $key) {
                if (! empty($this->charge->metadata->{$key})) {
                    $$variable = $this->charge->metadata->{$key};
                    break;
                } elseif (! empty($this->charge->billing_details->{$key})) {
                    $$variable = $this->charge->billing_details->{$key};
                    break;
                }

                $$variable = match ($variable) {
                    'name' => trans('general.na'),
                    'email' => 'no@email.com',
                    'phone' => null,
                };
            }
        }

        if ($email === 'no@email.com' && $name !== trans('general.na')) {
            $email = str($name)->slug('') . '.no' .  '@email.com';
        }

        if (! empty($contact = $this->income->contact ?? $this->expense->contact ?? null) && $contact->email === $email) {
            $contact->update([
                'name' => $name,
                'phone' => $phone,
            ]);

            return $contact;
        }

        $contact = Contact::customer()->email($email)->first();

        if (empty($contact)) {
            $contact = $this->dispatch(new CreateContact([
                'company_id'    => company_id(),
                'type'          => $this->type === 'invoice' ? 'customer' : 'vendor',
                'name'          => $name,
                'email'         => $email,
                'currency_code' => $this->currency->code ?? setting('default.currency'),
                'phone'         => $phone,
                'enabled'       => 1,
                'created_from'  => source_name('stripe'),
            ]));
        }

        return $contact;
    }

    protected function getItems($amount)
    {
        $item = Item::where(['company_id' => company_id(), 'name' => trans('stripe::general.stripe_item')])->first();

        if (is_null($item)) {
            $item = $this->dispatch(new CreateItem([
                'company_id'        => company_id(),
                'name'              => trans('stripe::general.stripe_item'),
                'sale_price'        => 0,
                'purchase_price'    => 0,
                'enabled'           => true,
            ]));
        }

        return [
            [
                'type'      => $this->type,
                'name'      => trans('stripe::general.stripe_item'),
                'item_id'   => $item->id,
                'quantity'  => '1',
                'price'     => $amount,
                'currency'  => $this->currency->code ?? setting('default.currency'),
            ]
        ];
    }
}
