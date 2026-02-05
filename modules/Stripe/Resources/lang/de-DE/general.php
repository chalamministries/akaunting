<?php

return [

    'name'                      => 'Stripe',
    'description'               => 'Bezahlte Rechnungen erhalten und andere Zahlungen synchronisieren',
    'payment'                   => 'Zahlung',
    'sync'                      => 'Sync',

    'form' => [
        'secret_key'            => 'Geheimer Schlüssel',
        'name'                  => 'Anzeigename',
        'order'                 => 'Bestellung',
        'sync'                  => 'Aktuelle Transaktionen synchronisieren',
    ],

    'card' => [
        'name'                  => 'Name des Karteninhabers',
        'number'                => 'Kartennummer',
        'expiry'                => 'Ablauf der Karte',
        'cvc'                   => 'KartenCVC',
        'confirm'               => 'Bestätigen',
    ],

    'success' => [
        'settings_saved'        => 'Einstellung gespeichert',
        'transactions_synced'   => 'Transaktionen synchronisiert'
    ],

    'error' => [
        'nothing_to_sync'       => 'Nichts zu synchronisieren',
        'no_settings'           => 'Bitte speichern Sie zuerst die Einstellungen.',

        'card_expiry'  => [
            'min'      => 'Falsches Datumsformat. Richtig: 11 / 2017',
        ]
    ],
];
