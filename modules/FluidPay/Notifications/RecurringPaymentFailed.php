<?php

namespace Modules\FluidPay\Notifications;

use App\Models\Document\Document;
use App\Notifications\Sale\Invoice as InvoiceNotification;

class RecurringPaymentFailed extends InvoiceNotification
{
    public function __construct(Document $invoice)
    {
        parent::__construct($invoice, 'fluidpay_recurring_failed_customer', false);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }
}
