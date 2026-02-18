<script src="{{ \Modules\FluidPay\Support\Config::tokenizerScriptUrl() }}"></script>
<script>
    window.AkauntingFluidPayConfig = {
        origin: '{{ \Modules\FluidPay\Support\Config::baseUrl() }}',
        maxTokenizerWait: 20,
        messages: @json([
            'tokenization_failed' => __('Tokenization failed. Please verify your information and try again.'),
            'processing_error' => __('Unable to process the payment. Please try again or contact support.'),
            'network_error' => __('Network error while contacting FluidPay. Please try again.'),
            'default_disclosure' => __('Service fees or surcharges may apply. The final amount will be shown before you submit your payment.'),
        ]),
    };
</script>
<script src="{{ mix('Resources/assets/js/fluidpay.min.js', 'modules/FluidPay') }}"></script>
