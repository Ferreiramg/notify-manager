<?php

declare(strict_types=1);

use NotifyManager\Models\NotificationLog;
use NotifyManager\Models\NotificationRule;
use NotifyManager\Models\NotificationUsage;

test('notification rule model has correct fillable attributes', function () {
    $rule = new NotificationRule;

    $expectedFillable = [
        'name',
        'channel',
        'conditions',
        'is_active',
        'start_date',
        'end_date',
        'max_sends_per_day',
        'max_sends_per_hour',
        'allowed_days',
        'allowed_hours',
        'priority',
        'metadata',
    ];

    expect($rule->getFillable())->toBe($expectedFillable);
});

test('notification rule model casts attributes correctly', function () {
    $rule = NotificationRule::create([
        'name' => 'Test Rule',
        'channel' => 'email',
        'conditions' => [['field' => 'priority', 'operator' => '>=', 'value' => 2]],
        'is_active' => true,
        'start_date' => now(),
        'end_date' => now()->addMonths(6),
        'max_sends_per_day' => 10,
        'max_sends_per_hour' => 2,
        'allowed_days' => [1, 2, 3, 4, 5],
        'allowed_hours' => [9, 10, 11, 14, 15, 16, 17],
        'priority' => 1,
        'metadata' => ['type' => 'business'],
    ]);

    expect($rule->conditions)->toBeArray();
    expect($rule->is_active)->toBeBool();
    expect($rule->start_date)->toBeInstanceOf(Carbon\Carbon::class);
    expect($rule->end_date)->toBeInstanceOf(Carbon\Carbon::class);
    expect($rule->max_sends_per_day)->toBeInt();
    expect($rule->max_sends_per_hour)->toBeInt();
    expect($rule->allowed_days)->toBeArray();
    expect($rule->allowed_hours)->toBeArray();
    expect($rule->priority)->toBeInt();
    expect($rule->metadata)->toBeArray();
});

test('notification log model has correct fillable attributes', function () {
    $log = new NotificationLog;

    $expectedFillable = [
        'notification_id',
        'channel',
        'recipient',
        'message',
        'status',
        'response',
        'metadata',
        'sent_at',
    ];

    expect($log->getFillable())->toBe($expectedFillable);
});

test('notification log model casts attributes correctly', function () {
    $log = NotificationLog::create([
        'notification_id' => 'test-123',
        'channel' => 'email',
        'recipient' => 'test@example.com',
        'message' => 'Test message',
        'status' => 'sent',
        'response' => 'Message sent successfully',
        'metadata' => ['priority' => 1],
        'sent_at' => now(),
    ]);

    expect($log->metadata)->toBeArray();
    expect($log->sent_at)->toBeInstanceOf(Carbon\Carbon::class);
});

test('notification usage model has correct fillable attributes', function () {
    $usage = new NotificationUsage;

    $expectedFillable = [
        'notification_id',
        'channel',
        'cost',
        'used_at',
        'metadata',
    ];

    expect($usage->getFillable())->toBe($expectedFillable);
});

test('notification usage model casts attributes correctly', function () {
    $usage = NotificationUsage::create([
        'notification_id' => 'test-123',
        'channel' => 'email',
        'cost' => 0.0050,
        'used_at' => now(),
        'metadata' => ['recipient' => 'test@example.com'],
    ]);

    expect($usage->cost)->toBeString(); // Decimal casts to string in Laravel
    expect($usage->cost)->toBe('0.0050');
    expect($usage->used_at)->toBeInstanceOf(Carbon\Carbon::class);
    expect($usage->metadata)->toBeArray();
});

test('can create and retrieve notification rule', function () {
    $rule = NotificationRule::create([
        'name' => 'Daily Limit Rule',
        'channel' => 'slack',
        'conditions' => [['field' => 'priority', 'operator' => '>=', 'value' => 2]],
        'is_active' => true,
        'max_sends_per_day' => 5,
        'max_sends_per_hour' => 1,
        'allowed_days' => [1, 2, 3, 4, 5],
        'allowed_hours' => [9, 10, 11, 14, 15, 16, 17],
        'priority' => 1,
        'metadata' => ['department' => 'marketing'],
    ]);

    $retrieved = NotificationRule::where('name', 'Daily Limit Rule')->first();

    expect($retrieved)->not->toBeNull();
    expect($retrieved->name)->toBe('Daily Limit Rule');
    expect($retrieved->channel)->toBe('slack');
    expect($retrieved->is_active)->toBeTrue();
    expect($retrieved->max_sends_per_day)->toBe(5);
});

