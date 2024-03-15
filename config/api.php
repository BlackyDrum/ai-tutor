<?php

return [
    'url' => env('CONVERSAITION_URL'),

    'username' => env('CONVERSAITION_USERNAME'),

    'password' => env('CONVERSAITION_PASSWORD'),

    'scope' => env('CONVERSAITION_SCOPE'),

    'grant_type' => env('CONVERSAITION_GRANT_TYPE'),

    'client_id' => env('CONVERSAITION_CLIENT_ID'),

    'client_secret' => env('CONVERSAITION_CLIENT_SECRET'),

    'auth_key' => env('API_AUTH_KEY'),

    'openai_language_model' => env('OPENAI_LANGUAGE_MODEL'),

    'token_expiration' => 20, // Expiration time of auth tokens after creation (in seconds)

    'max_requests' => 100, // Maximum number of messages per day per user (default value)

    'remaining_requests_alert_levels' => [10, 25, 50], // Show info when the user has n messages left for the day

    'max_message_length' => 4096, // Maximum number of characters for a single user message
];
