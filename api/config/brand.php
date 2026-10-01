<?php

return [

    'name' => env('BRAND_NAME', 'Tunduk Trips'),

    // Human-readable booking code prefix: TT-27-0142.
    'booking_prefix' => env('BRAND_BOOKING_PREFIX', 'TT'),

    // Tour dates, refund deadlines and operator communication run on Bishkek time.
    'timezone' => 'Asia/Bishkek',

    'currency' => 'USD',

    // Public Next.js site, for "Open on site" links in the admin.
    // Folder with demo photos for DemoCatalogSeeder (web/public/demo mounted in Docker); empty in CI.
    'demo_photos_path' => env('DEMO_PHOTOS_PATH'),

    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/'),

    // Where Laravel reaches the Next.js server to clear its catalog cache (inside Docker: http://web:3000),
    // and the shared secret (web REVALIDATE_SECRET). Empty secret: revalidation is off.
    'frontend_internal_url' => rtrim((string) env('FRONTEND_INTERNAL_URL', env('FRONTEND_URL', 'http://localhost:3000')), '/'),
    'frontend_revalidate_secret' => env('FRONTEND_REVALIDATE_SECRET'),

];
