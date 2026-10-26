<?php

return [
    'demo_mode' => false,
    'blog_url' => env('BLOG_URL'),
    'store_url' => env('STORE_URL', 'https://store.edilsonsantos.co.za'),
    // Shown by the frontend theme; leave empty to hide the tile or line
    'availability' => env('PORTFOLIO_AVAILABILITY'),
    'timezone' => env('PORTFOLIO_TIMEZONE', 'UTC'),
    'time_note' => env('PORTFOLIO_TIME_NOTE'),
];
