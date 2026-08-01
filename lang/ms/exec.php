<?php

return [
    // ── Muka hadapan ──────────────────────────────────────────────────
    'company' => 'DBENA SDN BHD',
    'title' => 'LAPORAN PRESTASI PEMILIK',
    'subtitle_service' => 'SERVIS :service  |  :period',
    'subtitle_all' => 'SEMUA SERVIS  |  :period',
    'kicker' => 'Laporan Pengurusan Berasaskan Data',
    'meta' => [
        'period' => 'Tempoh Pelaporan',
        'generated' => 'Tarikh Dijana',
        'prepared_for' => 'Disediakan Untuk',
        'prepared_for_value' => 'Pengurusan DBENA SDN BHD',
        'status' => 'Status Dokumen',
        'status_value' => 'Sulit & Untuk Kegunaan Dalaman',
        'owner' => 'Pemilik Data',
        'all_owners' => 'Semua Pemilik',
    ],
    'focus' => 'Fokus laporan: prestasi, punca akar, jurang kritikal dan pelan tindakan 30 hari.',

    // ── Keterukan ─────────────────────────────────────────────────────
    'severity' => [
        'critical' => 'KRITIKAL',
        'attention' => 'PERLU PERHATIAN',
        'stable' => 'STABIL',
    ],

    // ── 1. Ringkasan Eksekutif ────────────────────────────────────────
    'col_service' => 'Servis',
    'col_pic' => 'PIC',
    'pic_heading_note' => 'Skor PIC merangkumi semua servis yang dipegangnya.',
    'pic_no_red' => 'Tiada metrik kritikal bagi PIC ini dalam tempoh ini.',

    's1' => '1. Ringkasan Eksekutif',
    'exec_lead' => 'Prestasi keseluruhan pasukan bagi :period berada pada tahap :severity. :green daripada :total metrik mencapai sasaran, manakala :red metrik berstatus kritikal.',
    'tile' => [
        'score' => 'SKOR PASUKAN',
        'score_note' => 'Tahap :severity',
        'achieved' => 'CAPAI SASARAN',
        'achieved_note' => ':red metrik kritikal',
        'owners' => 'PIC DIPANTAU',
        'owners_note' => 'Dinilai bulan ini',
        'gap' => 'JURANG UTAMA',
        'gap_note' => 'Jumlah kekurangan RM',
    ],
    'priority_table' => [
        'rank' => 'Keutamaan',
        'issue' => 'Isu Utama',
        'evidence' => 'Bukti Data',
        'implication' => 'Implikasi',
    ],
    'implication' => [
        'blocks' => 'Menyekat :downstream daripada mencapai sasaran.',
        'direct' => 'Menjejaskan pencapaian sasaran secara langsung.',
    ],
    'summary_heading' => 'Rumusan Pengurusan',
    'no_target_note' => 'Metrik berikut tiada sasaran berangka dan tidak boleh dinilai: :metrics. Sasaran perlu ditetapkan sebelum prestasi boleh diukur.',
    'no_plan_note' => ':count metrik kritikal tiada pelan tindakan bertulis. Ini masalah disiplin pemantauan, bukan sekadar prestasi.',

    // ── 2. Scorecard ──────────────────────────────────────────────────
    's2' => '2. Scorecard Prestasi Keseluruhan',
    'scorecard' => [
        'metric' => 'Metrik',
        'pic' => 'PIC',
        'actual' => 'Actual',
        'target' => 'Sasaran',
        'pct' => 'Pencapaian',
        'status' => 'Status',
        'gap' => 'Jurang',
    ],
    'pic_heading' => 'Penilaian PIC',
    'pic_table' => [
        'pic' => 'PIC',
        'score' => 'Skor',
        'grade' => 'Gred',
        'achieved' => 'Capai Sasaran',
        'no_plan' => 'Tanpa Pelan',
        'verdict' => 'Penilaian',
    ],
    'observations_heading' => 'Pemerhatian Utama',
    'obs' => [
        'ads_vs_lead' => 'Ads Spend mencapai :ads%, namun Lead hanya mencapai :lead%. Ini menunjukkan isu kecekapan kempen atau kualiti lead.',
        'lead_to_visit' => 'Daripada :lead lead, hanya :visit menjadi Site Visit. Kadar penukaran sebenar :rate%.',
        'visit_to_quote' => 'Daripada :visit Site Visit, hanya :quote quotation dikeluarkan. Kadar penukaran :rate%.',
        'quote_to_sales' => 'Nilai quotation :amount menghasilkan Sales :sales, bersamaan kadar penukaran :rate%.',
    ],

    // ── 3. Punca Akar ─────────────────────────────────────────────────
    's3' => '3. Analisis Punca Akar',
    's3_1' => '3.1 Rantaian Sebab dan Kesan',
    's3_2' => '3.2 Diagnosis Pengurusan',
    's3_3' => '3.3 Risiko Jika Tiada Tindakan',
    'root_table' => [
        'cause' => 'Punca Akar',
        'evidence' => 'Bukti',
        'effect' => 'Kesan Langsung',
        'level' => 'Tahap',
    ],
    'level' => [
        'very_high' => 'Sangat Tinggi',
        'high' => 'Tinggi',
        'moderate' => 'Sederhana',
    ],
    'effect' => ['none' => 'Kesan terhad pada metrik ini sahaja'],
    'diagnosis_break' => 'Halangan pertama dalam corong ialah :stage. Peringkat selepasnya tidak akan pulih dengan usaha berasingan — ia bergantung sepenuhnya pada peringkat ini. Masalahnya adalah struktur corong dan disiplin pelaksanaan, bukan sekadar prestasi individu.',
    'diagnosis_clear' => 'Tiada halangan struktur dikesan dalam corong. Setiap peringkat menyokong peringkat berikutnya, dan jurang yang tinggal boleh diuruskan secara berasingan.',
    'risks' => [
        'Sales pipeline bulan berikutnya kekal nipis dan tidak stabil.',
        'Collection deposit serta progress claim akan terus terjejas.',
        'Kos iklan meningkat tanpa pulangan setimpal.',
        'Pasukan sukar dinilai kerana action plan dan sasaran tidak lengkap.',
    ],

    // ── 4. Pelan Tindakan ─────────────────────────────────────────────
    's4' => '4. Cadangan Pelan Tindakan 30 Hari',
    's4_note' => 'Pelan di bawah ialah cadangan pengurusan berdasarkan jurang yang dikenal pasti. Sasaran mingguan perlu disahkan dalam mesyuarat pengurusan.',
    'weekly_heading' => 'Sasaran Operasi Mingguan Disyorkan',
    'weekly_table' => [
        'metric' => 'Metrik',
        'weekly' => 'Sasaran Mingguan',
        'owner' => 'Owner',
        'cadence' => 'Kekerapan Semakan',
        'trigger' => 'Trigger Eskalasi',
    ],
    'cadence' => [
        'harian' => 'Harian',
        'dua_kali' => '2 kali seminggu',
        'mingguan' => 'Mingguan',
    ],
    'trigger' => 'Kurang :value menjelang Rabu',

    // ── 5. Mengikut PIC ───────────────────────────────────────────────
    's5' => '5. Pelan Tindakan Mengikut PIC',
    'pic_focus_table' => [
        'focus' => 'Fokus',
        'problem' => 'Masalah',
        'action' => 'Tindakan Wajib',
        'kpi' => 'KPI Tempoh Ini',
        'evidence' => 'Bukti / Rekod',
    ],
    'no_action_recorded' => 'Belum direkodkan — perlu diisi oleh PIC',
    'accountability_heading' => 'Peraturan Accountability',
    'accountability' => [
        'Setiap metrik merah mesti mempunyai PIC, tindakan, tarikh siap dan status semasa.',
        'Status "sedang buat" tidak diterima tanpa bukti: log call, calendar, quotation register atau collection tracker.',
        'Halangan yang tidak selesai dalam 48 jam mesti dinaikkan kepada pengurusan.',
        'Prestasi dinilai berdasarkan output dan trend mingguan, bukan aktiviti semata-mata.',
    ],

    // ── 6. Pemantauan ─────────────────────────────────────────────────
    's6' => '6. Struktur Pemantauan & Mesyuarat',
    's6_1' => '6.1 Daily Funnel Huddle — 15 Minit',
    's6_2' => '6.2 Weekly Performance Review — 45 Minit',
    'huddle_table' => [
        'agenda' => 'Agenda',
        'data' => 'Data Wajib',
        'owner' => 'Owner',
        'decision' => 'Keputusan',
    ],
    'weekly_review' => [
        'Semak actual vs target minggu semasa dan kumulatif bulan.',
        'Semak conversion rate bagi setiap peringkat funnel.',
        'Semak quotation ageing dan top 10 peluang jualan.',
        'Semak collection due, overdue dan sebab kelewatan.',
        'Tetapkan maksimum 3 tindakan kritikal bagi minggu berikutnya.',
    ],

    // ── 7. Keputusan ──────────────────────────────────────────────────
    's7' => '7. Keputusan & Arahan Pengurusan Dicadangkan',
    's7_lead' => 'Berdasarkan prestasi :period, skor pasukan :score% dan :red daripada :total metrik kritikal menunjukkan intervensi pengurusan perlu dibuat segera.',
    'decision_table' => [
        'decision' => 'Keputusan Diperlukan',
        'proposal' => 'Cadangan',
        'approver' => 'Pemilik Kelulusan',
        'date' => 'Tarikh',
    ],
    'approver' => 'Pengurusan',
    'immediately' => 'Segera',
    'conclusion_heading' => 'Kesimpulan Akhir',
    'conclusion_cause' => 'Punca paling kritikal: :cause',
    'conclusion_action' => 'Tindakan paling penting: capai sasaran mingguan yang disenaraikan di Bahagian 4, dan wajibkan action plan bertulis bagi semua metrik merah.',
    'conclusion_success' => 'Indikator kejayaan 30 hari: corong kembali stabil dan setiap peringkat menyokong peringkat berikutnya.',
    'source' => 'Sumber data: Dashboard Prestasi DBENA SDN BHD — :period. Dijana secara automatik.',

    'none' => 'Tiada',
];
