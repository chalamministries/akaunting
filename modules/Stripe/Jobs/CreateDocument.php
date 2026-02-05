<?php

namespace Modules\Stripe\Jobs;

use App\Abstracts\Job;
use App\Traits\Documents;
use App\Interfaces\Job\HasOwner;
use App\Models\Setting\Category;
use App\Interfaces\Job\HasSource;
use App\Models\Document\Document;
use App\Interfaces\Job\ShouldCreate;
use App\Http\Requests\Document\Document as Request;
use App\Jobs\Document\CreateDocument as BaseCreateDocument;
use App\Utilities\Date;
use Modules\Stripe\Traits\StripeSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateDocument extends Job implements HasOwner, HasSource, ShouldCreate, ShouldQueue
{
    use Documents, StripeSync;

    protected $charge;

    protected $type = null;

    /**
     * Create a new job instance.
     *
     * @param  $charge
     */
    public function __construct($charge)
    {
        $this->charge = $charge;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::transaction(function () {
            $amount = ($this->charge->amount - $this->charge->amount_refunded) / 100;

            if ($amount < 0) {
                return;
            }

            $this->selectCurrency();

            $this->type = Document::BILL_TYPE;

            if ($this->charge->amount_refunded > 0) {
                $bill = $this->createDocument($this->charge->amount_refunded / 100, $this->getContact(), null);
            }

            $this->type = Document::INVOICE_TYPE;

            return $this->createDocument($this->charge->amount / 100, $this->getContact(), $bill ?? null);
        });
    }

    protected function createDocument($amount, $contact, $bill)
    {
        $date = Date::parse($this->charge->created)->format('Y-m-d H:i:s');

        $category_type = $this->type == 'invoice' ? 'income' : 'expense';

        $total = $this->type == 'invoice' ? [
            [
                'code'      => 'application_fee',
                'name'      => trans('stripe::general.application_fee'),
                'amount'    => $this->charge->application_fee_amount,
            ],
        ] : null;

        $notes = $this->type == 'invoice' && $this->charge->amount_refunded > 0
        ? trans('stripe::general.stripe_bill_url', [
            'url'           => route('bills.show', $bill->id),
            'amount'        => money($amount, $this->currency->code, true),
            'bill_number'   => $bill->document_number,
            'stripe_id'     => $this->charge->id,
        ])
        : null;

        $document_request = new Request();

        $document = $this->dispatch(new BaseCreateDocument($document_request->merge([
            'company_id'            => company_id(),
            'contact_id'            => $contact->id,
            'amount'                => '0',
            'issued_at'             => $date,
            'due_at'                => $date,
            'type'                  => $this->type,
            'document_number'       => $this->getNextDocumentNumber($this->type),
            'order_number'          => $this->charge->order,
            'currency_code'         => $this->currency->code,
            'currency_rate'         => $this->currency->rate,
            'items'                 => $this->getItems($amount),
            'discount'              => '0',
            'notes'                 => $notes,
            'category_id'           => setting('stripe.category_id') ?? Category::type($category_type)->enabled()->first(),
            'recurring_frequency'   => 'no',
            'contact_name'          => $contact->name,
            'contact_email'         => $contact->email,
            'contact_phone'         => $contact->phone,
            'contact_address'       => $contact->address,
            'status'                => 'draft',
            'created_from'          => source_name('stripe'),
            'totals'                => $total,
        ])));

        if ($this->charge->captured == true) {
            event(new \App\Events\Document\PaymentReceived($document, [
                'type' => $category_type,
                'mark_paid' => $this->type,
                'reference' => $this->charge->id,
                'created_from' => $document->created_from,
            ]));
        }

        return $document;
    }
}
