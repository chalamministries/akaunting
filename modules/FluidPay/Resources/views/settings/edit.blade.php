<x-layouts.admin>
    <x-slot name="title">{{ __('fluidpay::settings.title') }}</x-slot>

    @php
        $options = $options ?? [];

        $fieldName = function (string $type, string $group, string $key): string {
            return sprintf('%s_%s_%s', $type, $group, $key);
        };

        $optionValue = function (string $type, string $group, string $key) use ($options): bool {
            return (bool) data_get($options, "{$type}.{$group}.{$key}", false);
        };

        $typesMeta = [
            'invoice' => [
                'title' => __('fluidpay::settings.sections.invoice.title'),
                'description' => __('fluidpay::settings.sections.invoice.description'),
            ],
            'retainer' => [
                'title' => __('fluidpay::settings.sections.retainer.title'),
                'description' => __('fluidpay::settings.sections.retainer.description'),
            ],
        ];
    @endphp

    <x-slot name="content">
        <x-form.container>
            <x-form id="setting" method="POST" route="fluidPay.settings.update">
                <x-form.section  style="background-color: #f2f4fc; padding: 15px; border-radius: 5px" class="rounded-lg">
                    <x-slot name="head">
                        <x-form.section.head
                            title="{{ __('fluidpay::settings.sections.credentials.title') }}"
                            description="{{ __('fluidpay::settings.sections.credentials.description') }}"
                        />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text
                            name="public_key"
                            label="{{ __('fluidpay::settings.fields.public_key') }}"
                            value="{{ old('public_key', $public_key) }}"
                            required
                            autocomplete="off"
                        />

                        <x-form.group.text
                            name="private_key"
                            label="{{ __('fluidpay::settings.fields.private_key') }}"
                            value="{{ old('private_key', $private_key) }}"
                            autocomplete="off"
                            not-required
                        />

                        <x-form.group.select
                            name="environment"
                            label="{{ __('fluidpay::settings.fields.environment') }}"
                            :options="[
                                'sandbox' => __('fluidpay::settings.options.environment.sandbox'),
                                'production' => __('fluidpay::settings.options.environment.production'),
                            ]"
                            :selected="old('environment', $environment ?? 'sandbox')"
                            form-group-class="sm:col-span-6"
                        />

                        <div id="fluidpay-env-hint" class="sm:col-span-6 text-xs text-orange-500 hidden">
                            {{ __('fluidpay::settings.messages.environment_changed') }}
                        </div>

                        <div class="sm:col-span-6 text-xs text-gray-500">
                            {!! __('fluidpay::settings.help.credentials') !!}
                        </div>
                    </x-slot>
                </x-form.section>

                @foreach ($typesMeta as $type => $meta)
                    @if ($type === 'retainer' && empty($show_retainers))
                        @continue
                    @endif

                    <x-form.section style=" background-color: #f2f4fc; padding: 15px; border-radius: 5px" class="rounded-lg">
                        <x-slot name="head">
                            <x-form.section.head
                                title="{{ $meta['title'] }}"
                                description="{{ $meta['description'] }}"
                            />
                        </x-slot>

                        <x-slot name="body">
                            <div class="space-y-8 col-span-full">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide" style="    background-color: white;
                                    padding: 5px 10px;
                                    display: inline-block;
                                    border-radius: 5px;">
                                        {{ __('fluidpay::settings.groups.payment') }}
                                    </h3>
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-6">
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'payment', 'enable_card') }}"
                                            label="{{ __('fluidpay::settings.fields.enable_card') }}"
                                            :value="$optionValue($type, 'payment', 'enable_card')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'payment', 'enable_ach') }}"
                                            label="{{ __('fluidpay::settings.fields.enable_ach') }}"
                                            :value="$optionValue($type, 'payment', 'enable_ach')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'payment', 'require_cvv') }}"
                                            label="{{ __('fluidpay::settings.fields.require_cvv') }}"
                                            :value="$optionValue($type, 'payment', 'require_cvv')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'payment', 'mask_number') }}"
                                            label="{{ __('fluidpay::settings.fields.mask_number') }}"
                                            :value="$optionValue($type, 'payment', 'mask_number')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'payment', 'strict_mode') }}"
                                            label="{{ __('fluidpay::settings.fields.strict_mode') }}"
                                            :value="$optionValue($type, 'payment', 'strict_mode')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'payment', 'calculate_fees') }}"
                                            label="{{ __('fluidpay::settings.fields.calculate_fees') }}"
                                            :value="$optionValue($type, 'payment', 'calculate_fees')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'payment', 'ach_verify_routing') }}"
                                            label="{{ __('fluidpay::settings.fields.ach_verify_routing') }}"
                                            :value="$optionValue($type, 'payment', 'ach_verify_routing')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'payment', 'ach_show_sec_code') }}"
                                            label="{{ __('fluidpay::settings.fields.ach_show_sec_code') }}"
                                            :value="$optionValue($type, 'payment', 'ach_show_sec_code')"
                                            not-required
                                        />
                                    </div>
                                </div>
                                <hr />
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide" style="    background-color: white;
                                    padding: 5px 10px;
                                    display: inline-block;
                                    border-radius: 5px;">
                                        {{ __('fluidpay::settings.groups.customer') }}
                                    </h3>
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-6">
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'user', 'show_name') }}"
                                            label="{{ __('fluidpay::settings.fields.show_name') }}"
                                            :value="$optionValue($type, 'user', 'show_name')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'user', 'show_email') }}"
                                            label="{{ __('fluidpay::settings.fields.show_email') }}"
                                            :value="$optionValue($type, 'user', 'show_email')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'user', 'show_phone') }}"
                                            label="{{ __('fluidpay::settings.fields.show_phone') }}"
                                            :value="$optionValue($type, 'user', 'show_phone')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'user', 'show_title') }}"
                                            label="{{ __('fluidpay::settings.fields.user_show_title') }}"
                                            :value="$optionValue($type, 'user', 'show_title')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'user', 'show_inline') }}"
                                            label="{{ __('fluidpay::settings.fields.user_show_inline') }}"
                                            :value="$optionValue($type, 'user', 'show_inline')"
                                            not-required
                                        />
                                    </div>
                                </div>
                                <hr />
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide" style="    background-color: white;
                                    padding: 5px 10px;
                                    display: inline-block;
                                    border-radius: 5px;">
                                        {{ __('fluidpay::settings.groups.billing') }}
                                    </h3>
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-6">
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'billing', 'show') }}"
                                            label="{{ __('fluidpay::settings.fields.billing_show') }}"
                                            :value="$optionValue($type, 'billing', 'show')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'billing', 'show_title') }}"
                                            label="{{ __('fluidpay::settings.fields.billing_show_title') }}"
                                            :value="$optionValue($type, 'billing', 'show_title')"
                                            not-required
                                        />
                                    </div>
                                </div>
                                <hr />
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide" style="    background-color: white;
                                    padding: 5px 10px;
                                    display: inline-block;
                                    border-radius: 5px;">
                                        {{ __('fluidpay::settings.groups.shipping') }}
                                    </h3>
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-6">
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'shipping', 'show') }}"
                                            label="{{ __('fluidpay::settings.fields.shipping_show') }}"
                                            :value="$optionValue($type, 'shipping', 'show')"
                                            not-required
                                        />
                                        <x-form.group.toggle
                                            name="{{ $fieldName($type, 'shipping', 'show_title') }}"
                                            label="{{ __('fluidpay::settings.fields.shipping_show_title') }}"
                                            :value="$optionValue($type, 'shipping', 'show_title')"
                                            not-required
                                        />
                                    </div>
                                </div>
                            </div>
                        </x-slot>
                    </x-form.section>
                @endforeach

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons without-cancel />

                        <x-form.input.hidden name="module_alias" :value="'fluidPay'" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>
    </x-slot>

    <x-script folder="settings" file="settings" />
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const select = document.querySelector('select[name="environment"]');
            const hint = document.getElementById('fluidpay-env-hint');
            if (!select || !hint) {
                return;
            }
            select.addEventListener('change', function () {
                hint.classList.remove('hidden');
            });
        });
    </script>
</x-layouts.admin>
