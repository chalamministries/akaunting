<?php

namespace Modules\Stripe\Console;

use App\Models\Banking\Transaction;
use App\Models\Module\Module;
use App\Traits\Jobs;
use Date;
use Modules\Stripe\Jobs\CreateDocument;
use Modules\Stripe\Jobs\UpdateDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StripeSync extends Command
{
    use Jobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync with Stripe';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $company_ids = Module::allCompanies()
            ->alias('stripe')
            ->enabled()
            ->pluck('company_id');

        foreach ($company_ids as $company_id) {
            company($company_id)->makeCurrent();

            if (empty(setting('stripe.secret_key')) || setting('stripe.sync') == 0) {
                continue;
            }

            try {
                $stripe = new \Stripe\StripeClient(setting('stripe.secret_key'));

                $data = [];

                $checkouts = $stripe->checkout->sessions->all(['limit' => '100', 'created[gt]' => strtotime(setting('stripe.last_check', Date::now()->toRfc3339String()))]);

                foreach ($checkouts as $checkout) {
                    if ($checkout->payment_intent) {
                        $data[$checkout->payment_intent] = $checkout->success_url ?? null;
                    }
                }

                $charges = $stripe->charges->all(['limit' => '100', 'created[gt]' => strtotime(setting('stripe.last_check', Date::now()->toRfc3339String()))]);

                setting()->set('stripe.last_check', Date::now()->toRfc3339String());
                setting()->save();

                if (empty($charges->data)) {
                    continue;
                }

                foreach ($charges->autoPagingIterator() as $charge) {
                    if ($charge->paid != true) {
                        continue;
                    }

                    if ($checkout = $data[$charge->payment_intent]) {
                        $transaction = Transaction::where('reference', $charge->id)->first();

                        if ($transaction) {
                            $this->dispatch(new UpdateDocument($transaction, $charge));

                            continue;
                        }

                        $invoice_id = null;

                        if (preg_match('/\/invoices\/(\d+)\/return/', $checkout, $matches)) {
                            $invoice_id = $matches[1];
                        }

                        if ($invoice_id) {
                            Transaction::where('document_id', $invoice_id)->update(['reference' => $charge->id]);
                        }
                    } else {
                        $this->dispatch(new CreateDocument($charge));
                    }
                }
            } catch (\Exception | \RuntimeException $e) {
                $this->error($e->getMessage());

                Log::error(
                    'Stripe Integration::: Exception:' . basename($e->getFile()) . ':' . $e->getLine() . ' - '
                    . $e->getCode() . ': ' . $e->getMessage()
                );

                setting()->set('stripe.sync', 0);
                setting()->save();

                continue;
            }
        }
    }
}
