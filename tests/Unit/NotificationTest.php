<?php

declare(strict_types=1);

use NotifyManager\Channels\EmailChannel;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\Services\NotificationManager;

test('can create notification DTO', function () {
    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    expect($notification->channel)->toBe('email');
    expect($notification->recipient)->toBe('test@example.com');
    expect($notification->message)->toBe('Test message');
    expect($notification->id)->not->toBeEmpty();
});

test('can register and get notification channel', function () {
    $manager = new NotificationManager;
    $emailChannel = new EmailChannel;

    $manager->registerChannel('email', $emailChannel);

    $retrievedChannel = $manager->getChannel('email');

    expect($retrievedChannel)->toBe($emailChannel);
    expect($retrievedChannel->getName())->toBe('email');
});

test('can calculate notification cost', function () {
    $manager = new NotificationManager;
    $emailChannel = new EmailChannel;
    $manager->registerChannel('email', $emailChannel);

    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message',
        options: ['priority' => 2]
    );

    $cost = $manager->calculateCost($notification);

    expect($cost)->toBeGreaterThan(0);
});

test('email channel validates email addresses', function () {
    $emailChannel = new EmailChannel;

    $validNotification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $invalidNotification = NotificationDTO::create(
        channel: 'email',
        recipient: 'invalid-email',
        message: 'Test message'
    );

    expect($emailChannel->validate($validNotification))->toBeTrue();
    expect($emailChannel->validate($invalidNotification))->toBeFalse();
});
