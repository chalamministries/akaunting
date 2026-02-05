@php
    $formattedAmount = money($invoice->amount_due, $invoice->currency_code);
@endphp

<div class="space-y-4">
    <div class="rounded-md border border-gray-200 bg-white p-4 shadow-sm">
        <div class="text-base font-semibold text-gray-900">
            {{ __('Pay Invoice :number', ['number' => $invoice->document_number]) }}
        </div>

        <p class="mt-2 text-sm text-gray-600">
            {{ __('Amount due: :amount', ['amount' => $formattedAmount]) }}
        </p>
    </div>

    <div class="rounded-md border border-gray-200 bg-white p-4 shadow-sm">
        @php
            $logoPath = base_path('modules/FluidPay/Assets/logo.svg');
            $logoData = ($logoPath && file_exists($logoPath))
                ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath))
                : null;
        @endphp

        @if($logoData)
            <div class="mb-4 flex justify-center">
                <img src="{{ $logoData }}" alt="FluidPay" class="h-10" />
            </div>
        @endif

        <div id="{{ $container_id }}" class="min-h-[280px]"></div>
        <div class="hidden" data-fluidpay-config='@json($config ?? [])'></div>
    </div>

    <button
        type="button"
        class="inline-flex items-center justify-center rounded-md bg-purple px-4 py-2 text-sm font-semibold text-white transition hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2"
        data-fluidpay-submit="{{ $container_id }}"
        data-fluidpay-submit-label="{{ __('Pay :amount now', ['amount' => $formattedAmount]) }}"
        data-fluidpay-submit-loading="{{ __('Processing payment...') }}"
    >
        {{ __('Pay :amount now', ['amount' => $formattedAmount]) }}
    </button>

    <p class="text-xs text-gray-500">
        {{ __('Payments are securely processed by FluidPay. Your card details never touch our servers.') }}
    </p>
</div>
