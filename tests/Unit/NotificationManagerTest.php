<?php

declare(strict_types=1);

use NotifyManager\Channels\EmailChannel;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\DTOs\NotificationRuleDTO;
use NotifyManager\Models\NotificationLog;
use NotifyManager\Models\NotificationRule;
use NotifyManager\Models\NotificationUsage;
use NotifyManager\Services\NotificationManager;
use NotifyManager\Services\QueueService;
use NotifyManager\Services\TemplateService;
use NotifyManager\Tests\Channels\MockSlackChannel;
use NotifyManager\Tests\Channels\MockWhatsAppChannel;

beforeEach(function () {
    $this->templateService = $this->mock(TemplateService::class);
    $this->queueService = $this->mock(QueueService::class);

    $this->manager = new NotificationManager($this->templateService, $this->queueService);
    $this->slackChannel = new MockSlackChannel(['cost_per_message' => 0.002]);
    $this->whatsappChannel = new MockWhatsAppChannel(['cost_per_message' => 0.005]);
    $this->emailChannel = new EmailChannel(['cost_per_message' => 0.001]);

    $this->manager->registerChannel('slack', $this->slackChannel);
    $this->manager->registerChannel('whatsapp', $this->whatsappChannel);
    $this->manager->registerChannel('email', $this->emailChannel);
});

test('can send notification through slack channel', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test slack message'
    );

    $result = $this->manager->send($notification);

    expect($result)->toBeTrue();
    expect($this->slackChannel->getSentNotifications())->toHaveCount(1);
});

test('can send notification through whatsapp channel', function () {
    $notification = NotificationDTO::create(
        channel: 'whatsapp',
        recipient: '+1234567890',
        message: 'Test WhatsApp message'
    );

    $result = $this->manager->send($notification);

    expect($result)->toBeTrue();
    expect($this->whatsappChannel->getSentNotifications())->toHaveCount(1);
});

test('fails to send when channel does not exist', function () {
    $notification = NotificationDTO::create(
        channel: 'nonexistent',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $result = $this->manager->send($notification);

    expect($result)->toBeFalse();
});

test('fails to send when channel validation fails', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: 'invalid-channel', // Should start with #
        message: 'Test message'
    );

    $result = $this->manager->send($notification);

    expect($result)->toBeFalse();
});

test('fails to send when whatsapp validation fails', function () {
    $notification = NotificationDTO::create(
        channel: 'whatsapp',
        recipient: 'invalid-number', // Should start with + and have 10+ digits
        message: 'Test message'
    );

    $result = $this->manager->send($notification);

    expect($result)->toBeFalse();
});

test('can calculate cost for different channels', function () {
    $slackNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    $whatsappNotification = NotificationDTO::create(
        channel: 'whatsapp',
        recipient: '+1234567890',
        message: 'Test message'
    );

    $slackCost = $this->manager->calculateCost($slackNotification);
    $whatsappCost = $this->manager->calculateCost($whatsappNotification);

    expect($slackCost)->toBe(0.002);
    expect($whatsappCost)->toBe(0.005);
});

test('can calculate cost with priority multiplier', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message',
        options: ['priority' => 2]
    );

    $cost = $this->manager->calculateCost($notification);

    // Base cost (0.002) * priority multiplier (1.5) = 0.003
    expect($cost)->toBe(0.003);
});

test('can calculate cost with length multiplier for long messages', function () {
    $longMessage = str_repeat('a', 200); // > 160 characters

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: $longMessage
    );

    $cost = $this->manager->calculateCost($notification);

    // Base cost (0.002) * length multiplier (1.2) = 0.0024
    expect($cost)->toBe(0.0024);
});

test('returns zero cost for nonexistent channel', function () {
    $notification = NotificationDTO::create(
        channel: 'nonexistent',
        recipient: 'test',
        message: 'Test message'
    );

    $cost = $this->manager->calculateCost($notification);

    expect($cost)->toBe(0.0);
});

