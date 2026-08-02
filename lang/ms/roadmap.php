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
        'not_shared' => 'Google menolak permintaan. Kalendar ini belum dikongsi dengan service account.',
        'not_shared_service' => 'Google menolak permintaan. Buka calendar.google.com → hover nama kalendar → tiga titik → Settings and sharing → Share with specific people → Add people → :email → pilih "See all event details" → Send. Google akan beri amaran emel itu kelihatan pelik; teruskan sahaja. Kalendar kekal Private.',
        'id_from_url' => 'Calendar ID diambil daripada pautan yang ditampal: :id',
        'api_disabled' => 'Google Calendar API belum diaktifkan dalam projek Google Cloud anda. Ini BUKAN masalah perkongsian — kalendar anda mungkin sudah dikongsi dengan betul. Buka pautan ini dan tekan ENABLE, tunggu seminit, kemudian uji semula: :url',
        'google_said' => 'Google berkata: ":message"',
        'probe_ok' => 'Calendar API aktif dan robot boleh log masuk. Kalendar yang robot nampak sekarang: :list',
        'probe_none' => '(tiada kalendar langsung — perkongsian belum sampai)',
        'probe_failed' => 'Robot tidak dapat bercakap dengan Calendar API langsung (:message).',
        'probe_network' => 'Robot tidak dapat menghubungi Google (:message).',
        'bad_id' => 'Itu bukan Calendar ID. Salin dari Settings and sharing → Integrate calendar → Calendar ID (contoh: nama@gmail.com atau c_xxxx@group.calendar.google.com).',
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
