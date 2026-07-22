<?php

return [
    'access_token_ttl_minutes' => (int) env('MOBILE_API_ACCESS_TOKEN_TTL', 60),
    'refresh_token_ttl_days' => (int) env('MOBILE_API_REFRESH_TOKEN_TTL', 30),
    'two_factor_challenge_ttl_minutes' => (int) env('MOBILE_API_2FA_CHALLENGE_TTL', 5),
];
