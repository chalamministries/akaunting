<?php

return [
    'title' => 'FluidPay Settings',

    'sections' => [
        'credentials' => [
            'title' => 'API Credentials',
            'description' => 'Provide your FluidPay API credentials to enable tokenisation and secure payment processing.',
        ],
        'invoice' => [
            'title' => 'Invoice Tokeniser',
            'description' => 'Control the fields and payment methods customers see when paying invoices.',
        ],
        'retainer' => [
            'title' => 'Retainer Tokeniser',
            'description' => 'Configure the FluidPay experience for retainers and deposits.',
        ],
    ],

    'groups' => [
        'payment' => 'Payment options',
        'customer' => 'Customer information',
        'billing' => 'Billing address',
        'shipping' => 'Shipping address',
    ],

    'fields' => [
        'public_key' => 'FluidPay Public API Key',
        'private_key' => 'FluidPay Private API Key',
        'private_key_placeholder' => 'Optional – only required when charging tokens server-side',
        'enable_card' => 'Enable card payments',
        'enable_ach' => 'Enable ACH payments',
        'require_cvv' => 'Require CVV',
        'mask_number' => 'Mask card number',
        'strict_mode' => 'Enable strict card mode',
        'calculate_fees' => 'Calculate processor fees automatically',
        'ach_show_sec_code' => 'Display ACH SEC code selector',
        'ach_verify_routing' => 'Validate ACH routing numbers',
        'show_name' => 'Collect customer name',
        'show_email' => 'Collect customer email',
        'show_phone' => 'Collect customer phone',
        'user_show_title' => 'Show customer section heading',
        'user_show_inline' => 'Display customer fields inline',
        'billing_show' => 'Collect billing address',
        'billing_show_title' => 'Show billing section heading',
        'shipping_show' => 'Collect shipping address',
        'shipping_show_title' => 'Show shipping section heading',
    ],

    'placeholders' => [
        'private_key_saved' => '•••••••••• (stored)',
    ],

    'help' => [
        'credentials' => 'Generate API keys in your FluidPay dashboard. Keep your private key secure; only your public key is required for the tokeniser.',
    ],

    'messages' => [
        'saved' => 'FluidPay settings saved successfully!',
    ],
];
