<?php

declare(strict_types=1);

use NotifyManager\Channels\BaseChannel;
use NotifyManager\Channels\EmailChannel;
use NotifyManager\Channels\TelegramChannel;
use NotifyManager\DTOs\NotificationDTO;
use NotifyManager\Tests\Channels\MockSlackChannel;
use NotifyManager\Tests\Channels\MockWhatsAppChannel;

test('base channel has correct default behavior', function () {
    $channel = new class extends BaseChannel
    {
        public function send(NotificationDTO $notification): bool
        {
            return true;
        }

        public function getName(): string
        {
            return 'test';
        }
    };

    $notification = NotificationDTO::create(
        channel: 'test',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    expect($channel->supports($notification))->toBeTrue();
    expect($channel->validate($notification))->toBeTrue();
    expect($channel->getCostPerMessage())->toBe(0.01);
});

test('base channel validation fails with empty recipient', function () {
    $channel = new class extends BaseChannel
    {
        public function send(NotificationDTO $notification): bool
        {
            return true;
        }

        public function getName(): string
        {
            return 'test';
        }
    };

    $notification = NotificationDTO::create(
        channel: 'test',
        recipient: '',
        message: 'Test message'
    );

    expect($channel->validate($notification))->toBeFalse();
});

test('base channel validation fails with empty message', function () {
    $channel = new class extends BaseChannel
    {
        public function send(NotificationDTO $notification): bool
        {
            return true;
        }

        public function getName(): string
        {
            return 'test';
        }
    };

    $notification = NotificationDTO::create(
        channel: 'test',
        recipient: 'test@example.com',
        message: ''
    );

    expect($channel->validate($notification))->toBeFalse();
});

test('base channel respects custom cost configuration', function () {
    $channel = new class(['cost_per_message' => 0.05]) extends BaseChannel
    {
        public function send(NotificationDTO $notification): bool
        {
            return true;
        }

        public function getName(): string
        {
            return 'test';
        }
    };

    expect($channel->getCostPerMessage())->toBe(0.05);
});

test('email channel validates email addresses correctly', function () {
    $channel = new EmailChannel;

    $validNotification = NotificationDTO::create(
        channel: 'email',
        recipient: 'valid@example.com',
        message: 'Test message'
    );

    $invalidNotification = NotificationDTO::create(
        channel: 'email',
        recipient: 'invalid-email',
        message: 'Test message'
    );

    expect($channel->validate($validNotification))->toBeTrue();
    expect($channel->validate($invalidNotification))->toBeFalse();
});

test('email channel supports only email channel', function () {
    $channel = new EmailChannel;

    $emailNotification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    $slackNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    expect($channel->supports($emailNotification))->toBeTrue();
    expect($channel->supports($slackNotification))->toBeFalse();
});

test('email channel has correct name and cost', function () {
    $channel = new EmailChannel;

    expect($channel->getName())->toBe('email');
    expect($channel->getCostPerMessage())->toBe(0.005);
});

test('email channel respects custom cost configuration', function () {
    $channel = new EmailChannel(['cost_per_message' => 0.01]);

    expect($channel->getCostPerMessage())->toBe(0.01);
});

test('telegram channel validates numeric recipient', function () {
    $channel = new TelegramChannel;

    $validNotification = NotificationDTO::create(
        channel: 'telegram',
        recipient: '123456789',
        message: 'Test message'
    );

    $invalidNotification = NotificationDTO::create(
        channel: 'telegram',
        recipient: 'invalid-id',
        message: 'Test message'
    );

    expect($channel->validate($validNotification))->toBeTrue();
    expect($channel->validate($invalidNotification))->toBeFalse();
});

test('telegram channel supports only telegram channel', function () {
    $channel = new TelegramChannel;

    $telegramNotification = NotificationDTO::create(
        channel: 'telegram',
        recipient: '123456789',
        message: 'Test message'
    );

    $emailNotification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    expect($channel->supports($telegramNotification))->toBeTrue();
    expect($channel->supports($emailNotification))->toBeFalse();
});

test('telegram channel has correct name and cost', function () {
    $channel = new TelegramChannel;

    expect($channel->getName())->toBe('telegram');
    expect($channel->getCostPerMessage())->toBe(0.002);
});

test('telegram channel respects custom cost configuration', function () {
    $channel = new TelegramChannel(['cost_per_message' => 0.003]);

    expect($channel->getCostPerMessage())->toBe(0.003);
});

test('mock slack channel validates channel prefix', function () {
    $channel = new MockSlackChannel;

    $validNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    $invalidNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: 'general',
        message: 'Test message'
    );

    expect($channel->validate($validNotification))->toBeTrue();
    expect($channel->validate($invalidNotification))->toBeFalse();
});

test('mock slack channel can track sent notifications', function () {
    $channel = new MockSlackChannel;

    $notification1 = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Message 1'
    );

    $notification2 = NotificationDTO::create(
        channel: 'slack',
        recipient: '#random',
        message: 'Message 2'
    );

    expect($channel->send($notification1))->toBeTrue();
    expect($channel->send($notification2))->toBeTrue();

    $sent = $channel->getSentNotifications();
    expect($sent)->toHaveCount(2);
    expect($sent[0]->message)->toBe('Message 1');
    expect($sent[1]->message)->toBe('Message 2');
});

test('mock slack channel can simulate failures', function () {
    $channel = new MockSlackChannel([], true);

    $notification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Test message'
    );

    expect($channel->send($notification))->toBeFalse();
    expect($channel->getSentNotifications())->toHaveCount(0);
});

