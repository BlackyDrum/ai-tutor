<?php

return [
    'chroma_host' => env('CHROMA_HOST', 'http://localhost'),

    'chroma_port' => env('CHROMA_PORT', 8000),

    'chroma_database' => env('CHROMA_DATABASE', 'new_database'),

    'chroma_tenant' => env('CHROMA_TENANT', 'new_tenant'),

    'chroma_server_auth_credentials' => env('CHROMA_SERVER_AUTH_CREDENTIALS'),

    'chroma_server_auth_credentials_provider' => env('CHROMA_SERVER_AUTH_CREDENTIALS_PROVIDER'),

    'chroma_server_auth_provider' => env('CHROMA_SERVER_AUTH_PROVIDER'),

    'jina_api_key' => env('JINA_API_KEY'),

    'max_document_results' => 5, // Maximum number of documents that should be returned after querying
];
