<?php

/*
|--------------------------------------------------------------------------
| Tetapan Domain DBENA
|--------------------------------------------------------------------------
| Semua "nombor ajaib" prototaip dipusatkan di sini supaya boleh diuji dan
| dilaraskan tanpa mengubah kod logik. Rujuk PRD.md §7.1 & §10.
*/

return [
    // Margin untung bersih anggaran (prototaip: estProfitMargin = 0.18)
    'profit_margin' => (float) env('DBENA_PROFIT_MARGIN', 0.18),

    // Ambang status servis: >= 35% = Memuaskan (prototaip: good = pct >= 35)
    'service_status_threshold' => (float) env('DBENA_SERVICE_STATUS_THRESHOLD', 35),

    // Bulan akhir tahun fiskal — menggantikan `monthsLeft = 5` yang hardcoded.
    'fiscal_year_end_month' => (int) env('DBENA_FISCAL_YEAR_END_MONTH', 12),

    // Lebar piramid tier: 100 - (index * 16) peratus
    'tier_pyramid_step' => 16,

    // Koordinat SVG TrendChart (viewBox 1180x380)
    'chart' => [
        'viewbox_width' => 1180,
        'viewbox_height' => 380,
        'plot_width' => 1160,
        'plot_height' => 340,
        'offset' => 20,
    ],

    'otp' => [
        'ttl_minutes' => (int) env('DBENA_OTP_TTL_MINUTES', 5),
        'max_attempts' => (int) env('DBENA_OTP_MAX_ATTEMPTS', 3),
        'resend_cooldown' => (int) env('DBENA_OTP_RESEND_COOLDOWN', 60),
    ],

    'login' => [
        'max_attempts' => (int) env('DBENA_LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('DBENA_LOGIN_DECAY_MINUTES', 15),
    ],

    // Pengganda period (prototaip: periodConfig.mult — kini AKTIF, keputusan D3)
    'period_multipliers' => [
        'weekly' => 1.0,
        'monthly' => 4.33,
        'quarterly' => 13.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrasi Google Sheet
    |--------------------------------------------------------------------------
    | driver 'link'    — sheet dikongsi "anyone with the link can view".
    |                    Dibaca melalui endpoint eksport CSV Google. Tiada kunci
    |                    API diperlukan. Baca-sahaja.
    | driver 'service' — service account Google Cloud. Boleh baca sheet PERIBADI.
    |                    Perlu fail kunci JSON + sheet dikongsi dengan emel SA.
    */
    'sheets' => [
        'driver' => env('DBENA_SHEETS_DRIVER', 'link'),

        'service_account' => [
            /*
             * Kelayakan MESTI dibaca melalui config, bukan env() terus.
             * Selepas `php artisan config:cache` — yang dijalankan setiap deploy —
             * env() memulangkan null. Meletakkannya di sini memastikan nilai
             * dibakar ke dalam cache config dan kekal tersedia.
             */
            'credentials_base64' => env('GOOGLE_SERVICE_ACCOUNT_BASE64'),
            'credentials_path' => env('GOOGLE_SERVICE_ACCOUNT_JSON', storage_path('app/google/service-account.json')),
        ],

        // Kekerapan tarik automatik (minit). 0 = matikan penjadualan.
        'sync_interval_minutes' => (int) env('DBENA_SHEETS_SYNC_MINUTES', 15),

        // Had saiz muat turun CSV (bait) — perisai terhadap sheet tersalah pautan.
        'max_bytes' => (int) env('DBENA_SHEETS_MAX_BYTES', 5 * 1024 * 1024),

        'timeout_seconds' => (int) env('DBENA_SHEETS_TIMEOUT', 20),

        // Simpan log sync selama N hari.
        'log_retention_days' => (int) env('DBENA_SHEETS_LOG_DAYS', 60),
    ],

    'avatar' => [
        'disk' => 'public',
        'path' => 'avatars',
        'max_kb' => 2048,
    ],
];
