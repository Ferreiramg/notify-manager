<?php

declare(strict_types=1);

namespace NotifyManager\Contracts;

use NotifyManager\DTOs\NotificationDTO;

interface NotificationChannelInterface
{
    /**
     * Send notification through this channel
     */
    public function send(NotificationDTO $notification): bool;

    /**
     * Get channel name
     */
    public function getName(): string;

    /**
     * Check if channel supports the notification type
     */
    public function supports(NotificationDTO $notification): bool;

    /**
     * Get cost per message for this channel
     */
    public function getCostPerMessage(): float;

    /**
     * Validate notification format for this channel
     */
    public function validate(NotificationDTO $notification): bool;
}
