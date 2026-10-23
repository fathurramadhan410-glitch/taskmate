<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Layanan Pihak Ketiga
    |--------------------------------------------------------------------------
    |
    | Konfigurasi Google OAuth untuk fitur "Masuk dengan Google" (Laravel
    | Socialite). Ambil Client ID & Secret dari Google Cloud Console:
    | https://console.cloud.google.com/apis/credentials
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];
