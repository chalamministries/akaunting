<?php

namespace Modules\Stripe\Listeners\Update;

use App\Abstracts\Listeners\Update as Listener;
use App\Events\Install\UpdateFinished;
use App\Traits\Permissions;
use Artisan;

class Version202 extends Listener
{
    use Permissions;

    const ALIAS = 'stripe';

    const VERSION = '2.0.2';

    public $alias = 'stripe';

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

        $this->updatePermissions();
    }

    public function updatePermissions()
    {
        // c=create, r=read, u=update, d=delete
        $this->attachPermissionsToAdminRoles([
            $this->alias . '-settings' => 'r,u',
        ]);
    }
}
