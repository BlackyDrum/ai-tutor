<?php

return [
    'auth_key' => env('API_AUTH_KEY'),

    'openai_language_model' => env('OPENAI_LANGUAGE_MODEL'),

    'jina_api_key' => env('JINA_API_KEY'),

    'openai_api_key' => env('OPENAI_API_KEY'),

    'token_expiration' => 20, // Expiration time of auth tokens after creation (in seconds)
];
