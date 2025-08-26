<?php

declare(strict_types=1);

namespace NotifyManager\Channels;

use NotifyManager\DTOs\NotificationDTO;

/**
 * Example Email Channel Implementation
 * This is a sample implementation - you should create your own based on your email service
 */
final class EmailChannel extends BaseChannel
{
    public function getName(): string
    {
        return 'email';
    }

    public function send(NotificationDTO $notification): bool
    {
        // This is a placeholder implementation
        // In a real application, you would integrate with your email service
        // (Laravel Mail, SendGrid, Mailgun, etc.)

        try {
            // Example: Using Laravel's Mail facade
            // Mail::to($notification->recipient)
            //     ->send(new NotificationMail($notification));

            // For demonstration purposes, we'll just log the attempt
            \Log::info('Email notification sent', [
                'recipient' => $notification->recipient,
                'subject' => $notification->subject,
                'message' => $notification->message,
            ]);

            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to send email notification', [
                'recipient' => $notification->recipient,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function validate(NotificationDTO $notification): bool
    {
        return parent::validate($notification) &&
               filter_var($notification->recipient, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function supports(NotificationDTO $notification): bool
    {
        return $notification->channel === 'email';
    }

    public function getCostPerMessage(): float
    {
        return $this->config['cost_per_message'] ?? 0.005; // $0.005 per email
    }
}
