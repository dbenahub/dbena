<?php

return [
    'title' => 'TASK CALENDAR',
    'subtitle' => 'Every planned task shows up on the calendar.',
    'nav' => 'Calendar',

    'all_team' => 'All Team',
    'team_filter' => 'TEAM FILTER',
    'others' => 'Others',
    'add_task' => 'Add Task',
    'added' => 'Task added to the calendar.',
    'export' => 'Export Calendar',

    'view' => [
        'month' => 'Month',
        'week' => 'Week',
        'day' => 'Day',
    ],
    'today' => 'Today',

    'stat' => [
        'total' => 'TOTAL TASK',
        'total_note' => 'All Tasks',
        'in_progress' => 'IN PROGRESS',
        'in_progress_note' => 'Under Way',
        'completed' => 'COMPLETED',
        'completed_note' => 'Finished',
        'pending' => 'PENDING',
        'pending_note' => 'Waiting',
        'cancelled' => 'CANCELLED',
        'cancelled_note' => 'Called off',
        'rate' => 'COMPLETION RATE',
        'rate_note' => 'This Month’s Target',
    ],

    'upcoming' => 'UPCOMING TASKS',
    'no_upcoming' => 'No upcoming tasks.',
    'view_all' => 'View All Tasks',
    'no_events' => 'No tasks on this day.',
    'all_day' => 'All day',

    'google' => [
        'title' => 'Google Calendar',
        'sync' => 'Push to Google Calendar',
        'syncing' => 'Pushing…',
        'not_connected' => 'No Calendar ID set yet. An admin can set it in Admin Panel → Roadmap.',
        'done' => ':created new events, :updated updated, :deleted removed.',
        'needs_write' => 'Google refused the write. The calendar is shared for READING only. Open calendar.google.com → Settings and sharing → Share with specific people → :email → change to "Make changes to events" → Save. (:message)',
        'api_disabled' => 'The Google Calendar API is not enabled in your Google Cloud project. Open this link, press ENABLE, wait a minute, then try again: :url',
        'source' => 'Pushed automatically from the DBENA Dashboard — Task Planning.',
        'last_synced' => 'Last pushed :time',
        'never' => 'Never pushed',
        'calendar_id' => 'Calendar ID for tasks',
    ],

    'form' => [
        'title' => 'Add Task',
        'task' => 'Task',
        'task_placeholder' => 'What needs doing?',
        'department' => 'Department',
        'day' => 'Day',
        'time' => 'Time (optional)',
        'action_by' => 'Action By',
        'monitor_by' => 'Monitor By',
        'status' => 'Status',
        'save' => 'Save',
        'cancel' => 'Cancel',
    ],

    'footer' => [
        'plan' => 'Plan Your Work',
        'work' => 'Work Your Plan',
        'achieve' => 'Achieve Your Goal',
    ],
];
