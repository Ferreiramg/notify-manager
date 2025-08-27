<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Notification Channel
    |--------------------------------------------------------------------------
    |
    | The default channel to use when sending notifications if none is specified.
    |
    */
    'default_channel' => env('NOTIFY_MANAGER_DEFAULT_CHANNEL', 'email'),

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the notification channels for your application.
    | You can create custom channels by implementing the NotificationChannelInterface.
    |
    */
    'channels' => [
        // Example configurations - implement these channels in your application
        // 'email' => \App\NotificationChannels\EmailChannel::class,
        // 'telegram' => \App\NotificationChannels\TelegramChannel::class,
        // 'slack' => \App\NotificationChannels\SlackChannel::class,
        // 'whatsapp' => \App\NotificationChannels\WhatsAppChannel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monetization Settings
    |--------------------------------------------------------------------------
    |
    | Configure the monetization settings for notification sending.
    |
    */
    'monetization' => [
        'enabled' => env('NOTIFY_MANAGER_MONETIZATION_ENABLED', true),
        'currency' => env('NOTIFY_MANAGER_CURRENCY', 'USD'),
        'default_cost_per_message' => env('NOTIFY_MANAGER_DEFAULT_COST', 0.01),
        'priority_multipliers' => [
            1 => 1.0,   // Low priority
            2 => 1.5,   // Normal priority
            3 => 2.0,   // High priority
        ],
        'length_multiplier_threshold' => 160, // Characters
        'length_multiplier' => 1.2, // 20% extra for long messages
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configure how notification activities should be logged.
    |
    */
    'logging' => [
        'enabled' => env('NOTIFY_MANAGER_LOGGING_ENABLED', true),
        'log_successful_sends' => env('NOTIFY_MANAGER_LOG_SUCCESS', true),
        'log_failed_sends' => env('NOTIFY_MANAGER_LOG_FAILURES', true),
        'log_blocked_sends' => env('NOTIFY_MANAGER_LOG_BLOCKED', true),
        'retention_days' => env('NOTIFY_MANAGER_LOG_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default rate limiting settings that can be overridden by specific rules.
    |
    */
    'rate_limiting' => [
        'default_max_per_hour' => env('NOTIFY_MANAGER_MAX_PER_HOUR', 100),
        'default_max_per_day' => env('NOTIFY_MANAGER_MAX_PER_DAY', 1000),
        'enable_global_limits' => env('NOTIFY_MANAGER_GLOBAL_LIMITS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for asynchronous notification processing.
    |
    */
    'queue' => [
        'enabled' => env('NOTIFY_MANAGER_QUEUE_ENABLED', false),
        'connection' => env('NOTIFY_MANAGER_QUEUE_CONNECTION', 'default'),
        'queue_name' => env('NOTIFY_MANAGER_QUEUE_NAME', 'notifications'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Settings
    |--------------------------------------------------------------------------
    |
    | Configure notification template settings. Templates should be stored
    | in resources/views/notifications/ and can be referenced by name.
    | Example: 'welcome' will look for resources/views/notifications/welcome.blade.php
    |
    */
    'templates' => [
        'path' => resource_path('views/notifications'),
        'cache_enabled' => env('NOTIFY_MANAGER_TEMPLATE_CACHE', true),
        'cache_ttl' => env('NOTIFY_MANAGER_TEMPLATE_CACHE_TTL', 3600), // 1 hour
    ],
];
