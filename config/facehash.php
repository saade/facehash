<?php

return [
    'defaults' => [
        'size' => 40,
        'variant' => 'gradient',
        'initial' => true,
        'blink' => false,
    ],

    'colors' => ['#ec4899', '#f59e0b', '#3b82f6', '#f97316', '#10b981'],

    'route' => [
        'enabled' => false,
        'prefix' => 'facehash',
        'middleware' => ['web'],
        'cache_control' => 'public, max-age=31536000, immutable',
    ],
];
