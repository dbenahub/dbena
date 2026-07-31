<?php

return [
    'page_title' => 'LAPORAN PRESTASI PEMILIK',
    'page_subtitle' => 'Penilaian PIC beserta ulasan & tindakan',

    'period' => [
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
    ],
    'label' => [
        'weekly' => 'Minggu :week, :month :year',
    ],

    'filter_period' => 'Tempoh',
    'filter_week' => 'Minggu',
    'filter_service' => 'Servis',
    'all_services' => 'Semua Servis',
    'export_pdf' => 'Eksport PDF',
    'generated_at' => 'Dijana pada',
    'generated_by' => 'Dijana oleh',

    'summary' => [
        'title' => 'Ringkasan Pasukan',
        'headline' => 'Skor pasukan :score% bagi tempoh :period — :owners PIC memantau :metrics metrik.',
        'team_score' => 'Skor Pasukan',
        'owners' => 'Bilangan PIC',
        'metrics_tracked' => 'Metrik Dipantau',
        'on_track' => 'Capai Sasaran',
        'critical' => 'Kritikal',
        'pending' => 'Belum Update',
        'top_performer' => 'Prestasi Terbaik',
        'needs_attention' => 'Perlu Perhatian',
        'none_need_attention' => 'Semua PIC berada dalam keadaan terkawal.',
    ],

    'owner' => [
        'score' => 'Skor',
        'grade' => 'Gred',
        'metrics' => 'metrik',
        'on_track' => 'Capai Sasaran',
        'has_plan' => 'Ada Pelan',
        'no_plan' => 'Tiada Pelan',
        'pending' => 'Belum Update',
        'avg_achievement' => 'Purata Pencapaian',
        'vs_previous' => 'vs tempoh lalu',
    ],

    'commentary_title' => 'Ulasan Prestasi',
    'actions_title' => 'Tindakan Diperlukan',
    'metrics_title' => 'Perincian Metrik',

    'col' => [
        'metric' => 'Metrik',
        'service' => 'Servis',
        'actual' => 'Actual',
        'target' => 'Sasaran',
        'achievement' => 'Pencapaian',
        'status' => 'Status',
        'plan' => 'Pelan Tindakan',
    ],

    'priority' => [
        'high' => 'Tinggi',
        'medium' => 'Sederhana',
        'low' => 'Rendah',
    ],

    'verdict' => [
        'excellent' => 'prestasi cemerlang',
        'good' => 'prestasi baik',
        'fair' => 'prestasi sederhana',
        'weak' => 'prestasi lemah',
        'critical' => 'prestasi kritikal',
    ],

    'commentary' => [
        'overall' => ':name mencatat skor :score% (Gred :grade) — :verdict, dengan :green daripada :total metrik mencapai sasaran.',
        'trend_up' => 'Prestasi meningkat :delta mata berbanding tempoh sebelumnya (:previous%), menunjukkan momentum positif yang perlu dikekalkan.',
        'trend_down' => 'Prestasi merosot :delta mata berbanding tempoh sebelumnya (:previous%) — kemerosotan ini perlu ditangani segera sebelum menjadi corak berterusan.',
        'trend_flat' => 'Prestasi kekal stabil berbanding tempoh sebelumnya (:previous%), tanpa perubahan ketara.',
        'trend_none' => 'Tiada data tempoh sebelumnya untuk perbandingan arah aliran.',
        'worst' => 'Metrik paling ketinggalan: :list. Ini memerlukan tumpuan segera.',
        'best' => 'Metrik yang melepasi sasaran: :list. Amalan di sini boleh dijadikan rujukan untuk metrik lain.',
        'no_plan' => ':count metrik gagal mencapai sasaran TANPA sebarang pelan tindakan direkodkan (:list). Ini adalah jurang disiplin, bukan sekadar jurang prestasi.',
        'has_plan' => ':count metrik belum mencapai sasaran tetapi sudah mempunyai pelan tindakan — kemajuan perlu dipantau setiap minggu.',
        'pending' => ':count daripada :total metrik masih belum dikemas kini untuk tempoh ini, menjadikan penilaian tidak lengkap.',
        'financial_gap' => 'Jumlah jurang kewangan yang perlu ditutup: :amount.',
    ],

    'action' => [
        'write_plan' => 'Sediakan pelan tindakan untuk ":metric"',
        'write_plan_detail' => 'Actual :actual berbanding sasaran :target (:service). Metrik ini berstatus Red kerana tiada pelan direkodkan.',
        'update_data' => 'Kemas kini data mingguan yang tertunggak',
        'update_data_detail' => ':count metrik tiada input langsung untuk tempoh ini. Penilaian tidak boleh dianggap muktamad sehingga data lengkap.',
        'revise_plan' => 'Semak semula pelan tindakan ":metric"',
        'revise_plan_detail' => 'Pelan sedia ada belum memberi kesan — pencapaian masih :pct%. Pertimbangkan pendekatan berbeza.',
        'escalate' => 'Naikkan kepada pengurusan untuk sokongan',
        'escalate_detail' => 'Skor di bawah 40% menunjukkan masalah struktur, bukan sekadar prestasi individu. Sesi semakan bersama pengurusan disyorkan.',
        'weekly_review' => 'Adakan semakan mingguan berfokus',
        'weekly_review_detail' => 'Skor pertengahan menunjukkan potensi pemulihan. Semakan mingguan pada metrik terlemah boleh menaikkan skor dengan cepat.',
        'maintain' => 'Kekalkan momentum semasa',
        'maintain_detail' => 'Prestasi berada pada tahap baik. Fokus pada konsistensi dan dokumentasikan amalan yang berkesan.',
    ],

    'no_data' => 'Tiada data prestasi PIC untuk tempoh ini.',
    'no_data_hint' => 'Pastikan Data Kritikal Mingguan telah diisi dan PIC ditetapkan untuk setiap metrik.',
];
