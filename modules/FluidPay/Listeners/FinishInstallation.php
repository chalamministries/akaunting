<?php

namespace Modules\FluidPay\Listeners;

use App\Events\Module\Installed as Event;
use App\Jobs\Setting\CreateEmailTemplate;
use App\Models\Setting\EmailTemplate;
use App\Traits\Jobs;
use Modules\FluidPay\Notifications\RecurringPaymentFailed;

class FinishInstallation
{
    use Jobs;

    protected string $alias = 'fluidpay';

    public function handle(Event $event): void
    {
        if ($event->alias !== $this->alias) {
            return;
        }

        $this->createFailedRecurringTemplate((int) $event->company_id);
    }

    protected function createFailedRecurringTemplate(int $companyId): void
    {
        $alias = 'fluidpay_recurring_failed_customer';

        if (EmailTemplate::where('company_id', $companyId)->alias($alias)->exists()) {
            return;
        }

        $this->dispatch(new CreateEmailTemplate([
            'company_id' => $companyId,
            'alias' => $alias,
            'class' => RecurringPaymentFailed::class,
            'name' => 'fluidpay::settings.email.templates.recurring_failed_customer',
            'subject' => trans('fluidpay::email_templates.' . $alias . '.subject'),
            'body' => trans('fluidpay::email_templates.' . $alias . '.body'),
            'created_from' => 'fluidpay::seed',
        ]));
    }
}
