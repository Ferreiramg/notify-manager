<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\Queue\Queue;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\Jobs\SendNotificationJob;
use NotifyManager\Services\QueueService;

beforeEach(function () {
    $this->queueFactory = $this->mock(QueueFactory::class);
    $this->queue = $this->mock(Queue::class);

    $this->config = [
        'enabled' => true,
        'connection' => 'default',
        'queue_name' => 'notifications',
    ];

    $this->queueService = new QueueService($this->queueFactory, $this->config);
});

test('dispatches notification job when queue is enabled', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $this->queueFactory
        ->shouldReceive('connection')
        ->with('default')
        ->once()
        ->andReturn($this->queue);

    $this->queue
        ->shouldReceive('pushOn')
        ->with('notifications', \Mockery::type(SendNotificationJob::class), null)
        ->once();

    $this->queueService->dispatch($notification);
});

test('dispatches delayed notification job', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $this->queueFactory
        ->shouldReceive('connection')
        ->with('default')
        ->once()
        ->andReturn($this->queue);

    $this->queue
        ->shouldReceive('pushOn')
        ->with('notifications', \Mockery::type(SendNotificationJob::class), 300)
        ->once();

    $this->queueService->dispatchDelayed($notification, 300);
});

test('dispatches notification at specific time', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $when = now()->addHours(2);

    $this->queueFactory
        ->shouldReceive('connection')
        ->with('default')
        ->once()
        ->andReturn($this->queue);

    $this->queue
        ->shouldReceive('pushOn')
        ->with('notifications', \Mockery::type(SendNotificationJob::class), \Mockery::type('int'))
        ->once();

    $this->queueService->dispatchAt($notification, $when);
});

test('throws exception when queue is disabled', function () {
    $disabledConfig = array_merge($this->config, ['enabled' => false]);
    $queueService = new QueueService($this->queueFactory, $disabledConfig);

    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    expect(function () use ($queueService, $notification) {
        $queueService->dispatch($notification);
    })->toThrow(\RuntimeException::class, 'Queue is not enabled in configuration');
});

test('returns correct configuration values', function () {
    expect($this->queueService->isEnabled())->toBeTrue();
    expect($this->queueService->getConnection())->toBe('default');
    expect($this->queueService->getQueueName())->toBe('notifications');
});

test('returns false when queue is disabled', function () {
    $disabledConfig = array_merge($this->config, ['enabled' => false]);
    $queueService = new QueueService($this->queueFactory, $disabledConfig);

    expect($queueService->isEnabled())->toBeFalse();
});
