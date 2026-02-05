<?php

namespace Modules\Stripe\Listeners;

use App\Events\Menu\SettingsCreated as Event;
use App\Traits\Modules;
use App\Traits\Permissions;

class ShowInSettingsMenu
{
    use Modules, Permissions;

    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if (!$this->moduleIsEnabled('stripe')) {
            return;
        }

        $title = trans('stripe::general.name');

        if ($this->canAccessMenuItem($title, 'read-stripe-settings')) {
            $event->menu->route('stripe.settings.edit', $title, [], 100, ['icon' => 'simple-icons-stripe', 'search_keywords' => trans('stripe::general.description')]);
        }
    }
}
