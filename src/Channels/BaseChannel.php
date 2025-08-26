<?php

declare(strict_types=1);

namespace NotifyManager\Channels;

use NotifyManager\Contracts\NotificationChannelInterface;
use NotifyManager\DTOs\NotificationDTO;

abstract class BaseChannel implements NotificationChannelInterface
{
    public function __construct(
        protected array $config = []
    ) {}

    public function supports(NotificationDTO $notification): bool
    {
        return true;
    }

    public function validate(NotificationDTO $notification): bool
    {
        return ! empty($notification->recipient) && ! empty($notification->message);
    }

    public function getCostPerMessage(): float
    {
        return $this->config['cost_per_message'] ?? 0.01;
    }

    abstract public function send(NotificationDTO $notification): bool;

    abstract public function getName(): string;
}
