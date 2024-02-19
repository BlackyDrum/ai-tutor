<?php

return [
    'url' => env('API_URL'),

    'username' => env('API_USERNAME'),

    'password' => env('API_PASSWORD'),

    'scope' => env('API_SCOPE'),

    'grant_type' => env('API_GRANT_TYPE'),

    'client_id' => env('API_CLIENT_ID'),

    'client_secret' => env('API_CLIENT_SECRET'),

    'max_tokens' => 1000, // max tokens per conversation

    'temperature' => 0.5,
];
