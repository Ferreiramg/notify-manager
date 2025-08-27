<?php

declare(strict_types=1);

namespace NotifyManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NotifyManager\Contracts\NotificationManagerInterface;
use NotifyManager\DTOs\NotificationDTO;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public int $timeout = 30;

    public function __construct(
        public readonly NotificationDTO $notification
    ) {}

    public function handle(NotificationManagerInterface $notificationManager): void
    {
        $notificationManager->send($this->notification);
    }

    public function failed(?Throwable $exception): void
    {
        // Log the failure
        logger()->error('Failed to send notification', [
            'notification_id' => $this->notification->id,
            'channel' => $this->notification->channel,
            'recipient' => $this->notification->recipient,
            'exception' => $exception?->getMessage(),
        ]);

        // You could also fire an event here for custom handling
        // event(new NotificationFailedEvent($this->notification, $exception));
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10);
    }
}
