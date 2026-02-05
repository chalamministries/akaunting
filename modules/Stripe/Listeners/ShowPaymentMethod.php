<?php

namespace Modules\Stripe\Listeners;

use App\Events\Module\PaymentMethodShowing as Event;
use App\Traits\Modules;

class ShowPaymentMethod
{
    use Modules;

    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if (! $this->moduleIsEnabled('stripe')) {
            return;
        }

        $method = setting('stripe');

        $method['code'] = 'stripe';

        $event->modules->payment_methods[] = $method;
    }
}
