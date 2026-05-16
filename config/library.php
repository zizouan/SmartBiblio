<?php

return [
    'auth' => [
        'access_token_ttl_minutes' => (int) env('ACCESS_TOKEN_TTL_MINUTES', 15),
        'refresh_token_ttl_days' => (int) env('REFRESH_TOKEN_TTL_DAYS', 7),
    ],
    'loans' => [
        'max_simultaneous' => (int) env('LOAN_MAX_SIMULTANEOUS', 3),
        'default_duration_days' => (int) env('LOAN_DEFAULT_DURATION_DAYS', 14),
    ],
];
