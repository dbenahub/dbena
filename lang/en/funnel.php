<?php

return [
    'title' => 'Root Cause Analysis',
    'subtitle' => 'Why the target was missed, and what to do about it',
    'diagnosis' => 'Diagnosis',
    'root_cause' => 'Root cause',
    'downstream_impact' => 'Impact on the company',
    'required_action' => 'Required action',
    'see_all' => 'See all :count diagnoses in the Owner Report',
    'no_issues' => 'No metrics are off target for this period.',

    'headline' => ':metric recorded :actual against a target of :target (:pct%).',
    'because_upstream' => 'The cause sits upstream in the funnel: :drivers. Quotations cannot be produced without enough activity feeding into them.',
    'because_conversion' => 'Upstream activity is sufficient (:driver at :pct%), so this is not a volume problem but a conversion one — the opportunities exist but are not turning into results.',
    'impact' => 'The effect carries downstream: :downstream are also affected. This drags company sales and collection performance, not just this one metric.',
    'no_plan' => 'No action plan has been recorded for this metric.',

    'driver_failed_inline' => ':metric is only :pct% of target',
    'driver_zero_inline' => ':metric has no activity at all',
    'driver_no_data_inline' => ':metric has not been updated',

    'basis_actual' => 'based on your actual conversion rate of :rate%',
    'basis_target' => 'based on the target ratio',

    'cause' => [
        'driver_failed' => 'Upstream activity below target',
        'driver_zero' => 'No upstream activity at all',
        'driver_no_data' => 'Upstream data not updated',
        'conversion' => 'Weak conversion rate',
        'efficiency' => 'Cost per unit above target',
        'top_of_funnel' => 'Top-of-funnel metric',
        'no_action_plan' => 'No action plan',
    ],

    'action' => [
        'raise_upstream' => 'Add :count :driver to close the gap',
        'raise_upstream_detail' => 'That is roughly :perWeek per week. You currently have :have. This estimate is :basis.',
        'start_activity' => 'Start :driver activity immediately',
        'start_activity_detail' => 'No :driver was recorded at all for this period. Without it, downstream metrics cannot move.',
        'record_data' => 'Update the :driver data',
        'record_data_detail' => 'This metric cannot be diagnosed without upstream data. Fill in the weekly values in the Google Sheet.',
        'fix_conversion' => 'Review the conversion process for :metric',
        'fix_conversion_detail' => ':driver activity is already sufficient — the gap is in follow-up quality, response speed, or offer fit. Review a sample of the ones that did not convert.',
        'improve_efficiency' => 'Reduce cost per unit for :metric',
        'improve_efficiency_detail' => 'Review ad targeting, lead quality, and which channels are wasting spend.',
        'write_plan' => 'Record an action plan for :metric',
        'write_plan_detail' => 'Fill in the Action Plan column in the Google Sheet. Without a written plan this metric stays Red even when work is being done.',
    ],
];
