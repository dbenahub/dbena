<?php

return [
    'title' => 'ANNUAL SERVICE ROADMAP',
    'subtitle' => 'The service journey across the year',
    'journey' => '12-MONTH JOURNEY',
    'journey_note' => 'Towards the Best Service',
    'service_col' => 'SERVICE',

    'status' => [
        'none' => 'No activity',
        'active_all_year' => 'Active All Year',
        'campaign' => 'Campaign Running',
        'paused' => 'Pause / Campaign Stop',
        'resumed' => 'Resumed',
    ],

    'legend' => 'LEGEND',
    'legend_note' => [
        'active_all_year' => 'Service runs all 12 months',
        'campaign' => 'Campaign period running',
        'paused' => 'Campaign paused for now',
        'resumed' => 'Campaign picked back up',
        'none' => 'Nothing planned',
    ],

    'summary' => 'STRATEGY SUMMARY',
    'summary_empty' => 'The strategy summary has not been written yet.',

    'view' => [
        'label' => 'View',
        'grid' => 'Year Grid',
        'quarter' => 'Quarters',
        'list' => 'Month List',
    ],

    'target' => [
        'monthly' => 'Monthly target',
        'annual' => 'Annual target',
        'company' => 'Company annual target',
        'active_months' => ':count active months',
        'note' => 'The annual target counts active months only — paused months carry no target.',
    ],

    'calendar' => [
        'title' => 'Google Calendar',
        'events' => ':count events',
        'events_month' => ':count events in :month',
        'none' => 'No events this month.',
        'not_connected' => 'Google Calendar is not connected yet.',
        'id' => 'Calendar ID',
        'id_hint' => 'Google Calendar → calendar settings → Integrate calendar → Calendar ID',
        'share_hint' => 'Share the calendar with :email (See all event details) before testing.',
        'test' => 'Test connection',
        'ok' => 'Read :count events for :year.',
        'untitled' => 'Untitled event',
        'failed' => 'Could not read the calendar: :message',
        'all_day' => 'All day',
    ],

    'empty_title' => 'The :year roadmap is not set up yet',
    'empty_body' => 'An admin needs to set each service’s status in the Admin Panel.',
    'empty_body_admin' => 'Set each service’s status in Admin Panel → Annual Roadmap.',

    'admin' => [
        'title' => 'Annual Service Roadmap',
        'note' => 'Click any cell to cycle its status. Changes save immediately.',
        'cycle_hint' => 'Click: None → Active All Year → Campaign → Pause → Resumed',
        'text' => 'Board text',
        'heading' => 'Heading',
        'sub' => 'Subheading',
        'summary_line' => 'Strategy summary (one line per point)',
        'save' => 'Save text',
        'saved' => 'Roadmap saved.',
        'fill_row' => 'Fill row',
        'clear_row' => 'Clear row',
        'copy_year' => 'Copy from :year',
        'copied' => 'Copied from :year.',
        'nothing_to_copy' => 'There is no :year roadmap to copy.',
    ],
];
