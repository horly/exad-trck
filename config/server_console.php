<?php

return [
    'enabled' => env('SERVER_CONSOLE_ENABLED', false),
    'gateway_url' => env('SERVER_CONSOLE_GATEWAY_URL', '/server-console/socket'),
    'ticket_secret' => env('SERVER_CONSOLE_TICKET_SECRET'),
    'ticket_ttl_seconds' => (int) env('SERVER_CONSOLE_TICKET_TTL', 30),
    'allowed_username' => env('SERVER_CONSOLE_ALLOWED_USERNAME', 'exad-tracking'),
];