test('can create notification rule', function () {
    $rule = NotificationRuleDTO::create(
        name: 'Test Rule',
        channel: 'slack',
        conditions: [
            [
                'field' => 'priority',
                'operator' => '>=',
                'value' => 2,
            ],
        ]
    );

    $result = $this->manager->createRule($rule);

    expect($result)->toBeTrue();
    expect(NotificationRule::where('name', 'Test Rule')->exists())->toBeTrue();
});

test('should send evaluates simple conditions correctly', function () {
    // Create a rule that blocks low priority messages
    NotificationRule::create([
        'name' => 'Block Low Priority',
        'channel' => 'slack',
        'conditions' => [
            [
                'field' => 'priority',
                'operator' => '>=',
                'value' => 2,
            ],
        ],
        'is_active' => true,
        'max_sends_per_day' => 0,
        'max_sends_per_hour' => 0,
        'allowed_days' => [],
        'allowed_hours' => [],
        'priority' => 1,
        'metadata' => [],
    ]);

    $lowPriorityNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Low priority message',
        options: ['priority' => 1]
    );

    $highPriorityNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'High priority message',
        options: ['priority' => 3]
    );

    expect($this->manager->shouldSend($lowPriorityNotification))->toBeFalse();
    expect($this->manager->shouldSend($highPriorityNotification))->toBeTrue();
});

test('should send respects daily limits', function () {
    NotificationRule::create([
        'name' => 'Daily Limit Rule',
        'channel' => 'slack',
        'conditions' => [],
        'is_active' => true,
        'max_sends_per_day' => 1,
        'max_sends_per_hour' => 0,
        'allowed_days' => [],
        'allowed_hours' => [],
        'priority' => 1,
        'metadata' => [],
    ]);

    // Create a log entry for today
    NotificationLog::create([
        'notification_id' => 'test-1',
        'channel' => 'slack',
        'recipient' => '#general',
        'message' => 'Previous message',
        'status' => 'sent',
        'response' => null,
        'metadata' => [],
        'sent_at' => now(),
    ]);

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    expect($this->manager->shouldSend($notification))->toBeFalse();
});

test('should send respects hourly limits', function () {
    NotificationRule::create([
        'name' => 'Hourly Limit Rule',
        'channel' => 'slack',
        'conditions' => [],
        'is_active' => true,
        'max_sends_per_day' => 0,
        'max_sends_per_hour' => 1,
        'allowed_days' => [],
        'allowed_hours' => [],
        'priority' => 1,
        'metadata' => [],
    ]);

    // Create a log entry for this hour
    NotificationLog::create([
        'notification_id' => 'test-1',
        'channel' => 'slack',
        'recipient' => '#general',
        'message' => 'Previous message',
        'status' => 'sent',
        'response' => null,
        'metadata' => [],
        'sent_at' => now()->subMinutes(30),
    ]);

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    expect($this->manager->shouldSend($notification))->toBeFalse();
});

test('should send respects allowed hours', function () {
    NotificationRule::create([
        'name' => 'Hours Rule',
        'channel' => 'slack',
        'conditions' => [],
        'is_active' => true,
        'max_sends_per_day' => 0,
        'max_sends_per_hour' => 0,
        'allowed_days' => [],
        'allowed_hours' => [9, 10, 11], // Only 9-11 AM
        'priority' => 1,
        'metadata' => [],
    ]);

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    // Mock current hour to be outside allowed hours
    $currentHour = now()->hour;
    if (in_array($currentHour, [9, 10, 11])) {
        expect($this->manager->shouldSend($notification))->toBeTrue();
    } else {
        expect($this->manager->shouldSend($notification))->toBeFalse();
    }
});

