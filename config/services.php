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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'kep' => [
        'endpoint' => env('KEP_API_URL', 'https://kanmoemployeeportal.com/api/kep'),
        'timeout' => (int) env('KEP_API_TIMEOUT', 90),
        'sync_password' => (bool) env('KEP_SYNC_PASSWORD', false),
    ],

    'n8n' => [
        'chat_enabled' => env('N8N_CHAT_ENABLED', true),
        'chat_webhook_url' => env('N8N_CHAT_WEBHOOK_URL'),
        'chat_title' => env('N8N_CHAT_TITLE', 'KMS Assistant'),
        'chat_subtitle' => env('N8N_CHAT_SUBTITLE', 'Ask anything about SOP and KMS.'),
        'chat_welcome_message' => env('N8N_CHAT_WELCOME_MESSAGE', 'Hi! Need help with KMS today?'),
    ],

];
