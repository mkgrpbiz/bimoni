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

    'line' => [
        'channel_id'           => env('LINE_CHANNEL_ID'),
        'channel_secret'       => env('LINE_CHANNEL_SECRET'),
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'liff_id'              => env('LIFF_ID'),
        'official_account_id'  => env('LINE_OFFICIAL_ACCOUNT_ID'),
    ],

    'ai_office' => [
        // Shared secret AI OFFICE sends as a Bearer token when calling
        // api/ai-office/* (see App\Http\Middleware\VerifyAiOfficeToken).
        'token' => env('AI_OFFICE_API_TOKEN'),
    ],

    // BIMONI管理君: 代理店・社内スタッフとのやりとり用の新規LINEチャンネル（会員登録用の'line'とは別チャンネル）
    'kanrikun' => [
        'channel_id' => env('KANRIKUN_CHANNEL_ID'),
        'channel_secret' => env('KANRIKUN_CHANNEL_SECRET'),
        'channel_access_token' => env('KANRIKUN_CHANNEL_ACCESS_TOKEN'),

        // BIMONI → AI OFFICE方向（管理君メッセージのリレー）。api/ai-office/*用のtokenとは別物。
        'relay_url' => env('KANRIKUN_RELAY_URL'),
        'relay_token' => env('KANRIKUN_RELAY_TOKEN'),
    ],

];