test('evaluates multiple condition operators correctly', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message containing keyword',
        options: [
            'priority' => 2,
            'tags' => ['urgent', 'alert'],
        ]
    );

    $manager = new NotificationManager;
    $reflectionMethod = new ReflectionMethod($manager, 'evaluateConditions');
    $reflectionMethod->setAccessible(true);

    // Test equals
    $conditions = [['field' => 'priority', 'operator' => '=', 'value' => 2]];
    expect($reflectionMethod->invoke($manager, $conditions, $notification))->toBeTrue();

    // Test not equals
    $conditions = [['field' => 'priority', 'operator' => '!=', 'value' => 1]];
    expect($reflectionMethod->invoke($manager, $conditions, $notification))->toBeTrue();

    // Test greater than
    $conditions = [['field' => 'priority', 'operator' => '>', 'value' => 1]];
    expect($reflectionMethod->invoke($manager, $conditions, $notification))->toBeTrue();

    // Test contains
    $conditions = [['field' => 'message', 'operator' => 'contains', 'value' => 'keyword']];
    expect($reflectionMethod->invoke($manager, $conditions, $notification))->toBeTrue();

    // Test not contains
    $conditions = [['field' => 'message', 'operator' => 'not_contains', 'value' => 'missing']];
    expect($reflectionMethod->invoke($manager, $conditions, $notification))->toBeTrue();

    // Test in array
    $conditions = [['field' => 'priority', 'operator' => 'in', 'value' => [1, 2, 3]]];
    expect($reflectionMethod->invoke($manager, $conditions, $notification))->toBeTrue();

    // Test not in array
    $conditions = [['field' => 'priority', 'operator' => 'not_in', 'value' => [4, 5, 6]]];
    expect($reflectionMethod->invoke($manager, $conditions, $notification))->toBeTrue();
});

test('logs activity when sending notifications', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    $this->manager->send($notification);

    $log = NotificationLog::where('notification_id', $notification->id)->first();

    expect($log)->not->toBeNull();
    expect($log->channel)->toBe('slack');
    expect($log->recipient)->toBe('#general');
    expect($log->status)->toBe('sent');
});

test('records usage when sending notifications', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    $this->manager->send($notification);

    $usage = NotificationUsage::where('notification_id', $notification->id)->first();

    expect($usage)->not->toBeNull();
    expect($usage->channel)->toBe('slack');
    expect($usage->cost)->toBe('0.0020'); // Decimal casts to string
});

test('handles send failures gracefully', function () {
    $failingChannel = new MockSlackChannel([], true); // Will fail
    $this->manager->registerChannel('failing', $failingChannel);

    $notification = NotificationDTO::create(
        channel: 'failing',
        recipient: '#general',
        message: 'Test message'
    );

    $result = $this->manager->send($notification);

    expect($result)->toBeFalse();

    $log = NotificationLog::where('notification_id', $notification->id)->first();
    expect($log->status)->toBe('failed');
});

test('handles log activity errors gracefully', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    // Create a spy to verify the log call doesn't throw
    expect(function () use ($notification) {
        $this->manager->logActivity($notification, 'sent', 'Success');
    })->not->toThrow(\Exception::class);
});

test('evaluates rule with date range correctly', function () {
    $tomorrow = now()->addDay();
    $yesterday = now()->subDay();

    // Future start date - should not send
    $futureRule = NotificationRule::create([
        'name' => 'Future Rule',
        'channel' => 'slack',
        'conditions' => [],
        'start_date' => $tomorrow,
        'is_active' => true,
    ]);

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    expect($this->manager->shouldSend($notification, $futureRule))
        ->toBeFalse();

    // Past end date - should not send
    $pastRule = NotificationRule::create([
        'name' => 'Past Rule',
        'channel' => 'slack',
        'conditions' => [],
        'end_date' => $yesterday,
        'is_active' => true,
    ]);

    expect($this->manager->shouldSend($notification, $pastRule))
        ->toBeFalse();
});

test('evaluates rule with allowed days correctly', function () {
    $currentDay = now()->dayOfWeek;
    $otherDay = $currentDay === 0 ? 1 : 0; // Different day

    $rule = NotificationRule::create([
        'name' => 'Day Rule',
        'channel' => 'slack',
        'conditions' => [],
        'allowed_days' => [$otherDay], // Not today
        'is_active' => true,
    ]);

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    expect($this->manager->shouldSend($notification, $rule))
        ->toBeFalse();

    // Update rule to allow current day
    $rule->update(['allowed_days' => [$currentDay]]);

    expect($this->manager->shouldSend($notification, $rule))
        ->toBeTrue();
});

