<?php

namespace Modules\Stripe\Listeners\Document;

use App\Events\Document\DocumentRecurring as Event;
use App\Models\Module\Module;
use App\Http\Requests\Portal\InvoicePayment as PaymentRequest;
use App\Jobs\Banking\CreateBankingDocumentTransaction;
use App\Traits\Modules;
use Date;
use Modules\Stripe\Models\Customer;
use Modules\Stripe\Models\RegisteredTransaction;
use Modules\Stripe\Traits\StripePayments;

class DocumentRecurring
{
    use StripePayments, Modules;

    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {

        /*
            In the new version of Stripe, we do not make recurring payments because the card information 
            is only entered through the checkout form it provides. It is being returned for now.
        */
        return;

        if (!$this->moduleIsEnabled('stripe')) {
            return;
        }

        if ($event->document->type != 'invoice') {
            return;
        }

        if (setting('stripe.recurring_payments') != true) {
            return;
        }

        $registered_transaction = RegisteredTransaction::where('invoice_id', $event->document->parent_id)->first();

        if (empty($registered_transaction)) {
            return;
        }

        \Stripe\Stripe::setApiKey(setting('stripe.secret_key'));

        $payment_request = new PaymentRequest();

        try {
            $customer = Customer::where('stripe_customer_id', $registered_transaction->stripe_customer_id)
                                ->where('stripe_card_id', $registered_transaction->stripe_card_id)
                                ->first();

            $precision = config('money.currencies.' . $event->document->currency_code . '.precision');

            $charge = \Stripe\Charge::create([
                'amount'    => $this->toStripeFormat($event->document->amount, $precision),
                'currency'  => mb_strtolower($event->document->currency_code),
                'customer'  => $customer->stripe_customer_id,
                'card'      => $customer->stripe_card_id,
            ]);
        } catch (\Exception $ex) {
            flash($ex->getMessage())->error()->important();

            return response()->json([
                'redirect'  => redirect()->back()->getTargetUrl(),
                'success'   => false,
                'data'      => false,
            ]);
        }

        // Mark paid
        $event->document->status = 'paid';
        $event->document->save();

        $date = Date::parse($charge->created)->format('Y-m-d H:i:s');

        $payment_data = [
            'company_id'        => $event->document->company_id,
            'type'              => 'income',
            'document_id'       => $event->document->id,
            'account_id'        => setting('stripe.account_id', setting('default.account')),
            'currency_code'     => $event->document->currency_code,
            'currency_rate'     => $event->document->currency_rate,
            'amount'            => $event->document->amount,
            'paid_at'           => $date,
            'payment_method'    => setting('default.payment_method'),
            'reference'         => 'stripe-id:' . $charge->id,
        ];

        $payment_request = new PaymentRequest();
        $payment_request->merge($payment_data);

        $invoice_payment = dispatch(new CreateBankingDocumentTransaction($event->document, $payment_request));
    }

    public static function toStripeFormat(float $amount, $precision)
    {
        for($i=1; $i<=$precision; $i++) {
            $amount = $amount * 10;
        }

        return $amount;
    }
}
