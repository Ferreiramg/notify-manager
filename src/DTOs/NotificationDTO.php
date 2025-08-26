<?php

declare(strict_types=1);

namespace NotifyManager\DTOs;

use Carbon\Carbon;

readonly class NotificationDTO
{
    public function __construct(
        public string $id,
        public string $channel,
        public string $recipient,
        public string $message,
        public array $metadata = [],
        public ?string $subject = null,
        public ?Carbon $scheduledAt = null,
        public int $priority = 1,
        public array $tags = [],
        public ?string $template = null,
        public array $templateData = [],
    ) {}

    public static function create(
        string $channel,
        string $recipient,
        string $message,
        array $options = []
    ): self {
        return new self(
            id: $options['id'] ?? uniqid('notification_', true),
            channel: $channel,
            recipient: $recipient,
            message: $message,
            metadata: $options['metadata'] ?? [],
            subject: $options['subject'] ?? null,
            scheduledAt: $options['scheduled_at'] ?? null,
            priority: $options['priority'] ?? 1,
            tags: $options['tags'] ?? [],
            template: $options['template'] ?? null,
            templateData: $options['template_data'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'subject' => $this->subject,
            'scheduled_at' => $this->scheduledAt?->toISOString(),
            'priority' => $this->priority,
            'tags' => $this->tags,
            'template' => $this->template,
            'template_data' => $this->templateData,
        ];
    }
}
