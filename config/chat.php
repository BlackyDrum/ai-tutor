<?php

return [
    // Sets the daily limit of messages a user can send
    'max_requests' => 100,

    // Controls the variability in agent responses. Lower values produce more predictable responses
    'temperature' => 0.7,

    // Defines the upper limit of tokens the AI can generate for each response
    'max_response_tokens' => 1000,

    // Alerts the user about their remaining message quota at these levels
    'remaining_requests_alert_levels' => [10, 25, 50], // Alerts when remaining messages hit these numbers.

    // The maximum character count allowed per user message to maintain manageable conversation lengths
    'max_message_length' => 4096,

    // Limits the number of previous messages considered for context in an ongoing conversation,
    // optimizing performance and keeping control over the size of the context window
    'max_messages_included' => 12 // Include the last n messages for context.

    // Note: The settings for 'max_requests', 'temperature', and 'max_response_tokens'
    // can be adjusted on a per-user basis for greater personalization and control.
];
