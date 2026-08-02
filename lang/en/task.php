<?php

return [
    'title' => 'MONTHLY TASK PLANNING',
    'tagline' => 'PLAN • FOCUS • EXECUTE • ACHIEVE',
    'nav' => 'Task Planning',

    'month' => 'MONTH / BULAN',
    'prepared_by' => 'PREPARED BY',
    'date_prepared' => 'DATE PREPARED',

    'legend' => 'LEGEND / PETUNJUK',

    'mark' => [
        'planning' => 'Planning',
        'due_date' => 'Due Date',
        'complete' => 'Complete',
        'kiv' => 'KIV / Pending',
        'cancel' => 'Batal / Cancel',
    ],
    'mark_clear' => 'Clear',

    'col' => [
        'no' => 'BIL',
        'task' => 'ITEM',
        'action_by' => 'ACTION BY',
        'monitor_by' => 'MONITOR BY',
        'remark' => 'REMARK / CATATAN',
    ],

    'add_task' => 'Add task',
    'task_placeholder' => 'What needs doing?',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'deleted' => 'Task deleted.',
    'no_tasks' => 'No tasks for this month yet.',
    'no_departments' => 'No departments yet. An admin needs to add them in the Admin Panel.',

    'copy_month' => 'Copy last month',
    'copied' => 'Copied :count tasks from :month.',
    'nothing_to_copy' => 'There are no tasks in :month to copy.',
    'this_week' => 'This week',
    'today' => 'Today',

    'priority' => 'PRIORITY FOCUS THIS MONTH',
    'summary' => 'MONTHLY SUMMARY',
    'notes' => 'NOTES / CATATAN',
    'board_saved' => 'Board saved.',
    'priority_placeholder' => 'One line per focus',
    'notes_placeholder' => 'One line per note',
    'save_board' => 'Save board',

    'stat' => [
        'total' => 'TOTAL TASK',
        'in_progress' => 'IN PROGRESS',
        'cancelled' => 'CANCELLED',
        'completed' => 'COMPLETED',
        'pending' => 'PENDING',
        'focus' => 'TARGET FOCUS',
    ],

    'footer' => [
        'plan' => 'PLAN YOUR WORK',
        'work' => 'WORK YOUR PLAN',
        'achieve' => 'ACHIEVE YOUR GOAL',
    ],

    'export' => 'Export PDF',

    'admin' => [
        'title' => 'Task Planning Departments',
        'note' => 'Departments that appear as section headings on the monthly task board.',
        'name' => 'Department name',
        'add' => 'Add department',
        'added' => 'Department added.',
        'saved' => 'Department saved.',
        'removed' => 'Department removed.',
        'active' => 'Active',
        'has_tasks' => ':count tasks',
        'delete_blocked' => 'This department still has tasks. Deactivate it to hide it instead.',
        'pic_note' => 'The PIC list for Action By and Monitor By comes from the Critical Data PICs, managed in Admin Panel → Settings.',
    ],
];
