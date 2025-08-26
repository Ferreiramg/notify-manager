<?php

declare(strict_types=1);

use Carbon\Carbon;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\DTOs\NotificationRuleDTO;

test('can create notification DTO with all parameters', function () {
    $scheduledAt = Carbon::now()->addHour();

    $notification = new NotificationDTO(
        id: 'test-123',
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message',
        metadata: ['key' => 'value'],
        subject: 'Test Subject',
        scheduledAt: $scheduledAt,
        priority: 2,
        tags: ['urgent', 'test'],
        template: 'welcome',
        templateData: ['name' => 'John']
    );

    expect($notification->id)->toBe('test-123');
    expect($notification->channel)->toBe('email');
    expect($notification->recipient)->toBe('test@example.com');
    expect($notification->message)->toBe('Test message');
    expect($notification->metadata)->toBe(['key' => 'value']);
    expect($notification->subject)->toBe('Test Subject');
    expect($notification->scheduledAt)->toBe($scheduledAt);
    expect($notification->priority)->toBe(2);
    expect($notification->tags)->toBe(['urgent', 'test']);
    expect($notification->template)->toBe('welcome');
    expect($notification->templateData)->toBe(['name' => 'John']);
});

test('can create notification DTO using create method', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Hello world',
        options: [
            'subject' => 'Important',
            'priority' => 3,
            'tags' => ['announcement'],
            'metadata' => ['source' => 'api'],
        ]
    );

    expect($notification->channel)->toBe('slack');
    expect($notification->recipient)->toBe('#general');
    expect($notification->message)->toBe('Hello world');
    expect($notification->subject)->toBe('Important');
    expect($notification->priority)->toBe(3);
    expect($notification->tags)->toBe(['announcement']);
    expect($notification->metadata)->toBe(['source' => 'api']);
    expect($notification->id)->not->toBeEmpty();
});

test('notification DTO create method generates unique IDs', function () {
    $notification1 = NotificationDTO::create('email', 'test1@example.com', 'Message 1');
    $notification2 = NotificationDTO::create('email', 'test2@example.com', 'Message 2');

    expect($notification1->id)->not->toBe($notification2->id);
});

test('notification DTO can be converted to array', function () {
    $scheduledAt = Carbon::now()->addHour();

    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message',
        options: [
            'subject' => 'Test',
            'scheduled_at' => $scheduledAt,
            'priority' => 2,
            'tags' => ['test'],
            'template' => 'welcome',
            'template_data' => ['name' => 'John'],
            'metadata' => ['key' => 'value'],
        ]
    );

    $array = $notification->toArray();

    expect($array)->toHaveKey('id');
    expect($array['channel'])->toBe('email');
    expect($array['recipient'])->toBe('test@example.com');
    expect($array['message'])->toBe('Test message');
    expect($array['subject'])->toBe('Test');
    expect($array['scheduled_at'])->toBe($scheduledAt->toISOString());
    expect($array['priority'])->toBe(2);
    expect($array['tags'])->toBe(['test']);
    expect($array['template'])->toBe('welcome');
    expect($array['template_data'])->toBe(['name' => 'John']);
    expect($array['metadata'])->toBe(['key' => 'value']);
});

test('can create notification rule DTO with all parameters', function () {
    $startDate = Carbon::now();
    $endDate = Carbon::now()->addMonths(6);

    $rule = new NotificationRuleDTO(
        name: 'Test Rule',
        channel: 'email',
        conditions: [['field' => 'priority', 'operator' => '>=', 'value' => 2]],
        isActive: true,
        startDate: $startDate,
        endDate: $endDate,
        maxSendsPerDay: 10,
        maxSendsPerHour: 2,
        allowedDays: [1, 2, 3, 4, 5],
        allowedHours: [9, 10, 11, 14, 15, 16, 17],
        priority: 1,
        metadata: ['type' => 'business']
    );

    expect($rule->name)->toBe('Test Rule');
    expect($rule->channel)->toBe('email');
    expect($rule->conditions)->toBe([['field' => 'priority', 'operator' => '>=', 'value' => 2]]);
    expect($rule->isActive)->toBeTrue();
    expect($rule->startDate)->toBe($startDate);
    expect($rule->endDate)->toBe($endDate);
    expect($rule->maxSendsPerDay)->toBe(10);
    expect($rule->maxSendsPerHour)->toBe(2);
    expect($rule->allowedDays)->toBe([1, 2, 3, 4, 5]);
    expect($rule->allowedHours)->toBe([9, 10, 11, 14, 15, 16, 17]);
    expect($rule->priority)->toBe(1);
    expect($rule->metadata)->toBe(['type' => 'business']);
});

test('can create notification rule DTO using create method', function () {
    $rule = NotificationRuleDTO::create(
        name: 'Simple Rule',
        channel: 'slack',
        conditions: [['field' => 'tags', 'operator' => 'contains', 'value' => 'urgent']],
        options: [
            'max_sends_per_day' => 5,
            'allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
            'priority' => 2,
        ]
    );

    expect($rule->name)->toBe('Simple Rule');
    expect($rule->channel)->toBe('slack');
    expect($rule->conditions)->toBe([['field' => 'tags', 'operator' => 'contains', 'value' => 'urgent']]);
    expect($rule->maxSendsPerDay)->toBe(5);
    expect($rule->allowedHours)->toBe([9, 10, 11, 12, 13, 14, 15, 16, 17]);
    expect($rule->priority)->toBe(2);
    expect($rule->isActive)->toBeTrue(); // Default value
});

test('notification rule DTO can be converted to array', function () {
    $startDate = Carbon::now();
    $endDate = Carbon::now()->addMonths(3);

    $rule = NotificationRuleDTO::create(
        name: 'Array Test Rule',
        channel: 'telegram',
        conditions: [['field' => 'recipient', 'operator' => 'in', 'value' => ['123', '456']]],
        options: [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'max_sends_per_day' => 3,
            'max_sends_per_hour' => 1,
            'allowed_days' => [1, 2, 3, 4, 5],
            'allowed_hours' => [9, 17],
            'priority' => 3,
            'metadata' => ['department' => 'marketing'],
        ]
    );

    $array = $rule->toArray();

    expect($array['name'])->toBe('Array Test Rule');
    expect($array['channel'])->toBe('telegram');
    expect($array['conditions'])->toBe([['field' => 'recipient', 'operator' => 'in', 'value' => ['123', '456']]]);
    expect($array['is_active'])->toBeTrue();
    expect($array['start_date'])->toBe($startDate->toISOString());
    expect($array['end_date'])->toBe($endDate->toISOString());
    expect($array['max_sends_per_day'])->toBe(3);
    expect($array['max_sends_per_hour'])->toBe(1);
    expect($array['allowed_days'])->toBe([1, 2, 3, 4, 5]);
    expect($array['allowed_hours'])->toBe([9, 17]);
    expect($array['priority'])->toBe(3);
    expect($array['metadata'])->toBe(['department' => 'marketing']);
});
