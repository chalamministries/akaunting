<?php

return [

    'name'                      => 'Stripe',
    'description'               => 'Get invoices paid and sync the other payments',
    'payment'                   => 'Payment',
    'sync'                      => 'Sync',
    'total'                     => 'Total transaction count: :count',
    'application_fee'           => 'Stripe Application Fee',
    'stripe_item'               => 'Stripe Service',
    'stripe_bill_url'           => ':amount of <b>:stripe_id</b> transaction has been refunded via <a href=":url">:bill_number</a>',
    'sync_text'                 => 'Sync this :type: :value',
    'transaction'               => 'Transaction',

    'form' => [
        'secret_key'            => 'Secret Key',
        'name'                  => 'Display Name',
        'order'                 => 'Order',
        'sync'                  => 'Sync Current Transactions',
        'recurring_payments'    => 'Recurring Payments',
        'store_card'            => 'Store Card',
    ],

    'card' => [
        'name'                  => 'Name on Card',
        'number'                => 'Card Number',
        'expiry'                => 'Card Expiry',
        'cvc'                   => 'Card CVC',
        'confirm'               => 'Confirm',
    ],

    'success' => [
        'settings_saved'        => 'Setting saved',
        'transactions_synced'   => 'Transactions synced'
    ],

    'error' => [
        'nothing_to_sync'       => 'There is no data found to be synced',
        'failed_authentication' => 'Failed authentication',

        'card_expiry'  => [
            'min'      => 'Wrong date format. Correct: 11 / 2017',
        ]
    ],
];
