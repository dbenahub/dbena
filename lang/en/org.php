<?php

return [
    'title' => 'Organizational Chart',
    'subtitle' => 'DBENA SDN BHD (1518035-A)',
    'nav' => 'Organizational Chart',

    'style' => [
        'executive' => 'Executive',
        'department' => 'Department',
        'support' => 'Support / Freelancer',
    ],

    'link' => [
        'solid' => 'Solid line',
        'dashed' => 'Dashed line',
    ],

    'view_only' => 'View only — only an admin can change this chart',
    'empty' => 'The organizational chart is still empty.',
    'empty_admin' => 'Press “Add box” to start building the chart.',

    'export' => 'Export PDF',
    'edit' => 'Arrange mode',
    'done' => 'Done arranging',

    'editor' => [
        'add' => 'Add box',
        'added' => 'A new box was added in the middle of the canvas.',
        'delete' => 'Delete box',
        'deleted' => 'The box and its lines were deleted.',
        'connect' => 'Connect line',
        'connect_hint' => 'Click the START box, then the END box.',
        'connect_from' => 'Selected: :name. Click a second box to connect.',
        'connected' => 'Line connected.',
        'connect_same' => 'A box cannot connect to itself.',
        'connect_exists' => 'That line already exists.',
        'cancel_connect' => 'Cancel connecting',
        'unlink' => 'Remove line',
        'unlinked' => 'Line removed.',
        'no_links' => 'This box has no lines.',
        'saved' => 'Chart saved.',
        'moved' => 'Position saved.',
        'panel' => 'Box details',
        'panel_none' => 'Click any box to edit its details.',
        'field_title' => 'Role / Department',
        'field_subtitle' => 'Middle line (e.g. Head of Dept.)',
        'field_height' => 'Height (px)',
        'field_name' => 'Name',
        'field_icon' => 'Icon',
        'field_style' => 'Box style',
        'field_width' => 'Width (px)',
        'links_here' => 'Lines on this box',
        'snap' => 'Snap to grid',
        'tidy' => 'Tidy positions',
        'tidied' => 'Positions snapped to the grid.',
        'drag_hint' => 'Drag any box to move it. Saved as soon as you let go.',
    ],
];
