<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | MTN Mobile Money API
    |--------------------------------------------------------------------------
    */
    'mtn_momo' => [
        'base_url'         => env('MTN_API_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'environment'      => env('MTN_ENVIRONMENT', 'sandbox'),
        'subscription_key' => env('MTN_SUBSCRIPTION_KEY'),
        'api_user'         => env('MTN_API_USER'),
        'api_key'          => env('MTN_API_KEY'),
        'callback_url'     => env('MTN_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Airtel Money API
    |--------------------------------------------------------------------------
    */
    'airtel_money' => [
        'base_url'      => env('AIRTEL_API_URL', 'https://openapiuat.airtel.africa'),
        'client_id'     => env('AIRTEL_CLIENT_ID'),
        'client_secret' => env('AIRTEL_CLIENT_SECRET'),
        'callback_url'  => env('AIRTEL_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudinary — Stockage des images (production Railway)
    |--------------------------------------------------------------------------
    */
    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key'    => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Peexit — Collect API (Mobile Money : MTN, Airtel, Orange...)
    | Doc : https://peex-api-docs.peexit.com/collect/collections
    |--------------------------------------------------------------------------
    */
    'peexit' => [
        'base_url'         => env('PEEX_BASE_URL', 'https://dev-backend.peexit.com/api/v1'),
        'secret_key'       => env('PEEX_SECRET_KEY'),
        // Credentials Basic Auth du webhook Peexit → votre serveur
        'callback_user'    => env('PEEX_CALLBACK_USER', 'peex'),
        'callback_password'=> env('PEEX_CALLBACK_PASSWORD', 'peex_callback'),
    ],

];