test('can create and retrieve notification log', function () {
    $log = NotificationLog::create([
        'notification_id' => 'log-test-456',
        'channel' => 'telegram',
        'recipient' => '123456789',
        'message' => 'Test telegram message',
        'status' => 'sent',
        'response' => 'Delivered successfully',
        'metadata' => ['chat_id' => '123456789'],
        'sent_at' => now(),
    ]);

    $retrieved = NotificationLog::where('notification_id', 'log-test-456')->first();

    expect($retrieved)->not->toBeNull();
    expect($retrieved->channel)->toBe('telegram');
    expect($retrieved->recipient)->toBe('123456789');
    expect($retrieved->status)->toBe('sent');
});

test('can create and retrieve notification usage', function () {
    $usage = NotificationUsage::create([
        'notification_id' => 'usage-test-789',
        'channel' => 'whatsapp',
        'cost' => 0.0075,
        'used_at' => now(),
        'metadata' => ['phone' => '+1234567890'],
    ]);

    $retrieved = NotificationUsage::where('notification_id', 'usage-test-789')->first();

    expect($retrieved)->not->toBeNull();
    expect($retrieved->channel)->toBe('whatsapp');
    expect($retrieved->cost)->toBe('0.0075'); // Decimal casts to string
    expect($retrieved->metadata['phone'])->toBe('+1234567890');
});

test('can query notification rules by channel and active status', function () {
    NotificationRule::create([
        'name' => 'Active Email Rule',
        'channel' => 'email',
        'conditions' => [],
        'is_active' => true,
        'max_sends_per_day' => 0,
        'max_sends_per_hour' => 0,
        'allowed_days' => [],
        'allowed_hours' => [],
        'priority' => 1,
        'metadata' => [],
    ]);

    NotificationRule::create([
        'name' => 'Inactive Email Rule',
        'channel' => 'email',
        'conditions' => [],
        'is_active' => false,
        'max_sends_per_day' => 0,
        'max_sends_per_hour' => 0,
        'allowed_days' => [],
        'allowed_hours' => [],
        'priority' => 1,
        'metadata' => [],
    ]);

    NotificationRule::create([
        'name' => 'Active Slack Rule',
        'channel' => 'slack',
        'conditions' => [],
        'is_active' => true,
        'max_sends_per_day' => 0,
        'max_sends_per_hour' => 0,
        'allowed_days' => [],
        'allowed_hours' => [],
        'priority' => 1,
        'metadata' => [],
    ]);

    $activeEmailRules = NotificationRule::where('channel', 'email')
        ->where('is_active', true)
        ->get();

    expect($activeEmailRules)->toHaveCount(1);
    expect($activeEmailRules->first()->name)->toBe('Active Email Rule');
});

test('can query notification logs by status and date', function () {
    $today = now();
    $yesterday = now()->subDay();

    NotificationLog::create([
        'notification_id' => 'today-sent',
        'channel' => 'email',
        'recipient' => 'test1@example.com',
        'message' => 'Today sent',
        'status' => 'sent',
        'response' => null,
        'metadata' => [],
        'sent_at' => $today,
    ]);

    NotificationLog::create([
        'notification_id' => 'today-failed',
        'channel' => 'email',
        'recipient' => 'test2@example.com',
        'message' => 'Today failed',
        'status' => 'failed',
        'response' => 'Error occurred',
        'metadata' => [],
        'sent_at' => $today,
    ]);

    NotificationLog::create([
        'notification_id' => 'yesterday-sent',
        'channel' => 'email',
        'recipient' => 'test3@example.com',
        'message' => 'Yesterday sent',
        'status' => 'sent',
        'response' => null,
        'metadata' => [],
        'sent_at' => $yesterday,
    ]);

    $todaySentLogs = NotificationLog::where('status', 'sent')
        ->whereDate('sent_at', $today)
        ->get();

    expect($todaySentLogs)->toHaveCount(1);
    expect($todaySentLogs->first()->notification_id)->toBe('today-sent');
});

