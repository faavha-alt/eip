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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // OIDC Google Workspace — SSO login (docs/03-autentikasi-sso.md).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // API baca-saja wa-blast (wa-blast = acuan terakhir nomor HP pegawai).
    // Kontrak: /ai/projects/wa-blast/docs/api-eip-inbound.md. Token dibuat
    // & dikelola di wa-blast sendiri (/settings/eip), bukan token EIP.
    'wablast' => [
        'base_url' => env('WABLAST_BASE_URL'),
        'inbound_token' => env('WABLAST_INBOUND_TOKEN'),
        // wa-blast & EIP Core sehosting (203.6.149.150) — hairpin NAT: server
        // gagal panggil domain publiknya sendiri lewat IP publik (timeout).
        // Resolve paksa ke loopback (SNI/Host tetap benar). Kosongkan bila
        // wa-blast pindah ke server lain. Pola sama spt EipClient di wa-blast.
        'local_ip' => env('WABLAST_LOCAL_IP', '127.0.0.1'),
    ],

];