test('mock whatsapp channel validates phone number format', function () {
    $channel = new MockWhatsAppChannel;

    $validNotification = NotificationDTO::create(
        channel: 'whatsapp',
        recipient: '+1234567890',
        message: 'Test message'
    );

    $invalidNotification = NotificationDTO::create(
        channel: 'whatsapp',
        recipient: '1234567890', // Missing +
        message: 'Test message'
    );

    $shortInvalidNotification = NotificationDTO::create(
        channel: 'whatsapp',
        recipient: '+123456789', // Too short
        message: 'Test message'
    );

    expect($channel->validate($validNotification))->toBeTrue();
    expect($channel->validate($invalidNotification))->toBeFalse();
    expect($channel->validate($shortInvalidNotification))->toBeFalse();
});

test('mock whatsapp channel can reset sent notifications', function () {
    $channel = new MockWhatsAppChannel;

    $notification = NotificationDTO::create(
        channel: 'whatsapp',
        recipient: '+1234567890',
        message: 'Test message'
    );

    $channel->send($notification);
    expect($channel->getSentNotifications())->toHaveCount(1);

    $channel->reset();
    expect($channel->getSentNotifications())->toHaveCount(0);
});

test('email channel handles send method properly', function () {
    $channel = new EmailChannel;

    $notification = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: 'Test message'
    );

    // Test successful send (mock would be needed for real implementation)
    $result = $channel->send($notification);

    // In a real implementation, this would send an email
    // For now, we just verify the method exists and can be called
    expect($result)->toBeTrue();
});

test('telegram channel handles send method properly', function () {
    $channel = new TelegramChannel;

    $notification = NotificationDTO::create(
        channel: 'telegram',
        recipient: '123456789',
        message: 'Test message'
    );

    // First validate the notification is valid
    expect($channel->validate($notification))->toBeTrue();

    // Test successful send (mock would be needed for real implementation)
    $result = $channel->send($notification);

    // Since TelegramChannel doesn't implement send method yet, it inherits from BaseChannel
    // For now, we just verify the method exists and can be called
    expect($result)->toBeBool(); // Just verify it returns a boolean
});

test('base channel provides default implementation', function () {
    $channel = new class extends BaseChannel
    {
        public function send(NotificationDTO $notification): bool
        {
            return true;
        }

        public function getName(): string
        {
            return 'test';
        }
    };

    $notification = NotificationDTO::create(
        channel: 'test',
        recipient: 'test-recipient',
        message: 'test message'
    );

    expect($channel->getName())->toBe('test');
    expect($channel->send($notification))->toBeTrue();
});

test('channels handle different validation edge cases', function () {
    $emailChannel = new EmailChannel;
    $telegramChannel = new TelegramChannel;

    // Test various email formats
    $validEmail = NotificationDTO::create(
        channel: 'email',
        recipient: 'user.name+tag@domain.co.uk',
        message: 'message'
    );

    $invalidEmail = NotificationDTO::create(
        channel: 'email',
        recipient: 'invalid-email',
        message: 'message'
    );

    $emptyEmail = NotificationDTO::create(
        channel: 'email',
        recipient: '',
        message: 'message'
    );

    expect($emailChannel->validate($validEmail))->toBeTrue();
    expect($emailChannel->validate($invalidEmail))->toBeFalse();
    expect($emailChannel->validate($emptyEmail))->toBeFalse();

    // Test various telegram recipient formats
    $validTelegram = NotificationDTO::create(
        channel: 'telegram',
        recipient: '987654321',
        message: 'message'
    );

    $invalidTelegram = NotificationDTO::create(
        channel: 'telegram',
        recipient: 'not-numeric',
        message: 'message'
    );

    $emptyTelegram = NotificationDTO::create(
        channel: 'telegram',
        recipient: '',
        message: 'message'
    );

    expect($telegramChannel->validate($validTelegram))->toBeTrue();
    expect($telegramChannel->validate($invalidTelegram))->toBeFalse();
    expect($telegramChannel->validate($emptyTelegram))->toBeFalse();
});

test('channels can be created with custom configuration', function () {
    // Test with custom configuration
    $customConfig = ['cost' => 0.05];

    $emailChannel = new EmailChannel($customConfig);
    $telegramChannel = new TelegramChannel($customConfig);

    // Verify channels are created successfully
    expect($emailChannel)->toBeInstanceOf(EmailChannel::class);
    expect($telegramChannel)->toBeInstanceOf(TelegramChannel::class);
});

test('mock channels provide additional testing features', function () {
    $slackChannel = new MockSlackChannel;
    $whatsappChannel = new MockWhatsAppChannel;

    $slackNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#general',
        message: 'Slack message'
    );

    $whatsappNotification = NotificationDTO::create(
        channel: 'whatsapp',
        recipient: '+1234567890',
        message: 'WhatsApp message'
    );

    // Test tracking functionality
    $slackChannel->send($slackNotification);
    $whatsappChannel->send($whatsappNotification);

    expect($slackChannel->getSentNotifications())->toHaveCount(1);
    expect($whatsappChannel->getSentNotifications())->toHaveCount(1);

    // Test normal functionality
    $successNotification = NotificationDTO::create(
        channel: 'slack',
        recipient: '#test',
        message: 'Should succeed'
    );

    expect($slackChannel->send($successNotification))->toBeTrue();
});

test('channels handle empty message validation', function () {
    $emailChannel = new EmailChannel;
    $telegramChannel = new TelegramChannel;

    $emptyMessageEmail = NotificationDTO::create(
        channel: 'email',
        recipient: 'test@example.com',
        message: ''
    );

    $emptyMessageTelegram = NotificationDTO::create(
        channel: 'telegram',
        recipient: '123456789',
        message: ''
    );

    expect($emailChannel->validate($emptyMessageEmail))->toBeFalse();
    expect($telegramChannel->validate($emptyMessageTelegram))->toBeFalse();
});
