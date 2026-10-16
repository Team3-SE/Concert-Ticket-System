<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Authentication
    |--------------------------------------------------------------------------
    | Bearer token ที่ Concert system ต้องส่งมาใน Authorization header
    */
    'api_key' => env('PAYMOCK_API_KEY', 'pm_test_key_concert_system_2024'),

    /*
    |--------------------------------------------------------------------------
    | Webhook HMAC Secret
    |--------------------------------------------------------------------------
    | ใช้เซ็น X-Signature บน webhook payload
    */
    'webhook_secret' => env('PAYMOCK_WEBHOOK_SECRET', 'whsec_concert_super_secret_2024'),

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Password
    |--------------------------------------------------------------------------
    */
    'admin_password' => env('PAYMOCK_ADMIN_PASSWORD', 'admin1234'),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Expiry (seconds)
    |--------------------------------------------------------------------------
    */
    'default_expires_in' => (int) env('PAYMOCK_DEFAULT_EXPIRES_IN', 600),

    /*
    |--------------------------------------------------------------------------
    | Magic Card Numbers
    |--------------------------------------------------------------------------
    | map card number (digits only) => [status, failure_code|null]
    */
    'card_rules' => [
        '4242424242424242' => ['COMPLETED', null],
        '4000000000000002' => ['FAILED',    'card_declined'],
        '4000000000009995' => ['FAILED',    'insufficient_funds'],
        '4000000000000069' => ['FAILED',    'expired_card'],
        '4000000000000127' => ['FAILED',    'incorrect_cvc'],
        '4000000000000119' => ['FAILED',    'processing_error'],
        '4100000000000019' => ['FAILED',    'fraud_suspected'],
        '4000000000000341' => ['COMPLETED', null],
    ],
];
