<?php

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => env('JWT_TTL', 180), // minutes (3 hours)
    'refresh_ttl' => env('JWT_REFRESH_TTL', 4320), // 3 days default
    'algo' => env('JWT_ALGO', 'HS256'),
    'required_claims' => ['iss','iat','exp','nbf','sub','jti'],
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
];
