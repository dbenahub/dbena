<?php

return [
    'title' => 'Strategic Planning & KPI Alignment',
    'subtitle' => 'Dashboard Summary',
    'vision' => 'VISION',
    'view_only' => 'View only — updated through Google Sheet',

    'col' => [
        'kra' => 'KRA',
        'kpi' => 'KPI',
        'target' => 'TARGET',
        'tactics' => 'TACTICS',
        'initiatives' => 'INITIATIVES',
        'timeline' => 'TIMELINE',
        'pic' => 'PIC/CI',
    ],

    'pic_prefix' => 'HOD',
    'synced_at' => 'Synced :time',

    'empty_title' => 'Strategic Planning not connected yet',
    'empty_body' => 'An admin needs to connect this service’s strategic planning tab in the Admin Panel before it appears here.',
    'empty_body_admin' => 'Connect this service’s strategic planning tab in Admin Panel → Google Sheet.',

    'admin' => [
        'title' => 'Strategic Planning & KPI Alignment',
        'note' => 'One Google Sheet file, one tab per service. Paste the file link once, then name the tab for each service.',
        'url' => 'Google Sheet file link',
        'tab' => 'Tab name',
        'tab_placeholder' => 'e.g. STRATEGIC_RENOVATION',
        'save' => 'Save',
        'sync' => 'Sync',
        'sync_all' => 'Sync all services',
        'saved' => 'Strategic planning settings saved.',
        'never' => 'Never synced',
        'no_tab' => 'No tab name set for :service.',
    ],

    'sync' => [
        'done' => ':rows rows and :tiles tiles synced.',
        'no_service' => 'This integration is not tied to any service.',
        'read_failed' => 'Could not read the sheet: :message',
        'no_table' => 'No header row found. Make sure this tab has a row with KRA and KPI as column headings.',
        'no_rows' => 'Header row found but there are no KRA rows beneath it.',
    ],
];
