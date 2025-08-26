<?php

declare(strict_types=1);

namespace NotifyManager\DTOs;

use Carbon\Carbon;

readonly class NotificationRuleDTO
{
    public function __construct(
        public string $name,
        public string $channel,
        public array $conditions,
        public bool $isActive = true,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null,
        public int $maxSendsPerDay = 0,
        public int $maxSendsPerHour = 0,
        public array $allowedDays = [],
        public array $allowedHours = [],
        public int $priority = 1,
        public array $metadata = [],
    ) {}

    public static function create(
        string $name,
        string $channel,
        array $conditions,
        array $options = []
    ): self {
        return new self(
            name: $name,
            channel: $channel,
            conditions: $conditions,
            isActive: $options['is_active'] ?? true,
            startDate: $options['start_date'] ?? null,
            endDate: $options['end_date'] ?? null,
            maxSendsPerDay: $options['max_sends_per_day'] ?? 0,
            maxSendsPerHour: $options['max_sends_per_hour'] ?? 0,
            allowedDays: $options['allowed_days'] ?? [],
            allowedHours: $options['allowed_hours'] ?? [],
            priority: $options['priority'] ?? 1,
            metadata: $options['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'channel' => $this->channel,
            'conditions' => $this->conditions,
            'is_active' => $this->isActive,
            'start_date' => $this->startDate?->toISOString(),
            'end_date' => $this->endDate?->toISOString(),
            'max_sends_per_day' => $this->maxSendsPerDay,
            'max_sends_per_hour' => $this->maxSendsPerHour,
            'allowed_days' => $this->allowedDays,
            'allowed_hours' => $this->allowedHours,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
        ];
    }
}
