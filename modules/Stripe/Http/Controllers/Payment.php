<?php

namespace Modules\Stripe\Http\Controllers;

use App\Abstracts\Http\PaymentController;
use App\Models\Document\Document;
use App\Http\Requests\Portal\InvoicePayment as PaymentRequest;
use App\Traits\Omnipay;
use Illuminate\Http\Request;
use Modules\Stripe\Models\Customer;
use Modules\Stripe\Traits\StripePayments;

class Payment extends PaymentController
{
    use Omnipay, StripePayments;

    public $alias = 'stripe';

    public $type = 'redirect';

    public function signed(Document $invoice, PaymentRequest $request)
    {
        return $this->show($invoice, $request, Customer::customerByCards($invoice->contact_id)->pluck('cards', 'id'));
    }

    public function confirm(Document $invoice)
    {
        try {
            $stripe = new \Stripe\StripeClient(setting('stripe.secret_key'));

            $checkout_session = $stripe->checkout->sessions->create([
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $invoice->currency_code,
                            'unit_amount' => $invoice->amount * 100,
                            'product_data' => [
                                'name' => $invoice->document_number,
                            ],
                        ],
                        'quantity' => 1
                    ]
                ],
                'mode' => 'payment',
                'success_url' => $this->getReturnUrl($invoice),
                'cancel_url' => $this->getCancelUrl($invoice),
            ]);

            $json = [
                'redirect'  => $checkout_session->url
            ];
        } catch (\Throwable $th) {
            flash($th->getMessage())->error()->important();
    
            $json = [
                'success' => false,
                'error' => $th->getMessage(),
                'data' => [],
                'redirect' => $this->getInvoiceUrl($invoice),
            ];
        }

        return response()->json($json);
    }

    public function return(Document $invoice, Request $request)
    {
        $this->setReference($invoice, $request->id);

        $this->finish($invoice, $request);

        return redirect($this->getInvoiceUrl($invoice));
    }
}
