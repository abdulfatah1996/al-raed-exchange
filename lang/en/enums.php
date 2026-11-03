<?php

return [
    'service_direction' => ['receive' => 'Receive', 'send' => 'Send', 'both' => 'Receive & send'],
    'currency_type' => ['fiat' => 'Fiat', 'crypto' => 'Crypto'],
    'payment_account_type' => [
        'paypal' => 'PayPal', 'bank' => 'Bank account', 'ewallet' => 'E-wallet', 'crypto_wallet' => 'USDT / crypto wallet',
        'cliq' => 'CliQ', 'instapay' => 'InstaPay', 'vodafone_cash' => 'Vodafone Cash', 'other' => 'Other',
    ],
    'payment_account_status' => ['active' => 'Active', 'paused' => 'Paused', 'retired' => 'Retired'],
];
