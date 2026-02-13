<?php

namespace Modules\FluidPay\Listeners;

use App\Abstracts\Event as BaseEvent;

class EnsureEmailTemplates extends FinishInstallation
{
    public function handle(BaseEvent $event): void
    {
        if ($event->alias !== $this->alias) {
            return;
        }

        $this->createFailedRecurringTemplate((int) $event->company_id);
    }
}
