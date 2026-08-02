<?php

return [
    'task_overdue' => 'Lewat :days hari',
    'task_due_today' => 'Tarikh akhir hari ini',
    'task_kiv' => 'KIV sejak :days hari',

    'journey_break' => ':service — jalan terputus di :stage',
    'journey_missing' => 'Tiada rekod :stage langsung. :next tidak dapat disediakan tanpanya.',
    'journey_below' => 'Sasaran :stage tidak dicapai, dan peringkat selepasnya bergantung padanya.',

    'no_action_plan' => 'Pelan tindakan belum ditulis — :metric',
    'no_action_plan_body' => 'Metrik :service ini merah tanpa pelan. Menulis pelan ialah langkah pertama.',

    'metric_red' => ':service — :metric di bawah sasaran',

    'roadmap_idle' => ':service dijadualkan aktif tetapi kosong',
    'roadmap_idle_body' => 'Roadmap menandakan :status untuk :month, tetapi tiada satu pun angka direkodkan.',

    'no_figures_body' => 'Tiada satu pun angka direkodkan untuk :month.',

    'badge' => [
        'overdue' => 'LEWAT',
        'today' => 'HARI INI',
        'kiv' => 'KIV',
        'blocked' => 'MENYEKAT',
        'no_plan' => 'TIADA PELAN',
        'behind' => 'DI BAWAH',
        'roadmap' => 'ROADMAP',
    ],

    'all_clear' => 'Tiada isu menyekat minggu ini',
    'all_clear_body' => 'Tiada tarikh akhir terlepas, tiada peringkat corong terputus, dan setiap metrik merah sudah mempunyai pelan tindakan.',
];