test('can record usage with cost tracking', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    // Send the notification which will record usage internally
    $this->manager->send($notification);

    $usage = NotificationUsage::where('notification_id', $notification->id)->first();

    expect($usage)
        ->not->toBeNull()
        ->and($usage->cost)->toBeString(); // Just verify cost is recorded as a string
});

test('handles record usage errors gracefully', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    // Should not throw exception
    expect(function () use ($notification) {
        // Call through the manager's send method which will call recordUsage
        $this->manager->send($notification);
    })->not->toThrow(\Exception::class);
});

test('calculates cost for different message lengths', function () {
    $shortNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Short'
    );

    $longMessage = str_repeat('This is a very long message that exceeds 160 characters. ', 4);
    $longNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: $longMessage
    );

    $shortCost = $this->manager->calculateCost($shortNotification);
    $longCost = $this->manager->calculateCost($longNotification);

    expect($longCost)->toBeGreaterThan($shortCost);
});

test('processes conditions with complex operators', function () {
    $rule = NotificationRule::create([
        'name' => 'Complex Rule',
        'channel' => 'slack',
        'conditions' => [
            ['field' => 'user_id', 'operator' => 'in', 'value' => [1, 2, 3]],
            ['field' => 'status', 'operator' => 'not_in', 'value' => ['blocked', 'suspended']],
            ['field' => 'age', 'operator' => '>=', 'value' => 18],
            ['field' => 'score', 'operator' => '<=', 'value' => 100],
        ],
        'is_active' => true,
    ]);

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message',
        options: [
            'metadata' => [
                'user_id' => 2,
                'status' => 'active',
                'age' => 25,
                'score' => 85,
            ],
        ]
    );

    expect($this->manager->shouldSend($notification, $rule))
        ->toBeTrue();

    // Test failing condition
    $notification2 = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message',
        options: [
            'metadata' => [
                'user_id' => 5, // Not in allowed list
                'status' => 'active',
                'age' => 25,
                'score' => 85,
            ],
        ]
    );

    expect($this->manager->shouldSend($notification2, $rule))
        ->toBeFalse();
});

test('can send notifications asynchronously via queue', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Async test message'
    );

    $this->queueService
        ->shouldReceive('isEnabled')
        ->once()
        ->andReturn(true);

    $this->queueService
        ->shouldReceive('dispatch')
        ->with(\Mockery::type(NotificationDTO::class), null)
        ->once();

    $this->manager->sendAsync($notification);
});

test('can send notifications at specific time', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Scheduled test message'
    );

    $when = now()->addHours(2);

    $this->queueService
        ->shouldReceive('isEnabled')
        ->once()
        ->andReturn(true);

    $this->queueService
        ->shouldReceive('dispatchAt')
        ->with(\Mockery::type(NotificationDTO::class), $when)
        ->once();

    $this->manager->sendAt($notification, $when);
});

test('throws exception when trying to queue with disabled queue service', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    $this->queueService
        ->shouldReceive('isEnabled')
        ->once()
        ->andReturn(false);

    expect(function () use ($notification) {
        $this->manager->sendAsync($notification);
    })->toThrow(\RuntimeException::class, 'Queue service is not enabled');
});

test('processes templates when sending notifications', function () {
    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Original message',
        options: [
            'template' => 'welcome',
        ]
    );

    $this->templateService
        ->shouldReceive('render')
        ->with(\Mockery::type(NotificationDTO::class))
        ->once()
        ->andReturn('Rendered template message');

    $result = $this->manager->send($notification);

    expect($result)->toBeTrue();
    expect($this->slackChannel->getSentNotifications())->toHaveCount(1);
});

test('skips template processing when no template service available', function () {
    $managerWithoutTemplate = new NotificationManager(null, null);
    $managerWithoutTemplate->registerChannel('slack', $this->slackChannel);

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message',
        options: [
            'template' => 'welcome',
        ]
    );

    $result = $managerWithoutTemplate->send($notification);

    expect($result)->toBeTrue();
    expect($this->slackChannel->getSentNotifications())->toHaveCount(1);
});
