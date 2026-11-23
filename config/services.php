<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // PayMock (mock payment gateway ใน paymentsgateway/) — ว่าง PAYMOCK_URL = ไม่ใช้ gateway (ใช้ปุ่มจำลองเดิม)
    'paymock' => [
        'url'            => env('PAYMOCK_URL'),
        'api_key'        => env('PAYMOCK_API_KEY'),
        'webhook_secret' => env('PAYMOCK_WEBHOOK_SECRET'),
        'callback_url'   => env('PAYMOCK_CALLBACK_URL'),
        'public_url'     => env('PAYMOCK_PUBLIC_URL', 'http://localhost:8081'),   // ที่อยู่ที่เบราว์เซอร์ของผู้ใช้เข้าถึง PayMock (QR, หน้ากรอกบัตร)
        'tolerance'      => (int) env('PAYMOCK_WEBHOOK_TOLERANCE', 300),
    ],

];
