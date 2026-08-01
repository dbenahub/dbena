<?php

return [
    'page_title' => 'Master List of Project',
    'page_subtitle' => 'Senarai penuh projek mengikut kategori servis',
    'nav' => 'Projek',
    'all' => 'Semua Projek',

    'search' => 'Cari projek…',
    'all_status' => 'Semua Status (:count)',
    'filter' => 'Tapis',
    'export' => 'Eksport',
    'view_sheet' => 'Lihat Google Sheet',
    'sync_now' => 'Sync Sekarang',
    'admin_only' => 'Admin sahaja',

    'tile' => [
        'total' => 'Jumlah Projek',
        'total_note' => 'Semua kategori',
        'turned_down' => 'Turned Down',
        'closed' => 'Projek Ditutup',
        'of_total' => ':pct% daripada jumlah',
    ],

    'col' => [
        'code' => 'Kod Projek',
        'date' => 'Tarikh',
        'client' => 'Nama Klien',
        'pic' => 'PIC Sales',
        'service' => 'Jenis Projek',
        'phone' => 'Telefon / Whatsapp',
        'address' => 'Alamat',
        'email' => 'Emel',
        'contract' => 'Jumlah Kontrak',
        'vo' => 'Variation Order (VO)',
        'status' => 'Status',
    ],

    'field' => [
        'code' => 'Kod Projek',
        'client_name' => 'Nama Klien',
    ],

    'status' => [
        'quotation' => 'Quotation',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'closed' => 'Closed',
    ],

    'showing' => 'Memaparkan :from hingga :to daripada :total rekod',
    'per_page' => 'Baris per halaman',
    'empty' => 'Tiada projek dijumpai.',
    'empty_hint' => 'Data projek diisi dalam Google Sheet, kemudian disegerakkan oleh Admin.',
    'no_sheet' => 'Google Sheet projek belum disambungkan. Admin boleh menyambungkannya di Admin Panel.',

    'sync' => [
        'read_failed' => 'Sheet tidak dapat dibaca: :message',
        'missing_column' => 'Lajur wajib belum dipetakan: :field',
        'done' => ':written projek disegerakkan, :skipped baris dilangkau.',
        'nothing' => 'Tiada baris projek yang sah dijumpai. Semak baris tajuk dan pemetaan lajur.',
        'unknown_services' => 'Jenis projek yang tidak dikenali dilangkau: :names',
    ],

    'source_note' => 'Data diisi dalam Google Sheet dan dipaparkan di sini selepas sync. Dashboard ini paparan sahaja.',
];
