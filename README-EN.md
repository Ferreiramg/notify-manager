# NotifyManager

[![Tests](https://github.com/Ferreiramg/notify-manager/workflows/Tests/badge.svg)](https://github.com/Ferreiramg/notify-manager/actions)
[![Security](https://github.com/Ferreiramg/notify-manager/workflows/Security%20&%20Quality/badge.svg)](https://github.com/Ferreiramg/notify-manager/actions)
[![Latest Stable Version](https://poser.pugx.org/ferreiramg/notify-manager/v/stable)](https://packagist.org/packages/ferreiramg/notify-manager)
[![Total Downloads](https://poser.pugx.org/ferreiramg/notify-manager/downloads)](https://packagist.org/packages/ferreiramg/notify-manager)
[![License](https://poser.pugx.org/ferreiramg/notify-manager/license)](https://packagist.org/packages/ferreiramg/notify-manager)

A comprehensive Laravel package for notification management with dispatch rules, logging, monetization, and multi-channel support.

## Features

✨ **Multi-Channel Support**: Email, Telegram, Slack, WhatsApp  
📋 **Rule-Based Dispatching**: Complex conditions, time restrictions, rate limiting  
💰 **Monetization**: Cost calculation with priority and length multipliers  
📊 **Comprehensive Logging**: Track all notification activities  
🔧 **Modern PHP**: Built with PHP 8.3+ and readonly DTOs  
🧪 **Well Tested**: 74%+ test coverage with Pest PHP  

## Requirements

- PHP 8.3+
- Laravel 11.0+

## Installation

Install the package via Composer:

```bash
composer require ferreiramg/notify-manager
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="notify-manager-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="notify-manager-config"
```

## Quick Start

### Basic Usage

```php
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\Facades\NotifyManager;

// Send a simple notification
$notification = NotificationDTO::create(
    channel: 'email',
    recipient: 'user@example.com',
    message: 'Hello from NotifyManager!',
    subject: 'Welcome'
);

NotifyManager::send($notification);
```

### With Rules

```php
use NotifyManager\DTOs\NotificationRuleDTO;

// Create a rule
$rule = NotificationRuleDTO::create(
    name: 'Business Hours Only',
    channel: 'slack',
    conditions: [
        ['field' => 'user_type', 'operator' => '=', 'value' => 'premium']
    ],
    allowedHours: [9, 10, 11, 12, 13, 14, 15, 16, 17],
    maxSendsPerDay: 5
);

NotifyManager::createRule($rule);
```

### Custom Channels

```php
use NotifyManager\Channels\BaseChannel;

class CustomChannel extends BaseChannel
{
    public function send(NotificationDTO $notification): bool
    {
        // Your custom implementation
        return true;
    }
    
    public function validate(NotificationDTO $notification): bool
    {
        // Your validation logic
        return !empty($notification->recipient);
    }
    
    public function getName(): string
    {
        return 'custom';
    }
}

// Register the channel
NotifyManager::registerChannel('custom', new CustomChannel());
```

## Configuration

The configuration file provides extensive customization options:

```php
return [
    'channels' => [
        'email' => [
            'driver' => \NotifyManager\Channels\EmailChannel::class,
            'cost' => 0.01,
        ],
        'telegram' => [
            'driver' => \NotifyManager\Channels\TelegramChannel::class,
            'cost' => 0.05,
            'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        ],
    ],
    
    'monetization' => [
        'enabled' => true,
        'priority_multipliers' => [
            1 => 1.0,
            2 => 1.5,
            3 => 2.0,
        ],
        'length_multiplier_threshold' => 160,
        'length_multiplier' => 1.2,
    ],
    
    'logging' => [
        'enabled' => true,
        'log_level' => 'info',
    ],
];
```

## API Reference

### NotificationManager

```php
// Send notification
NotifyManager::send(NotificationDTO $notification): bool

// Calculate cost
NotifyManager::calculateCost(NotificationDTO $notification): float

// Register channel
NotifyManager::registerChannel(string $name, NotificationChannelInterface $channel): void

// Create rule
NotifyManager::createRule(NotificationRuleDTO $rule): NotificationRule

// Check if should send
NotifyManager::shouldSend(NotificationDTO $notification, NotificationRule $rule): bool
```

### DTOs

```php
// Notification DTO
NotificationDTO::create(
    channel: string,
    recipient: string,
    message: string,
    subject?: string,
    priority?: int,
    tags?: array,
    options?: array
);

// Rule DTO
NotificationRuleDTO::create(
    name: string,
    channel: string,
    conditions?: array,
    allowedDays?: array,
    allowedHours?: array,
    maxSendsPerDay?: int,
    maxSendsPerHour?: int,
    startDate?: Carbon,
    endDate?: Carbon,
    priority?: int,
    metadata?: array
);
```

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Check code style:

```bash
composer format
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail to luis@lpdeveloper.com.br.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Luís Paulo Ferreira](https://github.com/Ferreiramg)
- [All Contributors](../../contributors)

---

Made with ❤️ for the Laravel community
