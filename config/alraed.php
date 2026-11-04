<?php

return [
    // Public contact details shown on the website.
    'whatsapp' => env('ALRAED_WHATSAPP', '970597775080'),
    'whatsapp_display' => env('ALRAED_WHATSAPP_DISPLAY', '+970 597 775 080'),
    'email' => env('ALRAED_EMAIL'),

    // Staff must enrol an authenticator app (TOTP) before using the panel.
    'require_mfa' => (bool) env('ALRAED_REQUIRE_MFA', true),

    'transfer_reference' => [
        'prefix' => 'TR',
        'date_format' => 'Ymd',   // TR20261103-0001
        'sequence_digits' => 4,
    ],
];
