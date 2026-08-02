<?php

return [
    'badge' => 'Plan: :plan',
    'tooltip' => 'The strategic plan sets :plan (:raw) for :kra. Fix one of the sheets so both agree.',

    'title' => ':count targets do not match Critical Data',
    'body' => 'Targets in this plan differ from the targets in the Critical Data table. An admin needs to align them.',
    'body_admin' => 'Fix it in Google Sheet — either the strategic planning tab or the Critical Data tab — then sync again. The dashboard writes to neither.',

    'critical' => 'Critical Data:',
    'plan' => 'Plan:',
];
