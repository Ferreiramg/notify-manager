<?php

declare(strict_types=1);

namespace NotifyManager\Services;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\Jobs\SendNotificationJob;

class QueueService
{
    public function __construct(
        private readonly QueueFactory $queueFactory,
        private readonly array $config
    ) {}

    public function dispatch(NotificationDTO $notification, ?int $delay = null): void
    {
        if (! $this->config['enabled']) {
            throw new \RuntimeException('Queue is not enabled in configuration');
        }

        $job = new SendNotificationJob($notification);

        $this->queueFactory
            ->connection($this->config['connection'])
            ->pushOn($this->config['queue_name'], $job, $delay);
    }

    public function dispatchDelayed(NotificationDTO $notification, int $delaySeconds): void
    {
        $this->dispatch($notification, $delaySeconds);
    }

    public function dispatchAt(NotificationDTO $notification, \DateTimeInterface $when): void
    {
        $delay = max(0, $when->getTimestamp() - time());
        $this->dispatch($notification, $delay);
    }

    public function isEnabled(): bool
    {
        return $this->config['enabled'];
    }

    public function getConnection(): string
    {
        return $this->config['connection'];
    }

    public function getQueueName(): string
    {
        return $this->config['queue_name'];
    }
}
