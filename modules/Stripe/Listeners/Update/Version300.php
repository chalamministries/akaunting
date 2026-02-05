<?php

namespace Modules\Stripe\Listeners\Update;

use App\Abstracts\Listeners\Update as Listener;
use App\Events\Install\UpdateFinished;
use Illuminate\Support\Facades\File;

class Version300 extends Listener
{
    const ALIAS = 'stripe';

    const VERSION = '3.0.0';

    /**
     * Handle the event.
     *
     * @param  $event
     * @return void
     */
    public function handle(UpdateFinished $event)
    {
        if ($this->skipThisUpdate($event)) {
            return;
        }

        File::delete(base_path('modules/Stripe/Listeners/ShowInSettingsPage.php'));

        File::deleteDirectory(base_path('modules/Stripe/Resources/views/partial/sync.blade.php'));
    }
}
