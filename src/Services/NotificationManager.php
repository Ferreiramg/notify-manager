<?php

declare(strict_types=1);

namespace NotifyManager\Services;

use Illuminate\Support\Facades\Log;
use NotifyManager\Contracts\NotificationChannelInterface;
use NotifyManager\Contracts\NotificationManagerInterface;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\DTOs\NotificationRuleDTO;
use NotifyManager\Models\NotificationLog;
use NotifyManager\Models\NotificationRule;
use NotifyManager\Models\NotificationUsage;

final class NotificationManager implements NotificationManagerInterface
{
    private array $channels = [];

    private string $rulesError = '';

    public function __construct(
        private ?TemplateService $templateService = null,
        private ?QueueService $queueService = null
    ) {}

    public function sendAsync(NotificationDTO $notification, ?int $delay = null): void
    {
        if (! $this->queueService || ! $this->queueService->isEnabled()) {
            throw new \RuntimeException('Queue service is not enabled');
        }

        $this->queueService->dispatch($notification, $delay);
    }

    public function sendAt(NotificationDTO $notification, \DateTimeInterface $when): void
    {
        if (! $this->queueService || ! $this->queueService->isEnabled()) {
            throw new \RuntimeException('Queue service is not enabled');
        }

        $this->queueService->dispatchAt($notification, $when);
    }

