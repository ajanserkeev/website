<?php

return [

    'name' => env('BRAND_NAME', 'Tunduk Trips'),

    // Human-readable booking code prefix: TT-27-0142.
    'booking_prefix' => env('BRAND_BOOKING_PREFIX', 'TT'),

    // Tour dates, refund deadlines and operator communication run on Bishkek time.
    'timezone' => 'Asia/Bishkek',

    'currency' => 'USD',

];
