<?php

declare(strict_types=1);

use NotifyManager\Contracts\NotificationManagerInterface;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\Jobs\SendNotificationJob;

test('job implements correct interface and traits', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $job = new SendNotificationJob($notification);

    expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    expect($job->tries)->toBe(3);
    expect($job->maxExceptions)->toBe(2);
    expect($job->timeout)->toBe(30);
});

test('job handles notification sending', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $job = new SendNotificationJob($notification);
    $notificationManager = $this->mock(NotificationManagerInterface::class);

    $notificationManager
        ->shouldReceive('send')
        ->with(\Mockery::type(NotificationDTO::class))
        ->once()
        ->andReturn(true);

    $job->handle($notificationManager);
});

test('job stores notification data correctly', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $job = new SendNotificationJob($notification);

    expect($job->notification)->toBe($notification);
    expect($job->notification->channel)->toBe('email');
    expect($job->notification->recipient)->toBe('test@example.com');
    expect($job->notification->message)->toBe('Test message');
});

test('job has correct retry until time', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $job = new SendNotificationJob($notification);
    $retryUntil = $job->retryUntil();

    expect($retryUntil)->toBeInstanceOf(\DateTime::class);
    expect($retryUntil->getTimestamp())->toBeGreaterThan(now()->timestamp);
});
