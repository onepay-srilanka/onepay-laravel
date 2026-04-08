<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OnePay API Base URL (server-side only)
    |--------------------------------------------------------------------------
    |
    | Fixed production endpoint — not read from .env and must never be taken
    | from browser, mobile, or any HTTP request. To point at a different
    | host (e.g. future sandbox), change this value only in this published
    | config file on the server.
    |
    */
    'base_url' => 'https://api.oneapay.lk/v3',

    /*
    |--------------------------------------------------------------------------
    | Application ID
    |--------------------------------------------------------------------------
    |
    | Your OnePay application identifier, issued from the merchant dashboard.
    |
    */
    'app_id' => env('ONEPAY_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | Application Token
    |--------------------------------------------------------------------------
    |
    | Bearer-style token sent in the Authorization header for every API call.
    |
    */
    'app_token' => env('ONEPAY_APP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Hash Salt
    |--------------------------------------------------------------------------
    |
    | Secret salt used when generating the SHA-256 request hash.
    | NEVER expose this value in client-side code or version control.
    |
    */
    'hash_salt' => env('ONEPAY_HASH_SALT'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency (app use)
    |--------------------------------------------------------------------------
    |
    | Not merged automatically into API calls — you must pass `currency` into
    | createCheckoutLink(). Use config('onepay.currency') when building payloads.
    |
    */
    'currency' => env('ONEPAY_CURRENCY', 'LKR'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('ONEPAY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of times to retry a failed request and the milliseconds to wait
    | between each attempt.
    |
    */
    'retry' => [
        'times' => env('ONEPAY_RETRY_TIMES', 3),
        'sleep_ms' => env('ONEPAY_RETRY_SLEEP_MS', 500),
    ],

];
