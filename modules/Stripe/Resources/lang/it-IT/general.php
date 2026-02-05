<?php

return [

    'name'                      => 'Striscia',
    'description'               => 'Ottieni fatture pagate e sincronizza gli altri pagamenti',
    'payment'                   => 'Pagamento',
    'sync'                      => 'Sincronizza',

    'form' => [
        'secret_key'            => 'Chiave segreta',
        'name'                  => 'Nome visualizzato',
        'order'                 => 'Ordine',
        'sync'                  => 'Sincronizza transazioni correnti',
    ],

    'card' => [
        'name'                  => 'Nome sulla carta',
        'number'                => 'Numero della carta',
        'expiry'                => 'Scadenza Carta',
        'cvc'                   => 'CVC carta',
        'confirm'               => 'Conferma',
    ],

    'success' => [
        'settings_saved'        => 'Impostazione salvata',
        'transactions_synced'   => 'Transazioni sincronizzate'
    ],

    'error' => [
        'nothing_to_sync'       => 'Niente da sincronizzare',
        'no_settings'           => 'Per favore, salva le impostazioni prima.',

        'card_expiry'  => [
            'min'      => 'Formato data errato. Corretto: 11 / 2017',
        ]
    ],
];
