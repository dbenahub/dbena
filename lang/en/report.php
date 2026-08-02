<?php

return [
    'title' => 'COMPREHENSIVE PERFORMANCE REPORT',
    'subtitle' => 'Laporan Prestasi Menyeluruh',
    'confidential' => 'CONFIDENTIAL — INTERNAL CORPORATE DOCUMENT',
    'company' => 'DBENA SDN BHD',
    'reg' => 'Registration No. 1518035-A',

    'cover' => [
        'period' => 'REPORT PERIOD',
        'scope' => 'SCOPE',
        'all_services' => 'All Services',
        'prepared' => 'PREPARED BY',
        'date' => 'DATE GENERATED',
    ],

    'scope' => [
        'weekly' => 'Week :week, :month',
    ],

    'section' => [
        'summary' => 'EXECUTIVE SUMMARY',
        'comparison' => 'PERIOD COMPARISON',
        'trend' => '12-MONTH TREND',
        'breakdown' => 'BREAKDOWN BY SERVICE',
        'funnel' => 'SALES FUNNEL ANALYSIS',
        'causes' => 'ROOT CAUSE ANALYSIS',
        'owners' => 'OWNER ACCOUNTABILITY',
        'actions' => 'RECOMMENDED ACTIONS',
    ],

    'summary' => [
        'actual' => 'Actual Sales',
        'target' => 'Target',
        'achievement' => 'Achievement',
        'gap' => 'Gap',
        'verdict' => 'Verdict',
        'vs_previous' => 'Versus :period',
        'no_previous' => 'No previous period data to compare against.',
        'narrative_green' => 'Performance is on track. :pct of target achieved, and the focus should stay on holding this pace.',
        'narrative_amber' => 'Performance is close but not safe. :pct achieved, with an RM:gap gap still to close within the period.',
        'narrative_red' => 'Performance is critical. Only :pct of target achieved and the RM:gap gap is too large to close without immediate action.',
    ],

    'verdict' => [
        'on_track' => 'On Track',
        'watch' => 'Needs Watching',
        'critical' => 'Critical',
    ],

    'col' => [
        'service' => 'Service',
        'actual' => 'Actual',
        'target' => 'Target',
        'pct' => 'Achievement',
        'gap' => 'Gap',
        'status' => 'Status',
        'stage' => 'Stage',
        'owner' => 'Owner',
        'reason' => 'Cause',
        'effect' => 'Effect',
        'metrics' => 'Metrics',
        'red' => 'Red',
        'amber' => 'Amber',
        'green' => 'Green',
        'score' => 'Score',
        'urgency' => 'Priority',
        'what' => 'Action',
        'why' => 'Why',
        'when' => 'When',
    ],

    'legend_actual' => 'Actual Sales',
    'legend_target' => 'Monthly Target',

    'funnel_note' => 'Each stage depends on the one before it. The first stage that fails caps every stage after it, no matter how much effort goes in there.',

    'cause' => [
        'missing' => 'Not a single :stage was recorded for this period.',
        'below' => 'The :stage target reached only :pct%.',
        'effect' => ':count downstream stages are blocked by this.',
        'metric_red' => ':metric recorded :actual against a :target target.',
        'has_plan' => 'An action plan is already written.',
        'no_plan' => 'No action plan has been written.',
    ],

    'action' => [
        'immediate' => 'IMMEDIATE',
        'ongoing' => 'ONGOING',
        'this_week' => 'This week',
        'this_month' => 'This month',
        'fix_stage' => 'Fix :stage before anything else.',
        'fix_stage_why' => ':count downstream stages will not reach target while this stays unresolved.',
        'write_plan' => 'Write an action plan for :metric.',
        'write_plan_why' => 'This metric is red with no plan. Without a plan, nobody owns it.',
        'metric_why' => ':metric recorded :actual against a :target target.',
        'close_gap' => 'Close the :amount sales gap.',
        'close_gap_why' => 'This service recorded the lowest achievement at :pct% and carries the largest gap.',
    ],

    'none' => 'No issues recorded for this section.',
    'page' => 'Page',
    'generated' => 'Generated :date by :by',

    'export_pdf' => 'PDF Report',
    'export_csv' => 'CSV Data',
    'period_label' => 'Period',
    'week_label' => 'Week',
];
