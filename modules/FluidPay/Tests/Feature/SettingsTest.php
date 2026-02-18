<?php

namespace Modules\FluidPay\Tests\Feature;

use Tests\Feature\FeatureTestCase;

class SettingsTest extends FeatureTestCase
{
    public function testItShouldSeeFluidPaySettingsPage(): void
    {
        $this->loginAs()
            ->get(route('fluidPay.settings.edit'))
            ->assertOk()
            ->assertSeeText(trans('fluidpay::settings.title'))
            ->assertSeeText(trans('fluidpay::settings.sections.credentials.title'));
    }

    public function testItShouldUpdateFluidPaySettings(): void
    {
        $request = [
            'public_key' => 'pub_test_123',
            'private_key' => 'sec_test_123',
            'environment' => 'sandbox',
        ];

        $this->loginAs()
            ->post(route('fluidPay.settings.update'), $request)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'error' => false,
            ]);
    }
}
