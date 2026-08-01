<?php

return [
    'page_title' => 'Master List of Project',
    'page_subtitle' => 'Full project list by service category',
    'nav' => 'Projects',
    'all' => 'All Projects',

    'search' => 'Search projects…',
    'filter' => 'Filter',
    'export' => 'Export',
    'view_sheet' => 'View Google Sheet',
    'sync_now' => 'Sync Now',
    'admin_only' => 'Admin only',

    'tile' => [
        'total' => 'Total Projects',
        'total_note' => 'All categories',
        'closed' => 'Closed Projects',
        'of_total' => ':pct% of total',
    ],

    'col' => [
        'code' => 'Project Code',
        'date' => 'Date',
        'client' => 'Client Name',
        'pic' => 'PIC Sales',
        'service' => 'Type Of Project',
        'phone' => 'Phone / Whatsapp',
        'address' => 'Address',
        'email' => 'Email',
        'contract' => 'Contract Amount',
        'vo' => 'Variation Order (VO)',
        'status' => 'Status',
    ],

    'field' => [
        'code' => 'Project Code',
        'client_name' => 'Client Name',
    ],

    'status' => [
        'quotation' => 'Quotation',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'closed' => 'Closed',
    ],

    'showing' => 'Showing :from to :to of :total entries',
    'per_page' => 'Rows per page',
    'empty' => 'No projects found.',
    'empty_hint' => 'Project data is entered in the Google Sheet, then synced by an Admin.',
    'no_sheet' => 'The project Google Sheet is not connected yet. An Admin can connect it in the Admin Panel.',

    'sync' => [
        'read_failed' => 'The sheet could not be read: :message',
        'missing_column' => 'A required column is unmapped: :field',
        'done' => ':written projects synced, :skipped rows skipped.',
        'nothing' => 'No valid project rows found. Check the header row and column mapping.',
        'unknown_services' => 'Unrecognised project types were skipped: :names',
    ],

    'source_note' => 'Data is entered in the Google Sheet and shown here after syncing. This dashboard is view-only.',
];
