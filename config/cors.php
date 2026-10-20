<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://soundofiwu.himaif-iwu.com', // Domain Frontend Production
        'http://localhost:5173',            // Frontend Vite Localhost
        'http://localhost:3000',
    ],
    'allowed_origins_patterns' => [
        '#^https://.*\.himaif-iwu\.com$#',   // Menerima semua subdomain himaif-iwu
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
