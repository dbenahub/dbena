<?php

return [
    'title' => 'Peta Perjalanan Sales',
    'subtitle' => 'Dari Lead hingga Sales Collection',
    'start' => 'MULA',
    'goal' => 'SASARAN',
    'week' => 'minggu',
    'target' => 'Sasaran',
    'gap' => 'Kurang',
    'blocked_by' => 'Tersekat oleh :stage',
    'blocked_note' => 'Peringkat ini akan pulih sendiri sebaik sahaja :stage dibetulkan. Jangan kejar ia berasingan.',

    'stage' => [
        'lead' => 'LEAD',
        'site_visit' => 'SITE VISIT',
        'quotation' => 'QUOTATION',
        'sales' => 'SALES & COLLECTION',
    ],

    'amount' => [
        'quotation' => 'Nilai quotation',
        'sales' => 'Kutipan (deposit)',
    ],

    'cause_title' => [
        'lead' => 'Tiada lead masuk',
        'site_visit' => 'Site visit tidak mencukupi',
        'quotation' => 'Quotation tak dapat dikeluarkan',
        'sales' => 'Tiada potensi sales',
    ],

    'cause' => [
        'lead' => 'Aktiviti pemasaran tidak dijalankan atau tidak konsisten.',
        'site_visit' => 'Tiada lead masuk, lead tidak berkualiti, atau tiada follow up.',
        'quotation' => 'Site visit tidak mencukupi, maklumat tidak lengkap, atau keperluan pelanggan tidak jelas.',
        'sales' => 'Quotation tiada atau terlalu sedikit, pelanggan tidak closing, atau follow up lemah.',
    ],

    // Kesimpulan di atas peta
    'healthy_title' => 'Perjalanan lancar',
    'healthy_body' => 'Setiap peringkat mencapai sasaran. Kekalkan rentak ini.',

    'break_title' => 'Jalan terputus di :stage',
    'break_body' => 'Sasaran jualan dan kutipan syarikat bergantung pada peringkat ini. Selagi :stage tidak dibetulkan, :count peringkat di hilirnya tidak akan capai sasaran walau apa pun usaha di sana.',
    'break_body_single' => 'Sasaran jualan dan kutipan syarikat bergantung pada peringkat ini. Betulkan :stage dahulu — peringkat selepasnya bergantung sepenuhnya padanya.',
    'break_action' => 'Sediakan justifikasi dan pelan kontingensi untuk :stage minggu ini.',
    'action_owner' => 'TINDAKAN OLEH :owner',
    'action_owner_none' => 'TINDAKAN DIPERLUKAN',
    'owner_label' => 'PIC',

    'legend_ok' => 'Capai sasaran',
    'legend_warn' => 'Hampir sasaran',
    'legend_break' => 'Jalan terputus',
    'legend_blocked' => 'Tersekat di hulu',
];
