<?php

return [
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),

            /*
             * Had masa WAJIB. Kod OTP dihantar semasa permintaan log masuk,
             * jadi sambungan SMTP yang tergantung akan menahan permintaan itu
             * sehingga pelayan web berputus asa — pengguna melihat "504
             * Gateway time-out" dan tiada petunjuk bahawa emel puncanya.
             *
             * Sepuluh saat cukup untuk Gmail pada hari biasa, dan cukup pantas
             * untuk gagal dengan mesej yang berguna apabila port disekat.
             */
            'timeout' => (int) env('MAIL_TIMEOUT', 10),
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
        'array' => ['transport' => 'array'],
        'failover' => ['transport' => 'failover', 'mailers' => ['smtp', 'log']],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'dbenareport@gmail.com'),
        'name' => env('MAIL_FROM_NAME', 'DBENA Dashboard'),
    ],
];
