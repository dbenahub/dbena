<?php

return [
    'title' => 'Sales Journey Map',
    'subtitle' => 'From Lead to Sales Collection',
    'start' => 'START',
    'goal' => 'TARGET',
    'week' => 'week',
    'target' => 'Target',
    'gap' => 'Short by',
    'blocked_by' => 'Blocked by :stage',
    'blocked_note' => 'This stage recovers on its own once :stage is fixed. Do not chase it separately.',

    'stage' => [
        'lead' => 'LEAD',
        'site_visit' => 'SITE VISIT',
        'quotation' => 'QUOTATION',
        'sales' => 'SALES & COLLECTION',
    ],

    'amount' => [
        'quotation' => 'Quotation value',
        'sales' => 'Collection (deposit)',
    ],

    'cause_title' => [
        'lead' => 'No leads coming in',
        'site_visit' => 'Not enough site visits',
        'quotation' => 'Quotations cannot be issued',
        'sales' => 'No sales potential',
    ],

    'cause' => [
        'lead' => 'Marketing activity is not running, or not running consistently.',
        'site_visit' => 'No leads coming in, leads are poor quality, or there is no follow up.',
        'quotation' => 'Not enough site visits, incomplete information, or unclear customer requirements.',
        'sales' => 'No quotations or too few, customers not closing, or weak follow up.',
    ],

    'healthy_title' => 'The journey is clear',
    'healthy_body' => 'Every stage is on target. Keep this pace.',

    'break_title' => 'The road breaks at :stage',
    'break_body' => 'Company sales and collection targets depend on this stage. Until :stage is fixed, the :count stages downstream will not reach target no matter what effort goes into them.',
    'break_body_single' => 'Company sales and collection targets depend on this stage. Fix :stage first — the stages after it depend entirely on it.',
    'break_action' => 'Action required from the data owner: prepare a justification and contingency plan for :stage this week.',

    'legend_ok' => 'On target',
    'legend_warn' => 'Near target',
    'legend_break' => 'Road broken',
    'legend_blocked' => 'Blocked upstream',
];
