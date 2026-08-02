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
    'blocked_by_owner' => 'Blocked by :stage — :owner',
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
    'break_action' => 'Prepare a justification and contingency plan for :stage this week.',
    'action_owner' => 'ACTION BY :owner',
    'action_owner_none' => 'ACTION REQUIRED',
    'owner_label' => 'PIC',

    'break_missing_title' => 'The road breaks at :stage — nothing was recorded',
    'break_body_missing' => 'Not a single :stage was recorded for this period. :next cannot be prepared without :stage, so the :count stages downstream are stuck HERE — not with their own owners.',
    'break_body_missing_single' => 'Not a single :stage was recorded for this period. Every stage after it depends entirely on this data.',
    'justify_owner' => 'JUSTIFICATION BY :owner',
    'justify_owner_none' => 'NO OWNER SET FOR :stage',
    'justify_missing' => ':owner must explain why no :stage was recorded, and give a contingency plan this week.',
    'justify_below' => ':owner must justify why the :stage target was missed, and give a contingency plan this week.',
    'justify_missing_none' => 'Assign an owner for :stage first. A stage with no name is a stage with no justification.',
    'waiting_title' => 'Stuck waiting on :stage',
    'waiting_note' => 'These owners cannot hit target while :stage stays broken. Do not ask them for the justification.',
    'waiting_owner' => ':owner (:stage)',
    'waiting_owner_none' => '— (:stage)',

    'legend_ok' => 'On target',
    'legend_warn' => 'Near target',
    'legend_break' => 'Road broken',
    'legend_blocked' => 'Blocked upstream',
];
