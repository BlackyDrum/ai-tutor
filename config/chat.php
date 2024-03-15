<?php

return [
    'max_requests' => 100, // Maximum number of messages per day per user (default value)

    'temperature' => 0.7, // Determinacy of agent answers per user (default value)

    'max_tokens' => 1000, // Maximum number of tokens for a generated response per user (default value)

    'remaining_requests_alert_levels' => [10, 25, 50], // Show info when the user has n messages left for the day

    'max_message_length' => 4096, // Maximum number of characters for a single user message
];
