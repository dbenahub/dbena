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

    'part_a' => 'PART A — OVERALL PERFORMANCE',
    'part_b' => 'PART B — ANALYSIS BY SERVICE',

    'roadmap' => [
        'status' => 'Roadmap Status',
        'unknown' => 'Not set yet',
        'active_note' => ':count of :total services are running this month.',
        'active_list' => 'Active: :names',
        'paused_list' => 'Paused: :names',
        'months_active' => ':count active months this year',
        'not_set' => 'This service has no roadmap set for :year, so the analysis below assumes it is running.',
    ],

    'service' => [
        'section' => 'SERVICE :name',
        'paused' => ':service is paused in the roadmap (:status). The :actual recorded this month is expected, not a failure — no campaign is running and no target applies. No recovery actions are proposed for this service.',
        'narrative_green' => ':service reached :pct of target (:actual of :target) and is on track. The roadmap marks it :status. The focus should stay on holding this pace.',
        'narrative_amber' => ':service reached :pct of target (:actual of :target) with a :gap gap still open. The roadmap marks it :status, so the target does apply and this gap must close within the same period.',
        'narrative_red' => ':service reached only :pct of target (:actual of :target), leaving a :gap gap. The roadmap marks it :status — a campaign is running, so this gap is not caused by a paused service and needs the immediate action below.',
        'no_causes' => 'No causes recorded for this service.',
        'no_actions' => 'No action needed for this service in this period.',
    ],

    'none' => 'No issues recorded for this section.',
    'page' => 'Page',
    'generated' => 'Generated :date by :by',

    'export_pdf' => 'PDF Report',
    'period_label' => 'Period',
    'week_label' => 'Week',
];
