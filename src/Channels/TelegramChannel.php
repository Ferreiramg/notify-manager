<?php

declare(strict_types=1);

namespace NotifyManager\Channels;

use NotifyManager\DTOs\NotificationDTO;

/**
 * Example Telegram Channel Implementation
 * This is a sample implementation - you should implement your own based on Telegram Bot API
 */
final class TelegramChannel extends BaseChannel
{
    public function getName(): string
    {
        return 'telegram';
    }

    public function send(NotificationDTO $notification): bool
    {
        // This is a placeholder implementation
        // In a real application, you would integrate with Telegram Bot API

        try {
            $botToken = $this->config['bot_token'] ?? '';
            $chatId = $notification->recipient;

            if (empty($botToken)) {
                throw new \InvalidArgumentException('Telegram bot token is required');
            }

            // Example API call to Telegram
            // $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            //     'chat_id' => $chatId,
            //     'text' => $notification->message,
            //     'parse_mode' => 'HTML',
            // ]);

            // For demonstration purposes, we'll just log the attempt
            \Log::info('Telegram notification sent', [
                'chat_id' => $chatId,
                'message' => $notification->message,
            ]);

            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to send Telegram notification', [
                'chat_id' => $notification->recipient,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function validate(NotificationDTO $notification): bool
    {
        return parent::validate($notification) &&
               is_numeric($notification->recipient);
    }

    public function supports(NotificationDTO $notification): bool
    {
        return $notification->channel === 'telegram';
    }

    public function getCostPerMessage(): float
    {
        return $this->config['cost_per_message'] ?? 0.002; // $0.002 per message
    }
}
