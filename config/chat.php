<?php

return [
    // Sets the daily limit of messages a user can send
    'max_requests' => 100,

    // Alerts the user about their remaining message quota at these levels
    'remaining_requests_alert_levels' => [10, 25, 50], // Alerts when remaining messages hit these numbers.

    // The maximum character count allowed per user message to maintain manageable conversation lengths
    'max_message_length' => 4096,

    // Note: 'max_requests' can be adjusted on a per-user basis
];
