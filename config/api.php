<?php

return [
    'openai_embedding_model' => env('OPENAI_EMBEDDING_MODEL'),

    'openai_conversation_title_creator_model' => env('OPENAI_CONVERSATION_TITLE_CREATOR_MODEL'),

    'jina_embedding_model' => env('JINA_EMBEDDING_MODEL'),

    'jina_api_key' => env('JINA_API_KEY'),

    'openai_api_key' => env('OPENAI_API_KEY'),

    'token_expiration' => 20, // Expiration time of auth tokens after creation (in seconds)
];
