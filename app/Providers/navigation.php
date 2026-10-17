<?php

/*
 * Single source of truth for the sidebar AND for who may open each page.
 * To add, remove or restrict a page, change it here only.
 * 'routes' (optional) lists every route name guarded by the same rule,
 * for example the buttons (POST/PATCH) that belong to a page.
 * Roles: admin, staff, representative, viewer
 */
return [
    'brand' => [
        'mark'     => 'U',
        'title'    => 'Umrah Hotel',
        'subtitle' => 'Management office',
    ],

    'items' => [
        ['route' => 'dashboard',         'label' => 'Dashboard',            'icon' => '⌂', 'roles' => ['admin', 'staff', 'representative', 'viewer'], 'menu_hidden_for' => ['representative']],
        ['route' => 'hotels',            'label' => 'Hotels',               'icon' => 'H', 'roles' => ['admin', 'staff', 'viewer'], 'routes' => ['hotels', 'bookings.index']],
        ['route' => 'bookings.create',   'label' => 'New booking',          'icon' => '＋', 'roles' => ['admin', 'staff'], 'routes' => ['bookings.create', 'bookings.store', 'bookings.edit', 'bookings.update']],
        ['route' => 'representative',    'label' => 'Hotel representative', 'icon' => '✓', 'roles' => ['admin', 'staff', 'representative'], 'routes' => ['representative', 'representative.update']],
        ['route' => 'companies',         'label' => 'Companies',            'icon' => '▤', 'roles' => ['admin', 'staff', 'viewer'], 'routes' => ['companies']],
        ['route' => 'accounts',          'label' => 'All accounts',         'icon' => '▤', 'roles' => ['admin', 'staff', 'viewer']],
        ['route' => 'company-account',   'label' => 'Company account',      'icon' => '▧', 'roles' => ['admin', 'staff', 'viewer']],
        ['route' => 'payments',          'label' => 'Payments',             'icon' => '$', 'roles' => ['admin', 'staff'], 'routes' => ['payments', 'payments.store']],
        ['route' => 'other-charges',     'label' => 'Other charges',        'icon' => '＋', 'roles' => ['admin', 'staff'], 'routes' => ['other-charges', 'other-charges.store']],
        ['route' => 'reports',           'label' => 'Reports',              'icon' => '▥', 'roles' => ['admin', 'staff', 'viewer'], 'routes' => ['reports', 'reports.csv']],
        ['route' => 'users.index',       'label' => 'Users',                'icon' => '◉', 'roles' => ['admin'], 'routes' => ['users.index', 'users.update', 'users.password']],
        ['route' => 'audit',             'label' => 'Activity log',         'icon' => '☷', 'roles' => ['admin']],
        ['route' => 'settings',          'label' => 'Settings',             'icon' => '⚙', 'roles' => ['admin'], 'routes' => ['settings', 'settings.update']],

        // Hidden rules: not shown in the sidebar, they only guard buttons and forms.
        ['route' => 'hotels.create',     'hidden' => true, 'roles' => ['admin'],          'routes' => ['hotels.create', 'hotels.store', 'hotels.manage', 'hotels.update', 'rooms.store', 'rooms.update']],
        ['route' => 'companies.store',   'hidden' => true, 'roles' => ['admin', 'staff'], 'routes' => ['companies.store', 'companies.update']],
        ['route' => 'companies.destroy', 'hidden' => true, 'roles' => ['admin'],          'routes' => ['companies.destroy']],
        ['route' => 'payments.destroy',  'hidden' => true, 'roles' => ['admin'],          'routes' => ['payments.destroy', 'other-charges.destroy']],
    ],
];
