<?php

return [
    'title' => 'ROADMAP TAHUNAN SERVIS',
    'subtitle' => 'Peta perjalanan servis sepanjang tahun',
    'journey' => 'PERJALANAN 12 BULAN',
    'journey_note' => 'Menuju Perkhidmatan Terbaik',
    'service_col' => 'SERVIS',

    'status' => [
        'none' => 'Tiada aktiviti',
        'active_all_year' => 'Aktif Sepanjang Tahun',
        'campaign' => 'Kempen Aktif',
        'paused' => 'Pause / Campaign Stop',
        'resumed' => 'Sambung Semula',
    ],

    'legend' => 'PETUNJUK',
    'legend_note' => [
        'active_all_year' => 'Servis aktif sepanjang 12 bulan',
        'campaign' => 'Tempoh kempen berlangsung',
        'paused' => 'Kempen dihentikan sementara',
        'resumed' => 'Kempen disambung semula',
        'none' => 'Tiada aktiviti dirancang',
    ],

    'summary' => 'RINGKASAN STRATEGI',
    'summary_empty' => 'Ringkasan strategi belum ditulis.',

    'view' => [
        'label' => 'Paparan',
        'grid' => 'Grid Tahunan',
        'quarter' => 'Suku Tahun',
        'list' => 'Senarai Bulan',
    ],

    'target' => [
        'monthly' => 'Sasaran bulanan',
        'annual' => 'Sasaran tahunan',
        'company' => 'Sasaran tahunan syarikat',
        'active_months' => ':count bulan aktif',
        'note' => 'Sasaran tahunan dikira daripada bulan aktif sahaja — bulan yang dijeda tidak membawa sasaran.',
    ],

    'calendar' => [
        'title' => 'Google Calendar',
        'events' => ':count acara',
        'events_month' => ':count acara pada :month',
        'none' => 'Tiada acara pada bulan ini.',
        'not_connected' => 'Google Calendar belum disambung.',
        'id' => 'Calendar ID',
        'id_hint' => 'Google Calendar → tetapan kalendar → Integrate calendar → Calendar ID',
        'share_hint' => 'Kongsi kalendar dengan :email (See all event details) sebelum menguji.',
        'test' => 'Uji sambungan',
        'ok' => ':count acara dibaca untuk tahun :year.',
        'untitled' => 'Acara tanpa tajuk',
        'failed' => 'Kalendar tidak dapat dibaca: :message',
        'all_day' => 'Sepanjang hari',
    ],

    'empty_title' => 'Roadmap :year belum ditetapkan',
    'empty_body' => 'Admin perlu menetapkan status setiap servis dalam Panel Admin.',
    'empty_body_admin' => 'Tetapkan status setiap servis di Panel Admin → Roadmap Tahunan.',

    'admin' => [
        'title' => 'Roadmap Tahunan Servis',
        'note' => 'Klik mana-mana sel untuk kitar statusnya. Perubahan disimpan serta-merta.',
        'cycle_hint' => 'Klik: Tiada → Aktif Sepanjang Tahun → Kempen Aktif → Pause → Sambung Semula',
        'text' => 'Teks papan',
        'heading' => 'Tajuk',
        'sub' => 'Subtajuk',
        'summary_line' => 'Ringkasan strategi (satu baris setiap poin)',
        'save' => 'Simpan teks',
        'saved' => 'Roadmap disimpan.',
        'fill_row' => 'Isi baris',
        'clear_row' => 'Kosongkan baris',
        'copy_year' => 'Salin dari :year',
        'copied' => 'Disalin daripada :year.',
        'nothing_to_copy' => 'Tiada roadmap :year untuk disalin.',
    ],
];
