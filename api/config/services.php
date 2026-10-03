<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Traveler sign-in on the site (Socialite). Create an OAuth client "Web application" in Google Cloud with the
    // redirect URI {FRONTEND_URL}/auth/google/callback; the site does the redirect, Laravel exchanges the code.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/').'/auth/google/callback',
    ],

    // Local testing without Google: sign in as any traveler by email. Ignored unless APP_ENV=local.
    'dev_login' => (bool) env('DEV_LOGIN', false),

    // Tour route builder (App\Services\Maps\RouteBuilder). The public OSRM servers (project-osrm.org for cars,
    // FOSSGIS routing.openstreetmap.de for walking) are meant for light use: fine for editors drawing routes,
    // never for traffic from the site. For heavy use run an own OSRM on the Geofabrik Kyrgyzstan extract.
    'osrm' => [
        'driving_url' => env('OSRM_DRIVING_URL', 'https://router.project-osrm.org'),
        'foot_url' => env('OSRM_FOOT_URL', 'https://routing.openstreetmap.de/routed-foot'),
    ],

    'elevation' => [
        'url' => env('ELEVATION_API_URL', 'https://api.open-meteo.com/v1/elevation'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
