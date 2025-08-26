<?php

declare(strict_types=1);

namespace NotifyManager\Facades;

use Illuminate\Support\Facades\Facade;
use NotifyManager\Contracts\NotificationManagerInterface;

/**
 * @method static bool send(\NotifyManager\DTOs\NotificationDTO $notification)
 * @method static void registerChannel(string $name, \NotifyManager\Contracts\NotificationChannelInterface $channel)
 * @method static \NotifyManager\Contracts\NotificationChannelInterface|null getChannel(string $name)
 * @method static bool createRule(\NotifyManager\DTOs\NotificationRuleDTO $rule)
 * @method static bool shouldSend(\NotifyManager\DTOs\NotificationDTO $notification)
 * @method static float calculateCost(\NotifyManager\DTOs\NotificationDTO $notification)
 * @method static void logActivity(\NotifyManager\DTOs\NotificationDTO $notification, string $status, string|null $response = null)
 */
final class NotifyManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationManagerInterface::class;
    }
}
