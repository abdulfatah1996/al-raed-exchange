<?php

return [
    // Staff must enrol an authenticator app (TOTP) before using the panel.
    'require_mfa' => (bool) env('ALRAED_REQUIRE_MFA', true),

    'transfer_reference' => [
        'prefix' => 'TR',
        'date_format' => 'Ymd',   // TR20261103-0001
        'sequence_digits' => 4,
    ],
];
