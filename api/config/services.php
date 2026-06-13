<?php

return [

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ── Eskiz.uz SMS ─────────────────────────────────────────
    'eskiz' => [
        'email'    => env('ESKIZ_EMAIL'),
        'password' => env('ESKIZ_PASSWORD'),
        'from'     => env('ESKIZ_FROM', '4546'),
    ],

    // ── Payme ─────────────────────────────────────────────────
    'payme' => [
        'merchant_id'       => env('PAYME_MERCHANT_ID'),
        'secret_key'        => env('PAYME_SECRET_KEY'),
        'test_secret_key'   => env('PAYME_TEST_SECRET_KEY'),
        'url'               => env('PAYME_URL', 'https://checkout.paycom.uz'),
        'test_url'          => env('PAYME_TEST_URL', 'https://test.paycom.uz'),
        'environment'       => env('PAYME_ENVIRONMENT', 'test'),
    ],

    // ── Click ─────────────────────────────────────────────────
    'click' => [
        'service_id'       => env('CLICK_SERVICE_ID'),
        'merchant_id'      => env('CLICK_MERCHANT_ID'),
        'secret_key'       => env('CLICK_SECRET_KEY'),
        'merchant_user_id' => env('CLICK_MERCHANT_USER_ID'),
    ],

    // ── Google Maps ───────────────────────────────────────────
    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];
