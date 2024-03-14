<?php

return [
    'host' => env('CHROMA_HOST', 'http://localhost'),

    'port' => env('CHROMA_PORT', 8000),

    'database' => env('CHROMA_DATABASE', 'new_database'),

    'tenant' => env('CHROMA_TENANT', 'new_tenant'),

    'embedding_function' => env('CHROMA_EMBEDDING_FUNCTION', 'jina'),

    'server_auth_credentials' => env('CHROMA_SERVER_AUTH_CREDENTIALS'),

    'jina_api_key' => env('JINA_API_KEY'),

    'openai_api_key' => env('OPENAI_API_KEY'),
];
