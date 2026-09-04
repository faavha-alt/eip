<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain email kampus yang diizinkan login
    |--------------------------------------------------------------------------
    |
    | Login Google OIDC hanya diterima utk akun dgn salah satu domain ini
    | (docs/03-autentikasi-sso.md). Data pegawai nyata FMIPA UNS memakai
    | LEBIH DARI SATU domain resmi sekaligus (mayoritas @staff.uns.ac.id,
    | sisanya @mipa.uns.ac.id) — jadi ini daftar, bukan satu domain tunggal.
    | Isi tanpa "@", pisah koma, mis. "staff.uns.ac.id,mipa.uns.ac.id".
    |
    */
    'allowed_email_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ALLOWED_EMAIL_DOMAINS', ''))
    ))),

];
