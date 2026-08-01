<?php

return [
    'title' => 'Analisis Punca',
    'subtitle' => 'Mengapa sasaran tidak tercapai, dan apa yang perlu dibuat',
    'diagnosis' => 'Diagnosis',
    'root_cause' => 'Punca',
    'downstream_impact' => 'Kesan kepada syarikat',
    'required_action' => 'Tindakan diperlukan',
    'see_all' => 'Lihat kesemua :count diagnosis dalam Laporan Pemilik',
    'no_issues' => 'Tiada metrik bermasalah bagi tempoh ini.',

    // Naratif
    'headline' => ':metric mencatat :actual berbanding sasaran :target (:pct%).',
    'because_upstream' => 'Puncanya di hulu corong: :drivers. Quotation tidak dapat disediakan tanpa aktiviti yang mencukupi sebelum itu.',
    'because_conversion' => 'Aktiviti hulu mencukupi (:driver pada :pct%), jadi masalahnya bukan jumlah aktiviti tetapi kadar penukaran — peluang ada, tetapi tidak bertukar menjadi hasil.',
    'impact' => 'Kesannya merebak ke hilir: :downstream turut terjejas. Ini menjejaskan prestasi jualan dan kutipan syarikat, bukan sekadar angka metrik ini.',
    'no_plan' => 'Tiada pelan tindakan direkodkan untuk metrik ini.',

    'driver_failed_inline' => ':metric hanya :pct% daripada sasaran',
    'driver_zero_inline' => ':metric langsung tiada aktiviti',
    'driver_no_data_inline' => ':metric belum dikemas kini',

    'basis_actual' => 'berdasarkan kadar penukaran sebenar anda :rate%',
    'basis_target' => 'berdasarkan nisbah sasaran',

    // Label punca
    'cause' => [
        'driver_failed' => 'Aktiviti hulu di bawah sasaran',
        'driver_zero' => 'Tiada aktiviti hulu langsung',
        'driver_no_data' => 'Data hulu belum dikemas kini',
        'conversion' => 'Kadar penukaran lemah',
        'efficiency' => 'Kos per unit melebihi sasaran',
        'top_of_funnel' => 'Metrik puncak corong',
        'no_action_plan' => 'Tiada pelan tindakan',
    ],

    // Tindakan
    // Poin ringkas — versi naratif yang dipendekkan
    'point' => [
        'cause_zero' => 'Punca: :metric langsung tiada aktiviti.',
        'cause_no_data' => 'Punca: :metric tiada data — tidak boleh diurus tanpa diukur.',
        'cause_low' => 'Punca: :metric hanya :pct% daripada sasaran.',
        'cause_conversion' => 'Punca: aktiviti :driver mencukupi, penukaran yang lemah (:pct%).',
        'impact' => 'Kesan: :downstream turut terjejas:more.',
        'impact_more' => ' dan :count lagi',
    ],

    'action' => [
        'raise_upstream' => 'Tambah :count :driver untuk menutup jurang',
        'raise_upstream_detail' => 'Bermakna kira-kira :perWeek setiap minggu. Anda kini ada :have. Anggaran ini :basis.',
        'start_activity' => 'Mulakan aktiviti :driver segera',
        'start_activity_detail' => 'Tiada :driver direkodkan langsung untuk tempoh ini. Tanpa aktiviti ini, metrik hilir tidak mungkin bergerak.',
        'record_data' => 'Kemas kini data :driver',
        'record_data_detail' => 'Metrik ini tidak boleh didiagnos tanpa data hulu. Isi nilai mingguan dalam Google Sheet.',
        'fix_conversion' => 'Semak proses penukaran untuk :metric',
        'fix_conversion_detail' => 'Aktiviti :driver sudah mencukupi — jurangnya pada kualiti susulan, kelajuan tindak balas, atau kesesuaian tawaran. Semak sampel yang gagal ditukar.',
        'improve_efficiency' => 'Kurangkan kos per unit bagi :metric',
        'improve_efficiency_detail' => 'Semak sasaran iklan, kualiti lead, dan saluran yang membazir perbelanjaan.',
        'write_plan' => 'Rekod pelan tindakan untuk :metric',
        'write_plan_detail' => 'Isi lajur Action Plan dalam Google Sheet. Tanpa pelan bertulis, metrik ini kekal berstatus Red walaupun ada usaha dijalankan.',
    ],
];
