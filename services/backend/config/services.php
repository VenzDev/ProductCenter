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

    // Admin login via Microsoft Entra ID (OpenID Connect), see docs/design.md.
    'microsoft' => [
        'client_id' => env('AZURE_OPENID_CLIENT_ID'),
        'client_secret' => env('AZURE_OPENID_CLIENT_SECRET_VALUE'),
        'redirect' => env('AZURE_OPENID_REDIRECT_URI'),
        'tenant' => env('AZURE_OPENID_TENANT_ID'),
        // Email domain allowed to self-provision an Admin on first login (see
        // MicrosoftAuthController::provisionAdmin).
        'allowed_domain' => env('AZURE_OPENID_ALLOWED_DOMAIN'),
    ],

    // SQS event publishing (LocalStack locally, real SQS in AWS), see docs/design.md.
    // Add one entry per queue here (and a matching case in App\Services\Sqs\SqsQueue) as
    // new async events are introduced — the client config above is shared by all of them.
    'sqs' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'endpoint' => env('AWS_ENDPOINT'),
        'queues' => [
            'product_description_requested' => env('SQS_PRODUCT_DESCRIPTION_QUEUE', 'product-description-requested'),
        ],
    ],

];
