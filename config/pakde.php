<?php
    return [

        /*
        |--------------------------------------------------------------------------
        | Create JOB (JNE Online Booking) JNE
        |--------------------------------------------------------------------------
        |
        | Ketika create order dan kirim malalui JNE, sistem akan secara otomatis
        | generate job number, parameter dibawah ini adalah untuk endpoint api generate jobnya
        */

        'jne_gentiket_url' => env('JNE_GENTIKET_URL', ''),

        /*
        |--------------------------------------------------------------------------
        | Create JOB (JNE Online Booking) JNE
        |--------------------------------------------------------------------------
        |
        | Ketika create order dan kirim malalui JNE, sistem akan secara otomatis
        | generate job number, parameter dibawah ini adalah untuk endpoint api generate jobnya
        */

        'jne_api_username' => env('JNE_API_USERNAME', ''),

        /*
        |--------------------------------------------------------------------------
        | Create JOB (JNE Online Booking) JNE
        |--------------------------------------------------------------------------
        |
        | Ketika create order dan kirim malalui JNE, sistem akan secara otomatis
        | generate job number, parameter dibawah ini adalah untuk endpoint api generate jobnya
        */

        'jne_api_key' => env('JNE_API_KEY', ''),
    ];