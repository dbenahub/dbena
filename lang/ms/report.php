<?php

return [
    'title' => 'LAPORAN PRESTASI MENYELURUH',
    'subtitle' => 'Comprehensive Performance Report',
    'confidential' => 'SULIT — DOKUMEN DALAMAN SYARIKAT',
    'company' => 'DBENA SDN BHD',
    'reg' => 'No. Pendaftaran 1518035-A',

    'cover' => [
        'period' => 'TEMPOH LAPORAN',
        'scope' => 'SKOP',
        'all_services' => 'Semua Servis',
        'prepared' => 'DISEDIAKAN OLEH',
        'date' => 'TARIKH JANAAN',
    ],

    'scope' => [
        'weekly' => 'Minggu :week, :month',
    ],

    'section' => [
        'summary' => 'RINGKASAN EKSEKUTIF',
        'comparison' => 'PERBANDINGAN TEMPOH',
        'trend' => 'TREND 12 BULAN',
        'breakdown' => 'PECAHAN MENGIKUT SERVIS',
        'funnel' => 'ANALISIS CORONG JUALAN',
        'causes' => 'PUNCA DAN SEBAB',
        'owners' => 'AKAUNTABILITI PEMILIK DATA',
        'actions' => 'CADANGAN TINDAKAN',
    ],

    'summary' => [
        'actual' => 'Jualan Sebenar',
        'target' => 'Sasaran',
        'achievement' => 'Pencapaian',
        'gap' => 'Jurang',
        'verdict' => 'Penilaian',
        'vs_previous' => 'Berbanding :period',
        'no_previous' => 'Tiada data tempoh sebelumnya untuk dibandingkan.',
        'narrative_green' => 'Prestasi berada pada landasan. :pct daripada sasaran dicapai, dan tumpuan sepatutnya kekal pada mengekalkan rentak ini.',
        'narrative_amber' => 'Prestasi hampir sasaran tetapi belum selamat. :pct dicapai, dengan jurang RM:gap yang masih perlu ditutup dalam tempoh ini.',
        'narrative_red' => 'Prestasi kritikal. Hanya :pct daripada sasaran dicapai dan jurang RM:gap adalah terlalu besar untuk ditutup tanpa tindakan segera.',
    ],

    'verdict' => [
        'on_track' => 'Pada Landasan',
        'watch' => 'Perlu Diperhatikan',
        'critical' => 'Kritikal',
    ],

    'col' => [
        'service' => 'Servis',
        'actual' => 'Sebenar',
        'target' => 'Sasaran',
        'pct' => 'Pencapaian',
        'gap' => 'Jurang',
        'status' => 'Status',
        'stage' => 'Peringkat',
        'owner' => 'Pemilik',
        'reason' => 'Punca',
        'effect' => 'Kesan',
        'metrics' => 'Metrik',
        'red' => 'Merah',
        'amber' => 'Kuning',
        'green' => 'Hijau',
        'score' => 'Skor',
        'urgency' => 'Keutamaan',
        'what' => 'Tindakan',
        'why' => 'Sebab',
        'when' => 'Bila',
    ],

    'legend_actual' => 'Jualan Sebenar',
    'legend_target' => 'Sasaran Bulanan',

    'funnel_note' => 'Setiap peringkat bergantung pada peringkat sebelumnya. Peringkat pertama yang gagal menghadkan setiap peringkat selepasnya, tanpa mengira usaha yang dicurahkan di sana.',

    'cause' => [
        'missing' => 'Tiada satu pun :stage direkodkan untuk tempoh ini.',
        'below' => 'Sasaran :stage hanya dicapai :pct%.',
        'effect' => ':count peringkat di hilirnya tersekat oleh ini.',
        'metric_red' => ':metric mencatat :actual berbanding sasaran :target.',
        'has_plan' => 'Pelan tindakan sudah ditulis.',
        'no_plan' => 'Pelan tindakan BELUM ditulis.',
    ],

    'action' => [
        'immediate' => 'SEGERA',
        'ongoing' => 'BERTERUSAN',
        'this_week' => 'Minggu ini',
        'this_month' => 'Bulan ini',
        'fix_stage' => 'Betulkan :stage sebelum apa-apa yang lain.',
        'fix_stage_why' => ':count peringkat di hilirnya tidak akan capai sasaran selagi ini tidak diselesaikan.',
        'write_plan' => 'Tulis pelan tindakan untuk :metric.',
        'write_plan_why' => 'Metrik ini merah tanpa pelan. Tanpa pelan, tiada siapa memilikinya.',
        'metric_why' => ':metric mencatat :actual berbanding sasaran :target.',
        'close_gap' => 'Tutup jurang jualan :amount.',
        'close_gap_why' => 'Servis ini mencatat pencapaian terendah pada :pct% dan menyumbang jurang terbesar.',
    ],

    'part_a' => 'BAHAGIAN A — PRESTASI KESELURUHAN',
    'part_b' => 'BAHAGIAN B — ANALISIS MENGIKUT SERVIS',

    'roadmap' => [
        'status' => 'Status Roadmap',
        'unknown' => 'Belum ditetapkan',
        'active_note' => ':count daripada :total servis sedang berjalan bulan ini.',
        'active_list' => 'Aktif: :names',
        'paused_list' => 'Dijeda: :names',
        'months_active' => ':count bulan aktif tahun ini',
        'not_set' => 'Roadmap servis ini belum ditetapkan untuk tahun :year, jadi analisis di bawah menganggapnya sedang berjalan.',
    ],

    'service' => [
        'section' => 'SERVIS :name',
        'paused' => ':service dijeda dalam roadmap (:status). Jualan :actual bulan ini adalah dijangka, bukan kegagalan — tiada kempen berjalan dan tiada sasaran dikenakan. Tiada tindakan pemulihan dicadangkan untuk servis ini.',
        'narrative_green' => ':service mencapai :pct daripada sasaran (:actual daripada :target) dan berada pada landasan. Roadmap menandakannya :status. Tumpuan sepatutnya kekal pada mengekalkan rentak ini.',
        'narrative_amber' => ':service mencapai :pct daripada sasaran (:actual daripada :target) dengan jurang :gap yang masih terbuka. Roadmap menandakannya :status, jadi sasaran itu memang terpakai dan jurang ini perlu ditutup dalam tempoh yang sama.',
        'narrative_red' => ':service mencapai hanya :pct daripada sasaran (:actual daripada :target), meninggalkan jurang :gap. Roadmap menandakannya :status — kempen sedang berjalan, jadi jurang ini bukan disebabkan servis yang dijeda dan memerlukan tindakan segera di bawah.',
        'no_causes' => 'Tiada punca direkodkan untuk servis ini.',
        'no_actions' => 'Tiada tindakan diperlukan untuk servis ini pada tempoh ini.',
    ],

    'none' => 'Tiada isu direkodkan untuk seksyen ini.',
    'page' => 'Halaman',
    'generated' => 'Dijana :date oleh :by',

    'export_pdf' => 'Laporan PDF',
    'period_label' => 'Tempoh',
    'week_label' => 'Minggu',
];
