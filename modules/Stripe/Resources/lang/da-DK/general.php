<?php

return [

    'name'                      => 'Stripe',
    'description'               => 'Få fakturaer og kontingenter betalt med kreditkort',
    'payment'                   => 'Betaling',
    'sync'                      => 'Synkroniser',

    'form' => [
        'secret_key'            => 'Hemmelig nøgle',
        'name'                  => 'Visningsnavn',
        'order'                 => 'Bestil',
        'sync'                  => 'Synkroniser nuværende transaktioner',
    ],

    'card' => [
        'name'                  => 'Navn på kort',
        'number'                => 'Kortnummer',
        'expiry'                => 'Kortets udløb',
        'cvc'                   => 'Kort CVC',
        'confirm'               => 'Bekræft',
    ],

    'success' => [
        'settings_saved'        => 'Indstilling gemt',
        'transactions_synced'   => 'Transaktioner synkroniseret'
    ],

    'error' => [
        'nothing_to_sync'       => 'Intet at synkronisere',
        'no_settings'           => 'Gem indstillingerne først.',

        'card_expiry'  => [
            'min'      => 'Forkert datoformat. Rigtigt: 11/2017',
        ]
    ],
];
