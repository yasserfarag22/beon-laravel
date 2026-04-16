<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Beon API Key
    |--------------------------------------------------------------------------
    | Your Beon account API key (beon-token header).
    | Get it from: https://app.beon.chat → Settings → API
    */
    'api_key' => env('BEON_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Beon API Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('BEON_BASE_URL', 'https://v3.api.beon.chat'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('BEON_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Channel ID
    |--------------------------------------------------------------------------
    | The default WhatsApp channel ID to use for and session messages.
    */
    'default_channel_id' => env('BEON_CHANNEL_ID'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    | Optional secret to verify incoming webhook payloads.
    */
    'webhook_secret' => env('BEON_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Predefined Templates
    |--------------------------------------------------------------------------
    | Define your templates here to simplify sending them via the fluent API.
    | Example: Beon::to($to)->template('welcome')->send();
    */
    'templates' => [
        // 'welcome' => [
        //     'id' => 12345,
        //     'content' => 'Welcome {{1}} to our service!',
        //     'language' => 'en',
        // ],
    ],

];