    public function send(NotificationDTO $notification): bool
    {
        try {
            if (! $this->shouldSend($notification)) {
                $this->logActivity($notification, 'blocked', $this->rulesError);

                return false;
            }

            $channel = $this->getChannel($notification->channel);
            if (! $channel) {
                $this->logActivity($notification, 'failed', 'Channel not found');

                return false;
            }

            if (! $channel->supports($notification) || ! $channel->validate($notification)) {
                $this->logActivity($notification, 'failed', 'Notification not supported or invalid');

                return false;
            }

            // Process template if available
            if ($this->templateService && $notification->template) {
                $renderedMessage = $this->templateService->render($notification);
                $notification = new NotificationDTO(
                    id: $notification->id,
                    channel: $notification->channel,
                    recipient: $notification->recipient,
                    message: $renderedMessage,
                    subject: $notification->subject,
                    priority: $notification->priority,
                    tags: $notification->tags,
                    metadata: $notification->metadata,
                    template: $notification->template,
                    templateData: $notification->templateData
                );
            }

            $cost = $this->calculateCost($notification);
            $this->recordUsage($notification, $cost);

            $result = $channel->send($notification);

            $this->logActivity(
                $notification,
                $result ? 'sent' : 'failed',
                $result ? 'Successfully sent' : 'Failed to send'
            );

            return $result;
        } catch (\Throwable $e) {
            Log::error('Notification send failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->logActivity($notification, 'error', $e->getMessage());

            return false;
        }
    }

    public function registerChannel(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
    }

    public function getChannel(string $name): ?NotificationChannelInterface
    {
        return $this->channels[$name] ?? null;
    }

    public function createRule(NotificationRuleDTO $rule): int
    {
        try {
            $rule = NotificationRule::create($rule->toArray());

            return $rule->id ?? 0;
        } catch (\Throwable $e) {
            Log::error('Failed to create notification rule', [
                'rule_name' => $rule->name,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    public function shouldSend(NotificationDTO $notification): bool
    {
        $rules = ! empty($notification->rules) ? $notification->rules : NotificationRule::where('channel', $notification->channel)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if (! $this->evaluateRule($rule, $notification)) {
                return false;
            }
        }

        return true;
    }

    public function calculateCost(NotificationDTO $notification): float
    {
        $channel = $this->getChannel($notification->channel);
        if (! $channel) {
            return 0.0;
        }

        $baseCost = $channel->getCostPerMessage();

        // Apply priority multiplier
        $priorityMultiplier = match ($notification->priority) {
            1 => 1.0,
            2 => 1.5,
            3 => 2.0,
            default => 1.0,
        };

        // Apply length multiplier for long messages
        $lengthMultiplier = strlen($notification->message) > 160 ? 1.2 : 1.0;

        return $baseCost * $priorityMultiplier * $lengthMultiplier;
    }

    public function logActivity(NotificationDTO $notification, string $status, ?string $response = null): void
    {
        try {
            NotificationLog::create([
                'notification_id' => $notification->id,
                'channel' => $notification->channel,
                'recipient' => $notification->recipient,
                'message' => $notification->message,
                'status' => $status,
                'response' => $response,
                'metadata' => $notification->metadata,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log notification activity', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function evaluateRule(NotificationRule $rule, NotificationDTO $notification): bool
    {
        // Check date range
        if ($rule->start_date && now()->lt($rule->start_date)) {
            $this->rulesError = "Current date is before the rule's start date.";

            return false;
        }

        if ($rule->end_date && now()->gt($rule->end_date)) {
            $this->rulesError = "Current date is after the rule's end date.";

            return false;
        }

        // Check allowed days
        if (! empty($rule->allowed_days) && ! in_array(now()->dayOfWeek, $rule->allowed_days)) {

            $this->rulesError = "Today is not in the rule's allowed days. Pemitted days: ".implode(', ', $rule->allowed_days).'. Today is: '.now()->dayOfWeek.'.';

            return false;
        }

        // Check allowed hours
        if (! empty($rule->allowed_hours) && ! in_array(now()->hour, $rule->allowed_hours)) {
            $this->rulesError = "Current hour is not in the rule's allowed hours. Permitted hours: ".implode(', ', $rule->allowed_hours).'. Current hour is: '.now()->hour.'.';

            return false;
        }

        // Check daily limit
        if ($rule->max_sends_per_day > 0) {
            $todaySends = NotificationLog::where('channel', $notification->channel)
                ->where('recipient', $notification->recipient)
                ->whereDate('sent_at', today())
                ->count();

            if ($todaySends >= $rule->max_sends_per_day) {
                $this->rulesError = 'Daily send limit reached. Max allowed: '.$rule->max_sends_per_day.', sent today: '.$todaySends.'.';

                return false;
            }
        }

        // Check hourly limit
        if ($rule->max_sends_per_hour > 0) {
            $hourSends = NotificationLog::where('channel', $notification->channel)
                ->where('recipient', $notification->recipient)
                ->where('sent_at', '>=', now()->subHour())
                ->count();

            if ($hourSends >= $rule->max_sends_per_hour) {
                $this->rulesError = 'Hourly send limit reached. Max allowed: '.$rule->max_sends_per_hour.', sent in the last hour: '.$hourSends.'.';

                return false;
            }
        }

        // Evaluate custom conditions
        return $this->evaluateConditions($rule->conditions, $notification);
    }

    private function evaluateConditions(array $conditions, NotificationDTO $notification): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? '';

            $notificationValue = $this->getNotificationFieldValue($notification, $field);

            if (! $this->compareValues($notificationValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    private function getNotificationFieldValue(NotificationDTO $notification, string $field): mixed
    {
        return match ($field) {
            'recipient' => $notification->recipient,
            'message' => $notification->message,
            'priority' => $notification->priority,
            'subject' => $notification->subject,
            'tags' => $notification->tags,
            default => $notification->metadata[$field] ?? null,
        };
    }

    private function compareValues(mixed $left, string $operator, mixed $right): bool
    {
        return match ($operator) {
            '=' => $left == $right,
            '!=' => $left != $right,
            '>' => $left > $right,
            '<' => $left < $right,
            '>=' => $left >= $right,
            '<=' => $left <= $right,
            'contains' => str_contains(implode(', ', (array) $left), (string) $right),
            'not_contains' => ! str_contains(implode(', ', (array) $left), (string) $right),
            'in' => in_array($left, (array) $right),
            'not_in' => ! in_array($left, (array) $right),
            default => false,
        };
    }

    private function recordUsage(NotificationDTO $notification, float $cost): void
    {
        try {
            NotificationUsage::create([
                'notification_id' => $notification->id,
                'channel' => $notification->channel,
                'cost' => $cost,
                'used_at' => now(),
                'metadata' => [
                    'recipient' => $notification->recipient,
                    'priority' => $notification->priority,
                    'message_length' => strlen($notification->message),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to record notification usage', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
