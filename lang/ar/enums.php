<?php

return [
    'service_direction' => ['receive' => 'استقبال', 'send' => 'إرسال', 'both' => 'استقبال وإرسال'],
    'currency_type' => ['fiat' => 'عملة تقليدية', 'crypto' => 'عملة رقمية'],
    'payment_account_type' => [
        'paypal' => 'PayPal', 'bank' => 'حساب بنكي', 'ewallet' => 'محفظة إلكترونية', 'crypto_wallet' => 'محفظة USDT / كريبتو',
        'cliq' => 'CliQ', 'instapay' => 'InstaPay', 'vodafone_cash' => 'Vodafone Cash', 'other' => 'أخرى',
    ],
    'payment_account_status' => ['active' => 'نشط', 'paused' => 'متوقف مؤقتًا', 'retired' => 'مُلغى'],
];
