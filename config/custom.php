<?php

return [
    'demo_mode' => false,
    'blog_url' => env('BLOG_URL'),
    'store_url' => env('STORE_URL', 'https://store.edilsonsantos.co.za'),
    // ES Journal is private: it is only linked from the admin dashboard, never from the public site
    'journal_url' => env('JOURNAL_URL', 'https://journal.edilsonsantos.co.za'),
    // Portfolio -> Themes: where the catalogue and packages are fetched from (server side),
    // where installed themes live (a volume in production), and whether unsigned packages install.
    'theme_store_url' => env('THEME_STORE_URL', env('STORE_URL', 'https://store.edilsonsantos.co.za')),
    'themes_path' => env('THEMES_PATH', storage_path('app/themes')),
    'themes_allow_unsigned' => (bool) env('THEMES_ALLOW_UNSIGNED', false),
    // Shown by the frontend theme; leave empty to hide the tile or line
    'availability' => env('PORTFOLIO_AVAILABILITY'),
    'timezone' => env('PORTFOLIO_TIMEZONE', 'UTC'),
    'time_note' => env('PORTFOLIO_TIME_NOTE'),
];
