<?php

return [

    'name'                      => 'Stripe',
    'description'               => 'Merrni pagesa të faturave dhe sinkronizoni pagesat e tjera',
    'payment'                   => 'Pagesa',
    'sync'                      => 'Sinkronizim',

    'form' => [
        'secret_key'            => 'Çelësi Sekret',
        'name'                  => 'Shfaq Emrin',
        'order'                 => 'Porosi',
        'sync'                  => 'Sinkronizo Transaksionet Aktuale',
    ],

    'card' => [
        'name'                  => 'Emri ne Karte',
        'number'                => 'Numri i Kartes',
        'expiry'                => 'Skadimi i Kartës',
        'cvc'                   => 'Karta CVC',
        'confirm'               => 'Konfirmo',
    ],

    'success' => [
        'settings_saved'        => 'Konfigurimi u ruajt',
        'transactions_synced'   => 'Transaksionet u sinkronizuan'
    ],

    'error' => [
        'nothing_to_sync'       => 'Asgjë për të sinkronizuar',
        'no_settings'           => 'Ju lutem, ruani rregullimet e para.',

        'card_expiry'  => [
            'min'      => 'Formati i datës është gabim. I saktë: 11 / 2017',
        ]
    ],
];
