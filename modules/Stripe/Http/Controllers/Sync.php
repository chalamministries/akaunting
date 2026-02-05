<?php

namespace Modules\Stripe\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Jobs\Banking\UpdateTransaction;
use App\Models\Banking\Transaction;
use App\Utilities\Date;
use Modules\Stripe\Jobs\CreateDocument;
use Modules\Stripe\Jobs\UpdateDocument;
use Illuminate\Support\Facades\Cache;

class Sync extends Controller
{
    public function getStripes()
    {
        $stripe = new \Stripe\StripeClient(setting('stripe.secret_key'));

        try {
            $checkout_data = [];

            $checkouts = $stripe->checkout->sessions->all(['limit' => '100']);

            foreach ($checkouts as $checkout) {
                if ($checkout->payment_intent) {
                    $checkout_data[$checkout->payment_intent] = $checkout->success_url ?? null;
                }
            }

            $charges = $stripe->charges->all(['limit' => '100']);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

        $count = 0;

        foreach ($charges->autoPagingIterator() as $key => $transaction) {
            $steps[] = [
                'text' => trans('stripe::general.sync_text', [
                    'type' => trans('stripe::general.transaction'),
                    'value' => $transaction->id
                ]),
                'url'  => route('stripe.sync.send', $transaction->id)
            ];

            $count++;

            $all_transactions[$transaction->id] = $transaction;
        }

        $company_id = company_id();

        $cache_data = [
            'all_transactions_' => $count ? $all_transactions : null,
            'all_checkouts_' => $checkout_data,
            'last_transactions_' => $count ? end($all_transactions)->id : null,
            'transactions_count_' => $count,
            'transactions_steps_' => $count ? $steps : null,
        ];

        foreach ($cache_data as $key => $value) {
            Cache::put($key . $company_id, $value, Date::now()->addHour(6));
        }
    }

    public function count()
    {
        $company_id = company_id();

        $charges = $this->getStripes();

        $count = Cache::get('transactions_count_'   . $company_id);
        $steps = Cache::get('transactions_steps_'   . $company_id);

        $json = [
            'title' => trans('stripe::general.total', ['count' => $count]),
            'errors' => false,
            'success' => true,
            'count' => $count,
            'steps' => $steps,
        ];

        if (is_string($charges)) {
            $json = array_merge($json, [
                'title' => $charges,
                'errors' => true,
                'success' => false,
            ]);
        } elseif ($count === 0) {
            $json = array_merge($json, [
                'title' => trans('stripe::general.error.nothing_to_sync'),
                'errors' => false,
                'success' => false,
            ]);
        }

        return response()->json($json);
    }

    public function send($transaction_id)
    {
        $company_id = company_id();

        $all_transactions = Cache::get('all_transactions_'  . $company_id);
        $checkouts = Cache::get('all_checkouts_'  . $company_id);
        $last_transaction = Cache::get('last_transactions_'  . $company_id);

        if ($checkout = $checkouts[$all_transactions[$transaction_id]->payment_intent]) {
            $transaction = Transaction::where('reference', $all_transactions[$transaction_id]->id)->first();

            if ($transaction) {
                $response = $this->ajaxDispatch(new UpdateDocument($transaction, $all_transactions[$transaction_id]));
            } else {
                $response = $this->updateTransaction($all_transactions[$transaction_id], $checkout);
            }
        } else {
            $response = $this->ajaxDispatch(new CreateDocument($all_transactions[$transaction_id]));
        }

        if ($last_transaction == $transaction_id) {
            $response['finished'] = trans('stripe::general.success.transactions_synced');

            $timestamp = Date::now()->toRfc3339String();

            setting()->set('stripe.last_check', $timestamp);
            setting()->save();

            $cache_key = [
                'all_transactions_',
                'last_transactions_',
                'transactions_count_',
                'transactions_steps_'
            ];

            foreach ($cache_key as $key) {
                Cache::forget($key . $company_id);
            }
        }

        return response()->json($response);
    }

    public function updateTransaction($charge, $checkout)
    {
        $invoice_id = null;

        if (preg_match('/\/invoices\/(\d+)\/return/', $checkout, $matches)) {
            $invoice_id = $matches[1];
        }

        if (! $invoice_id) {
            return [
                'title' => trans('stripe::general.error.nothing_to_sync'),
                'errors' => false,
                'success' => false,
            ];
        }

        $transaction = Transaction::where('document_id', $invoice_id)->first();

        if (! $transaction) {
            return [
                'title' => trans('stripe::general.error.nothing_to_sync'),
                'errors' => false,
                'success' => false,
            ];
        }

        return $this->ajaxDispatch(new UpdateTransaction($transaction, [
            'reference' => $charge->id
        ]));
    }
}
