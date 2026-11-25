<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blind index
    |--------------------------------------------------------------------------
    |
    | Kunci HMAC untuk kolom pencarian (NIK, No KK, No HP). HARUS sama dengan
    | SIDULUR_INDEX_KEY di aplikasi bale-front.
    |
    */

    'index_key' => env('SIDULUR_INDEX_KEY', env('APP_KEY')),

    'ticket_expiry_days' => (int) env('SIDULUR_TICKET_EXPIRY_DAYS', 30),

    'document_link_days' => (int) env('SIDULUR_DOCUMENT_LINK_DAYS', 30),

    'storage' => [
        'disk' => env('SIDULUR_DOCUMENT_DISK', 'sidulur'),
        'bucket' => env('SIDULUR_DOCUMENT_BUCKET'),
        'key' => env('SIDULUR_ACCESS_KEY_ID'),
        'secret' => env('SIDULUR_SECRET_ACCESS_KEY'),
    ],

    'whatsapp' => [
        'purpose' => env('SIDULUR_WA_PURPOSE', 'sidulur'),
        'center' => env('SIDULUR_WHATSAPP'),
    ],

];
