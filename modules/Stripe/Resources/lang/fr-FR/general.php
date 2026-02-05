<?php

return [

    'name'                      => 'Stripe',
    'description'               => 'Obtenez paiement de vos factures et synchronisez les autres paiements',
    'payment'                   => 'Paiement',
    'sync'                      => 'Synchroniser',

    'form' => [
        'secret_key'            => 'Clé secrète',
        'name'                  => 'Nom d\'affichage',
        'order'                 => 'Commande',
        'sync'                  => 'Synchroniser les transactions en cours',
    ],

    'card' => [
        'name'                  => 'Nom du titulaire',
        'number'                => 'Numéro de carte',
        'expiry'                => 'Date d\'expiration',
        'cvc'                   => 'Code CVC',
        'confirm'               => 'Confirmer',
    ],

    'success' => [
        'settings_saved'        => 'Paramètres enregistrés',
        'transactions_synced'   => 'Transactions synchronisées'
    ],

    'error' => [
        'nothing_to_sync'       => 'Rien à synchroniser',
        'no_settings'           => 'Veuillez d\'abord enregistrer les paramètres.',

        'card_expiry'  => [
            'min'      => 'Format de date incorrect. Correct : 11 / 2017',
        ]
    ],
];
