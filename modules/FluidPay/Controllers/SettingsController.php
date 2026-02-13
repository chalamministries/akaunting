<?php

namespace Modules\FluidPay\Controllers;

use App\Abstracts\Http\Controller;
use App\Traits\Modules as ModulesTrait;
use App\Utilities\Modules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\FluidPay\Support\Config;

class SettingsController extends Controller
{
    use ModulesTrait;

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
            'environment' => $this->getSettingValue('environment') ?: 'sandbox',
            'options' => $this->getOptions(),
            'show_retainers' => $this->moduleIsEnabled('retainers'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'public_key' => ['required', 'string'],
            'private_key' => ['nullable', 'string'],
            'environment' => ['nullable', 'in:sandbox,production'],
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

        if (! empty($validated['environment'])) {
            setting([
                'fluidpay.environment' => $validated['environment'],
                'fluidpay_environment' => $validated['environment'],
            ])->save();
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

        $serializedOptions = json_encode($options);

        setting([
            'fluidpay.options' => $serializedOptions,
            'fluidpay_options' => $serializedOptions,
        ])->save();

        $this->storeTokenizerSettings($options);

        Modules::clearPaymentMethodsCache();

        return response()->json([
            'success' => true,
            'error' => false,
            'redirect' => route('fluidpay.settings.edit'),
        ]);
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
            } else {
                $unserialized = @unserialize($stored);

                $stored = is_array($unserialized) ? $unserialized : [];
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

    protected function storeTokenizerSettings(array $options): void
    {
        $documentMap = [
            'invoice' => Config::DOCUMENT_INVOICES,
            'retainer' => Config::DOCUMENT_RETAINERS,
        ];

        foreach ($documentMap as $type => $document) {
            $tokenizerSettings = $this->buildTokenizerSettings($options[$type] ?? [], $document);
            $serialized = json_encode($tokenizerSettings);

            setting([
                "fluidpay.$document" => $serialized,
                "fluidpay_{$document}" => $serialized,
            ])->save();
        }
    }

    protected function buildTokenizerSettings(array $options, string $document): array
    {
        $settings = Config::defaults($document);

        $settings['payment']['types']['card'] = (bool) data_get($options, 'payment.enable_card', true);
        $settings['payment']['types']['ach'] = (bool) data_get($options, 'payment.enable_ach', true);
        $settings['payment']['card']['requireCVV'] = (bool) data_get($options, 'payment.require_cvv', true);
        $settings['payment']['card']['strict_mode'] = (bool) data_get($options, 'payment.strict_mode', false);
        $settings['payment']['card']['mask_number'] = (bool) data_get($options, 'payment.mask_number', false);
        $settings['payment']['ach']['showSecCode'] = (bool) data_get($options, 'payment.ach_show_sec_code', false);
        $settings['payment']['ach']['verifyAccountRouting'] = (bool) data_get($options, 'payment.ach_verify_routing', true);
        $settings['payment']['calculateFees'] = (bool) data_get($options, 'payment.calculate_fees', true);

        $settings['user']['showName'] = (bool) data_get($options, 'user.show_name', true);
        $settings['user']['showEmail'] = (bool) data_get($options, 'user.show_email', true);
        $settings['user']['showPhone'] = (bool) data_get($options, 'user.show_phone', true);
        $settings['user']['showTitle'] = (bool) data_get($options, 'user.show_title', true);
        $settings['user']['showInline'] = (bool) data_get($options, 'user.show_inline', true);

        $settings['billing']['show'] = (bool) data_get($options, 'billing.show', true);
        $settings['billing']['showTitle'] = (bool) data_get($options, 'billing.show_title', true);

        $settings['shipping']['show'] = (bool) data_get($options, 'shipping.show', true);
        $settings['shipping']['showTitle'] = (bool) data_get($options, 'shipping.show_title', true);

        return $settings;
    }
}
