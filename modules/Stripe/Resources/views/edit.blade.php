<x-layouts.admin>
    <x-slot name="title">{{ trans('stripe::general.name') }}</x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="stripe" method="POST" route="stripe.settings.update">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('general.general') }}" description="{{ trans('stripe::general.description') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text name="secret_key" label="{{ trans('stripe::general.form.secret_key') }}" :value="old('secret_key', setting('stripe.secret_key'))" />

                        <x-form.group.select name="account_id" label="{{ trans_choice('general.accounts', 1) }}" :options="$accounts" :selected="old('account_id', setting('stripe.account_id'))" not-required />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('stripe::general.payment') }}" description="" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text name="name" label="{{ trans('stripe::general.form.name') }}" :value="old('name', setting('stripe.name'))" />

                        <x-form.group.text name="order" label="{{ trans('stripe::general.form.order') }}" :value="old('order', setting('stripe.order'))" />

                        <x-form.group.toggle name="customer" label="{{ trans('general.enabled') }}" :value="old('customer', setting('stripe.customer'))" not-required />

                        <x-form.group.toggle name="store_card" label="{{ trans('stripe::general.form.store_card') }}" :value="old('store_card', setting('stripe.store_card'))" not-required />

                        <x-form.group.toggle name="recurring_payments" label="{{ trans('stripe::general.form.recurring_payments') }}" :value="old('recurring_payments', setting('stripe.recurring_payments'))" not-required />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('stripe::general.sync') }}" description="" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select
                            name="category_id"
                            label="{{ trans_choice('general.categories', 1) }}"
                            :options="$categories"
                            :selected="old('category_id', setting('stripe.category_id'))" />

                        <x-form.group.toggle name="sync" label="{{ trans('general.enabled') }}" :value="old('sync', setting('stripe.sync'))" />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <div class="flex items-center justify-end sm:col-span-6">
                            <x-button 
                                class="px-3 py-1.5 rounded-xl mr-2 text-xs sm:text-base font-medium leading-6 bg-gray-100 hover:bg-gray-200 disabled:bg-gray-50"
                                override="class"
                                :disabled="!setting('stripe.secret_key')" 
                                @click="count"
                            >
                                @notmobile
                                    <span class="material-icons-outlined align-middle  text-secondary text-2xl">sync</span>
                                @endnotmobile
                                {{ trans('stripe::general.form.sync') }}
                            </x-button>

                            <x-link href="{{ url()->previous() }}" class="px-6 py-1.5 hover:bg-gray-200 rounded-lg ltr:mr-2 rtl:ml-2" override="class">
                                {{ trans('general.cancel') }}
                            </x-link>

                            <x-button
                                type="submit"
                                class="relative flex items-center justify-center bg-green hover:bg-green-700 text-white px-6 py-1.5 text-base rounded-lg disabled:bg-green-100"
                                ::disabled="form.loading"
                                override="class"
                            >
                                <i v-if="form.loading" class="animate-submit delay-[0.28s] absolute w-3 h-3 rounded-full left-0 right-0 -top-3.5 m-auto before:absolute before:w-2 before:h-2 before:rounded-full before:animate-submit before:delay-[0.14s] after:absolute after:w-2 after:h-2 after:rounded-full after:animate-submit before:-left-3.5 after:-left-3.5 after:delay-[0.42s]"></i>
                                <span :class="[{'opacity-0': form.loading}]">
                                    {{ trans('general.save') }}
                                </span>
                            </x-button>
                        </div>
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>

        <akaunting-modal
            :show="show">
            <template #card-header>
                <h4 class="text-base font-medium">
                    @{{ title }}
                </h4>

                <button v-show="show_close" type="button" class="text-lg" @click="show = show_close = false" aria-hidden="true">
                    <span class="rounded-md border-b-2 px-2 py-1 text-sm bg-gray-100">esc</span>
                </button>
            </template>
            
                <template #modal-body>
                    <div class="py-1 px-5 bg-body h-5/6 overflow-y-auto">
                        <el-progress :text-inside="true" :stroke-width="24" :percentage="progress.total" :status="progress.status"></el-progress>
                        <div class="mt-3.5" id="progress-text" v-html="progress.text"></div>
                    </div>
                </template>

                <template #card-footer>
                    <span></span>
                </template>
            </akaunting-modal>
    </x-slot>

    <x-script alias="stripe" file="stripe" />
</x-layouts.admin>

