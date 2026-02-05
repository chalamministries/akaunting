<?php

return [

    'name'                      => 'ストライプ',
    'description'               => '請求書を受け取り、他の支払いを同期する',
    'payment'                   => 'ペイメント',
    'sync'                      => '同期',

    'form' => [
        'secret_key'            => 'シークレットキー',
        'name'                  => '表示名',
        'order'                 => 'オーダー',
        'sync'                  => '現在のトランザクションを同期',
    ],

    'card' => [
        'name'                  => 'カードの名前',
        'number'                => 'カード番号',
        'expiry'                => 'カード有効期限',
        'cvc'                   => 'カードCVC',
        'confirm'               => '確認',
    ],

    'success' => [
        'settings_saved'        => '設定を保存しました',
        'transactions_synced'   => '同期されたトランザクション'
    ],

    'error' => [
        'nothing_to_sync'       => '同期するものはありません',
        'no_settings'           => '最初に設定を保存してください。',

        'card_expiry'  => [
            'min'      => '間違った日付形式。正：11/2017',
        ]
    ],
];
