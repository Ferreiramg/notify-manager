<?php

declare(strict_types=1);

namespace NotifyManager\Contracts;

use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\DTOs\NotificationRuleDTO;

interface NotificationManagerInterface
{
    /**
     * Send a notification through specified channel
     */
    public function send(NotificationDTO $notification): bool;

    /**
     * Register a new notification channel
     */
    public function registerChannel(string $name, NotificationChannelInterface $channel): void;

    /**
     * Get registered channel by name
     */
    public function getChannel(string $name): ?NotificationChannelInterface;

    /**
     * Create a new notification rule
     */
    public function createRule(NotificationRuleDTO $rule): bool;

    /**
     * Check if notification should be sent based on rules
     */
    public function shouldSend(NotificationDTO $notification): bool;

    /**
     * Get notification cost for monetization
     */
    public function calculateCost(NotificationDTO $notification): float;

    /**
     * Log notification activity
     */
    public function logActivity(NotificationDTO $notification, string $status, ?string $response = null): void;

    /**
     * Send notification asynchronously via queue
     */
    public function sendAsync(NotificationDTO $notification, ?int $delay = null): void;

    /**
     * Send notification at a specific time
     */
    public function sendAt(NotificationDTO $notification, \DateTimeInterface $when): void;
}
