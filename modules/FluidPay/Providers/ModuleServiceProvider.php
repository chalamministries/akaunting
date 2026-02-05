<?php

namespace Modules\FluidPay\Providers;

use App\Events\Menu\SettingsCreated;
use App\Events\Module\PaymentMethodShowing;
use App\Traits\Modules;
use App\Traits\Permissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    use Modules, Permissions;

    protected string $alias = 'fluidpay';

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'fluidpay');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'fluidpay');

        $this->registerPermissions();
        $this->registerSettingsMenu();

        Event::listen(PaymentMethodShowing::class, function ($event): void {
            $publicKey = $this->getPublicKey();

            if (empty($publicKey)) {
                return;
            }

            $name = trans('fluidpay::general.name');
            if ($name === 'fluidpay::general.name') {
                $name = 'FluidPay';
            }

            $event->modules->payment_methods[] = [
                'name' => $name,
                'code' => 'fluidpay.tokenizer',
                'customer' => true,
                'order' => 500,
            ];
        });

        $self = $this;

        View::composer([
            'portal.invoices.show',
            'portal.invoices.preview',
            'portal.invoices.signed',
        ], function ($view) use ($self): void {
            static $assetsInjected = false;

            if ($assetsInjected) {
                return;
            }

            if (empty($self->getPublicKey())) {
                return;
            }

            $view->getFactory()->startPush('scripts_end', view('fluidpay::portal.scripts')->render());

            $assetsInjected = true;
        });
    }

    public function register(): void
    {
        //
    }

    protected function getPublicKey(): ?string
    {
        $namespaced = setting('fluidpay.public_key');

        if ($namespaced !== null && $namespaced !== '') {
            return $namespaced;
        }

        return setting('fluidpay_public_key');
    }

    protected function registerPermissions(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        $this->attachPermissionsToAdminRoles([
            'read-fluidpay-settings',
            'update-fluidpay-settings',
        ]);
    }

    protected function registerSettingsMenu(): void
    {
        $self = $this;

        Event::listen(SettingsCreated::class, function ($event) use ($self): void {
            if (! $self->moduleIsEnabled($self->alias)) {
                return;
            }

            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();

            if (! $user || ! $user->can('read-fluidpay-settings')) {
                return;
            }

            $name = trans('fluidpay::general.name');
            if ($name === 'fluidpay::general.name') {
                $name = 'FluidPay';
            }

            $description = trans('fluidpay::general.description');
            if ($description === 'fluidpay::general.description') {
                $description = 'FluidPay payment gateway integration.';
            }

            $event->menu->route(
                'fluidpay.settings.edit',
                $name,
                [],
                1200,
                [
                    'icon' => 'credit_card',
                    'search_keywords' => $description,
                ]
            );
        });
    }

}
