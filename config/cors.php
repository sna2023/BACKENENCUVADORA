<?php

$envOrigins    = array_map('trim', explode(',', env('ALLOWED_ORIGINS', '')));
$frontendUrl   = env('FRONTEND_URL', '');
$localOrigins  = ['http://localhost:5173', 'http://localhost:5174'];

$allOrigins = array_values(array_unique(array_merge(
    $envOrigins,
    $frontendUrl ? [$frontendUrl] : [],
    $localOrigins,
)));

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => $allOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];
