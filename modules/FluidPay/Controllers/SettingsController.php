<?php

namespace Modules\FluidPay\Controllers;

use App\Abstracts\Http\Controller;
use App\Utilities\Modules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\FluidPay\Support\Config;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read-fluidpay-settings')->only('edit');
        $this->middleware('permission:update-fluidpay-settings')->only('update');
    }

    public function edit(): View
    {
        return view('fluidpay::settings.edit', [
            'public_key' => $this->getSettingValue('public_key'),
            'private_key' => $this->getSettingValue('private_key'),
            'options' => $this->getOptions(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'public_key' => ['required', 'string'],
            'private_key' => ['nullable', 'string'],
        ]);

        setting([
            'fluidpay.public_key' => $validated['public_key'],
            'fluidpay_public_key' => $validated['public_key'],
        ])->save();

        if ($request->has('private_key')) {
            $value = $request->input('private_key');

            if ($value !== null && $value !== '') {
                setting([
                    'fluidpay.private_key' => $value,
                    'fluidpay_private_key' => $value,
                ])->save();
            } else {
                setting()->forget('fluidpay.private_key');
                setting()->forget('fluidpay_private_key');
                setting()->save();
            }
        }

        $options = $this->defaultOptions();

        foreach ($options as $type => $groups) {
            foreach ($groups as $group => $values) {
                foreach ($values as $key => $default) {
                    $field = $this->optionFieldName($type, $group, $key);

                    $options[$type][$group][$key] = (int) $request->input($field, $default ? 1 : 0) === 1;
                }
            }
        }

        setting([
            'fluidpay.options' => $options,
            'fluidpay_options' => $options,
        ])->save();

        Modules::clearPaymentMethodsCache();

        return redirect()->back()->with('success', __('fluidpay::settings.messages.saved'));
    }

    protected function getSettingValue(string $key): ?string
    {
        $namespaced = setting('fluidpay.' . $key);

        if ($namespaced !== null && $namespaced !== '') {
            return $namespaced;
        }

        return setting('fluidpay_' . $key);
    }

    protected function getOptions(): array
    {
        $defaults = $this->defaultOptions();

        $stored = setting('fluidpay.options');

        if ($stored === null) {
            $stored = setting('fluidpay_options');
        }

        if (is_string($stored)) {
            $decoded = json_decode($stored, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $stored = $decoded;
            }
        }

        if (! is_array($stored)) {
            $stored = [];
        }

        return array_replace_recursive($defaults, $stored);
    }

    protected function defaultOptions(): array
    {
        return [
            'invoice' => [
                'payment' => [
                    'enable_card' => true,
                    'enable_ach' => true,
                    'require_cvv' => true,
                    'mask_number' => false,
                    'strict_mode' => false,
                    'calculate_fees' => true,
                    'ach_show_sec_code' => false,
                    'ach_verify_routing' => true,
                ],
                'user' => [
                    'show_name' => true,
                    'show_email' => true,
                    'show_phone' => true,
                    'show_title' => true,
                    'show_inline' => true,
                ],
                'billing' => [
                    'show' => true,
                    'show_title' => true,
                ],
                'shipping' => [
                    'show' => true,
                    'show_title' => true,
                ],
            ],
            'retainer' => [
                'payment' => [
                    'enable_card' => true,
                    'enable_ach' => true,
                    'require_cvv' => true,
                    'mask_number' => false,
                    'strict_mode' => false,
                    'calculate_fees' => true,
                    'ach_show_sec_code' => false,
                    'ach_verify_routing' => true,
                ],
                'user' => [
                    'show_name' => true,
                    'show_email' => true,
                    'show_phone' => true,
                    'show_title' => true,
                    'show_inline' => true,
                ],
                'billing' => [
                    'show' => true,
                    'show_title' => true,
                ],
                'shipping' => [
                    'show' => false,
                    'show_title' => false,
                ],
            ],
        ];
    }

    protected function optionFieldName(string $type, string $group, string $key): string
    {
        return sprintf('%s_%s_%s', $type, $group, $key);
    }
}
