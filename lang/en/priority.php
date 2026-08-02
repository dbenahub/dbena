<?php

return [
    'task_overdue' => ':days days overdue',
    'task_due_today' => 'Due today',
    'task_kiv' => 'KIV for :days days',

    'journey_break' => ':service — the road breaks at :stage',
    'journey_missing' => 'Not a single :stage was recorded. :next cannot be prepared without it.',
    'journey_below' => 'The :stage target was missed, and every stage after it depends on it.',

    'no_action_plan' => 'No action plan written — :metric',
    'no_action_plan_body' => 'This :service metric is red with no plan. Writing one is the first step.',

    'metric_red' => ':service — :metric is below target',

    'roadmap_idle' => ':service is scheduled active but empty',
    'roadmap_idle_body' => 'The roadmap marks :status for :month, but not a single figure was recorded.',

    'badge' => [
        'overdue' => 'OVERDUE',
        'today' => 'TODAY',
        'kiv' => 'KIV',
        'blocked' => 'BLOCKING',
        'no_plan' => 'NO PLAN',
        'behind' => 'BEHIND',
        'roadmap' => 'ROADMAP',
    ],

    'all_clear' => 'Nothing is blocking this week',
    'all_clear_body' => 'No missed deadlines, no broken funnel stages, and every red metric already has an action plan.',
];