test('can calculate total usage cost by channel', function () {
    NotificationUsage::create([
        'notification_id' => 'cost-1',
        'channel' => 'email',
        'cost' => 0.005,
        'used_at' => now(),
        'metadata' => [],
    ]);

    NotificationUsage::create([
        'notification_id' => 'cost-2',
        'channel' => 'email',
        'cost' => 0.010,
        'used_at' => now(),
        'metadata' => [],
    ]);

    NotificationUsage::create([
        'notification_id' => 'cost-3',
        'channel' => 'slack',
        'cost' => 0.002,
        'used_at' => now(),
        'metadata' => [],
    ]);

    $emailCost = (float) NotificationUsage::where('channel', 'email')->sum('cost');
    $slackCost = (float) NotificationUsage::where('channel', 'slack')->sum('cost');

    expect($emailCost)->toBe(0.015);
    expect($slackCost)->toBe(0.002);
});

test('notification rule model has proper relationships and timestamps', function () {
    $rule = NotificationRule::create([
        'name' => 'Test Rule',
        'channel' => 'email',
        'conditions' => ['user_type' => 'premium'],
        'is_active' => true,
    ]);

    expect($rule->id)->toBeNumeric();
    expect($rule->created_at)->not->toBeNull();
    expect($rule->updated_at)->not->toBeNull();
    expect($rule->name)->toBe('Test Rule');
});

test('notification log model stores metadata correctly', function () {
    $metadata = ['user_id' => 123, 'campaign' => 'welcome'];

    $log = NotificationLog::create([
        'notification_id' => 'test-log-metadata',
        'channel' => 'email',
        'recipient' => 'test@example.com',
        'message' => 'Test message',
        'status' => 'sent',
        'metadata' => $metadata,
        'sent_at' => now(),
    ]);

    expect($log->metadata)->toBe($metadata);
    expect($log->status)->toBe('sent');
});

test('notification usage model tracks costs and timestamps properly', function () {
    $usage = NotificationUsage::create([
        'notification_id' => 'cost-test-timestamp',
        'channel' => 'whatsapp',
        'cost' => 0.25,
        'used_at' => now(),
    ]);

    expect($usage->cost)->toBe('0.2500'); // Decimal cast to string with 4 decimal places
    expect($usage->used_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($usage->channel)->toBe('whatsapp');
});

test('models handle complex json attributes correctly', function () {
    $conditions = ['age' => ['operator' => 'gte', 'value' => 18]];
    $allowedDays = [1, 2, 3, 4, 5]; // Weekdays
    $allowedHours = [9, 10, 11, 12, 13, 14, 15, 16, 17]; // Business hours

    $rule = NotificationRule::create([
        'name' => 'Business Hours Rule',
        'channel' => 'email',
        'conditions' => $conditions,
        'allowed_days' => $allowedDays,
        'allowed_hours' => $allowedHours,
        'metadata' => ['department' => 'marketing'],
        'is_active' => true,
    ]);

    expect($rule->conditions)->toBe($conditions);
    expect($rule->allowed_days)->toBe($allowedDays);
    expect($rule->allowed_hours)->toBe($allowedHours);
    expect($rule->metadata)->toBe(['department' => 'marketing']);
    expect($rule->is_active)->toBeTrue();
});

test('models can be updated with new data', function () {
    $rule = NotificationRule::create([
        'name' => 'Initial Rule',
        'channel' => 'email',
        'conditions' => [],
        'is_active' => true,
    ]);

    $rule->update([
        'name' => 'Updated Rule',
        'conditions' => ['status' => 'active'],
        'is_active' => false,
    ]);

    expect($rule->name)->toBe('Updated Rule');
    expect($rule->conditions)->toBe(['status' => 'active']);
    expect($rule->is_active)->toBeFalse();
});

test('models handle empty and null values correctly', function () {
    $rule = NotificationRule::create([
        'name' => 'Empty Values Rule',
        'channel' => 'email',
        'conditions' => [],
        'allowed_days' => null,
        'allowed_hours' => null,
        'metadata' => null,
        'is_active' => true,
    ]);

    expect($rule->conditions)->toBe([]);
    expect($rule->allowed_days)->toBeNull();
    expect($rule->allowed_hours)->toBeNull();
    expect($rule->metadata)->toBeNull();
});
