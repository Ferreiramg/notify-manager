<?php

declare(strict_types=1);

namespace NotifyManager\Tests\Channels;

use NotifyManager\Channels\BaseChannel;
use NotifyManager\DTOs\NotificationDTO;

class MockWhatsAppChannel extends BaseChannel
{
    private bool $shouldFail = false;

    private array $sentNotifications = [];

    public function __construct(array $config = [], bool $shouldFail = false)
    {
        parent::__construct($config);
        $this->shouldFail = $shouldFail;
    }

    public function getName(): string
    {
        return 'whatsapp';
    }

    public function send(NotificationDTO $notification): bool
    {
        if ($this->shouldFail) {
            return false;
        }

        $this->sentNotifications[] = $notification;

        return true;
    }

    public function supports(NotificationDTO $notification): bool
    {
        return $notification->channel === 'whatsapp';
    }

    public function validate(NotificationDTO $notification): bool
    {
        // WhatsApp numbers should start with + and have at least 10 digits
        return parent::validate($notification) &&
               preg_match('/^\+\d{10,}$/', $notification->recipient);
    }

    public function getCostPerMessage(): float
    {
        return $this->config['cost_per_message'] ?? 0.005;
    }

    public function getSentNotifications(): array
    {
        return $this->sentNotifications;
    }

    public function reset(): void
    {
        $this->sentNotifications = [];
    }
}
