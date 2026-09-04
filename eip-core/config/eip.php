<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain email kampus yang diizinkan login
    |--------------------------------------------------------------------------
    |
    | Login Google OIDC hanya diterima utk akun dgn domain email ini
    | (docs/03-autentikasi-sso.md). Isi tanpa "@", mis. "mipa.uns.ac.id".
    |
    */
    'allowed_email_domain' => env('ALLOWED_EMAIL_DOMAIN'),

];
