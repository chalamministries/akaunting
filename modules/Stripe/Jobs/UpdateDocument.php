<?php

namespace Modules\Stripe\Jobs;

use App\Abstracts\Job;
use App\Traits\Documents;
use App\Models\Setting\Category;
use App\Models\Document\Document;
use App\Models\Banking\Transaction;
use App\Interfaces\Job\ShouldUpdate;
use App\Http\Requests\Document\Document as Request;
use App\Jobs\Document\CreateDocument as BaseCreateDocument;
use App\Jobs\Document\UpdateDocument as BaseUpdateDocument;
use App\Utilities\Date;
use Modules\Stripe\Traits\StripeSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateDocument extends Job implements ShouldUpdate, ShouldQueue
{
    use Documents, StripeSync;

    protected $income;

    protected $expense;

    protected $charge;

    protected $type = null;

    /**
     * Create a new job instance.
     *
     * @param  $charge
     */
    public function __construct($transaction, $charge)
    {
        $this->income = $transaction?->type == Transaction::INCOME_TYPE ? $transaction->document : null;

        $this->expense = $transaction?->type == Transaction::EXPENSE_TYPE ? $transaction->document : null;

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

            $this->type = Document::BILL_TYPE;

            if ($this->charge->amount_refunded > 0 && is_null($this->expense)) {
                $this->expense = $this->createDocument($this->charge->amount_refunded / 100);
            } elseif ($this->charge->amount_refunded > 0 && !is_null($this->expense)) {
                $this->updateDocument($this->charge->amount_refunded / 100);
            }

            $this->type = Document::INVOICE_TYPE;

            return $this->updateDocument($this->charge->amount / 100);
        });
    }

    protected function updateDocument($amount)
    {
        $document_request = new Request();

        $document_type = $this->type == 'invoice' ? 'income' : 'expense';

        $total = $this->type == 'invoice' ? [
            [
                'code'      => 'application_fee',
                'name'      => trans('stripe::general.application_fee'),
                'amount'    => $this->charge->application_fee_amount,
            ],
        ] : null;

        $notes = $this->type == 'invoice' && $this->charge->amount_refunded > 0
        ? trans('stripe::general.stripe_bill_url', [
            'url'           => route('bills.show', $this->expense->id),
            'amount'        => money($amount, strtoupper($this->charge->currency), true),
            'bill_number'   => $this->expense->document_number,
            'stripe_id'     => $this->charge->id
        ])
        : null;

        $contact = $this->getContact();

        $document = $this->dispatch(new BaseUpdateDocument($this->$document_type, $document_request->merge([
            'company_id'            => company_id(),
            'contact_id'            => $contact->id,
            'contact_name'          => $contact->name,
            'contact_email'         => $contact->email,
            'contact_phone'         => $contact->phone,
            'contact_address'       => $contact->address,
            'type'                  => $this->type,
            'document_number'       => $this->$document_type->document_number,
            'order_number'          => $this->charge->order,
            'items'                 => $this->getItems($amount),
            'notes'                 => $notes,
            'totals'                => $total,
        ])));

        if ($this->charge->captured == true) {
            $document->status = 'paid';
            $document->save();

            Transaction::where('document_id', $document->id)->update([
                'contact_id'        => $contact->id,
                'amount'            => $document->amount,
                'reference'         => $this->charge->id,
                'description'       => $this->charge->description,
                'created_from'      => $document->created_from,
            ]);
        }

        return $document;
    }

    protected function createDocument($amount)
    {
        $date = Date::parse($this->charge->created)->format('Y-m-d H:i:s');

        $document_request = new Request();

        $contact = $this->getContact();

        $document = $this->dispatch(new BaseCreateDocument($document_request->merge([
            'company_id'            => company_id(),
            'contact_id'            => $contact->id,
            'amount'                => '0',
            'issued_at'             => $date,
            'due_at'                => $date,
            'type'                  => $this->type,
            'document_number'       => $this->getNextDocumentNumber($this->type),
            'order_number'          => $this->charge->order,
            'currency_code'         => setting('default.currency'),
            'currency_rate'         => 1,
            'items'                 => $this->getItems($amount),
            'discount'              => '0',
            'category_id'           => setting('stripe.category_id') ?? Category::type('expense')->enabled()->first(),
            'recurring_frequency'   => 'no',
            'contact_name'          => $contact->name,
            'contact_email'         => $contact->email,
            'contact_phone'         => $contact->phone,
            'contact_address'       => $contact->address,
            'status'                => 'draft',
            'created_from'          => source_name('stripe'),
        ])));

        if ($this->charge->captured == true) {
            event(new \App\Events\Document\PaymentReceived($document, [
                'type' => 'expense',
                'mark_paid' => $this->type,
                'reference' => $this->charge->id,
                'created_from' => $document->created_from,
            ]));
        }

        return $document;
    }
}
