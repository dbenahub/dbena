<?php

return [
    'title' => 'Strategic Planning & KPI Alignment',
    'subtitle' => 'Dashboard Summary',
    'vision' => 'VISI',
    'view_only' => 'Paparan sahaja — dikemas kini melalui Google Sheet',

    'col' => [
        'kra' => 'KRA',
        'kpi' => 'KPI',
        'target' => 'TARGET',
        'tactics' => 'TACTICS',
        'initiatives' => 'INITIATIVES',
        'timeline' => 'TIMELINE',
        'pic' => 'PIC/CI',
    ],

    'pic_prefix' => 'HOD',
    'synced_at' => 'Disegerak :time',

    'empty_title' => 'Strategic Planning belum disambung',
    'empty_body' => 'Admin perlu menyambung tab strategic planning servis ini dalam Panel Admin sebelum ia muncul di sini.',
    'empty_body_admin' => 'Sambungkan tab strategic planning servis ini di Panel Admin → Google Sheet.',

    'admin' => [
        'title' => 'Strategic Planning & KPI Alignment',
        'note' => 'Satu fail Google Sheet, satu tab setiap servis. Tampal pautan fail sekali, kemudian namakan tab bagi setiap servis.',
        'url' => 'Pautan fail Google Sheet',
        'tab' => 'Nama tab',
        'tab_placeholder' => 'contoh: RENOVATION',
        'save' => 'Simpan',
        'sync' => 'Sync',
        'sync_all' => 'Sync semua servis',
        'saved' => 'Tetapan strategic planning disimpan.',
        'never' => 'Belum pernah disegerak',
        'no_tab' => 'Nama tab belum ditetapkan untuk :service.',
    ],

    'sync' => [
        'done' => ':rows baris dan :tiles petak disegerak.',
        'no_service' => 'Integrasi ini tidak terikat kepada mana-mana servis.',
        'read_failed' => 'Gagal membaca sheet: :message',
        'no_table' => 'Tiada baris tajuk jumpa. Pastikan tab ini mempunyai satu baris yang mengandungi KRA dan KPI sebagai tajuk lajur.',
        'no_rows' => 'Baris tajuk jumpa tetapi tiada baris KRA di bawahnya.',
    ],
];
