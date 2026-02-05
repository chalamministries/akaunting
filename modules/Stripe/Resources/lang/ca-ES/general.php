<?php

return [

    'name'                      => 'Stripe',
    'description'               => 'Obté les factures pagades i sincronitza els altres pagaments',
    'payment'                   => 'Pagament',
    'sync'                      => 'Sincronitza',

    'form' => [
        'secret_key'            => 'Clau privada',
        'name'                  => 'Mostra el nom',
        'order'                 => 'Ordena',
        'sync'                  => 'Sincronitza les transaccions actuals',
    ],

    'card' => [
        'name'                  => 'Nom a la targeta',
        'number'                => 'Número de targeta',
        'expiry'                => 'Data final de validesa de la targeta',
        'cvc'                   => 'CVV de la targeta',
        'confirm'               => 'Confirma',
    ],

    'success' => [
        'settings_saved'        => 'S\'ha desat la configuració',
        'transactions_synced'   => 'S\'han sincronitzat les transaccions'
    ],

    'error' => [
        'nothing_to_sync'       => 'No hi ha res per sincronitzar',
        'no_settings'           => 'Si us plau, desa primer la configuració.',

        'card_expiry'  => [
            'min'      => 'Format de data incorrecte. El correcte és: 11 / 2020',
        ]
    ],
];
