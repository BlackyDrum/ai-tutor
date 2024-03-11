<?php

return [
    'url' => env('API_URL'),

    'username' => env('API_USERNAME'),

    'password' => env('API_PASSWORD'),

    'scope' => env('API_SCOPE'),

    'grant_type' => env('API_GRANT_TYPE'),

    'client_id' => env('API_CLIENT_ID'),

    'client_secret' => env('API_CLIENT_SECRET'),

    'auth_key' => env('API_AUTH_KEY'),

    'token_expiration' => 20, // Expiration time of auth tokens after creation (in seconds)

    'max_requests' => 100, // Maximum number of messages per day per user (default value)

    'remaining_requests_alert_levels' => [10, 25, 50], // Show info when the user has n messages left for the day

    'max_message_length' => 4096, // Maximum number of characters for a single user message
];
