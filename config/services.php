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

    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY'),
        'public' => env('STRIPE_PUBLIC_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
        'subscription_amount' => env('STRIPE_SUBSCRIPTION_AMOUNT', 9.99),
        'success_url' => env('STRIPE_SUCCESS_URL', env('APP_URL', 'http://localhost') . '/profile?payment=success'),
        'cancel_url' => env('STRIPE_CANCEL_URL', env('APP_URL', 'http://localhost') . '/profile?payment=cancelled'),
        'khqr_enabled' => env('STRIPE_KHQR_ENABLED', false),
    ],

    'aba_payway' => [
        'merchant_id' => env('ABA_PAYWAY_MERCHANT_ID'),
        'api_key' => env('ABA_PAYWAY_API_KEY'),
        'secret_key' => env('ABA_PAYWAY_SECRET_KEY'),
        'public_key' => env('ABA_PAYWAY_PUBLIC_KEY'),
        'private_key' => env('ABA_PAYWAY_PRIVATE_KEY'),
        'sandbox' => env('ABA_PAYWAY_SANDBOX', true),
        'sandbox_base_url' => env('ABA_PAYWAY_SANDBOX_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
        'production_base_url' => env('ABA_PAYWAY_PRODUCTION_BASE_URL', 'https://checkout.payway.com.kh'),
        'success_url' => env('ABA_PAYWAY_SUCCESS_URL', env('APP_URL', 'http://localhost') . '/profile?payment=success&provider=payway'),
        'cancel_url' => env('ABA_PAYWAY_CANCEL_URL', env('APP_URL', 'http://localhost') . '/profile?payment=cancelled&provider=payway'),
        'return_url' => env('ABA_PAYWAY_RETURN_URL', env('APP_URL', 'http://localhost') . '/api/v1/payway/callback'),
        'view_type' => env('ABA_PAYWAY_VIEW_TYPE', 'hosted_view'),
        'qr_image_template' => env('ABA_PAYWAY_QR_IMAGE_TEMPLATE', 'template3_color'),
        'qr_lifetime_minutes' => (int) env('ABA_PAYWAY_QR_LIFETIME_MINUTES', 30),
        'merchant_name' => env('ABA_PAYWAY_MERCHANT_NAME', 'e-Libra Platform'),
        'default_currency' => env('ABA_PAYWAY_DEFAULT_CURRENCY', 'USD'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL', 'http://localhost') . '/login'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'enabled' => env('TELEGRAM_ALERTS_ENABLED', true),
    ],

];
