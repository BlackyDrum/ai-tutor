<?php

return [
    'chroma_host' => env('CHROMA_HOST', 'http://localhost'),

    'chroma_port' => env('CHROMA_PORT', 8000),

    'chroma_database' => env('CHROMA_DATABASE', 'new_database'),

    'chroma_tenant' => env('CHROMA_TENANT', 'new_tenant'),

    'chroma_embedding_function' => env('CHROMA_EMBEDDING_FUNCTION', 'jina'),

    'chroma_server_auth_credentials' => env('CHROMA_SERVER_AUTH_CREDENTIALS'),

    'chroma_server_auth_credentials_provider' => env('CHROMA_SERVER_AUTH_CREDENTIALS_PROVIDER'),

    'chroma_server_auth_provider' => env('CHROMA_SERVER_AUTH_PROVIDER'),

    'jina_api_key' => env('JINA_API_KEY'),

    'openai_api_key' => env('OPENAI_API_KEY'),
];
