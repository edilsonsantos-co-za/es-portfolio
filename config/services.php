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
    ],

    // Logto (OpenID Connect) single sign-on for the admin panel. Disabled unless app id and secret are set.
    // LOGTO_INTERNAL_ENDPOINT is an optional server-to-server URL (e.g. a Docker service name) for token calls.
    'logto' => [
        'endpoint' => env('LOGTO_ENDPOINT'),
        'internal_endpoint' => env('LOGTO_INTERNAL_ENDPOINT'),
        'app_id' => env('LOGTO_APP_ID'),
        'app_secret' => env('LOGTO_APP_SECRET'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
