<?php

declare(strict_types=1);

namespace NotifyManager\Services;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Cache;
use NotifyManager\DTOs\NotificationDTO;

class TemplateService
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
        private readonly array $config
    ) {}

    public function render(NotificationDTO $notification): string
    {
        if (! $notification->template) {
            return $notification->message;
        }

        $cacheKey = $this->getCacheKey($notification);

        if ($this->config['cache_enabled'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $rendered = $this->renderTemplate($notification);

        if ($this->config['cache_enabled']) {
            Cache::put($cacheKey, $rendered, 3600); // Cache for 1 hour
        }

        return $rendered;
    }

    private function renderTemplate(NotificationDTO $notification): string
    {
        $templatePath = $this->getTemplatePath($notification->template);

        if (! $this->viewFactory->exists($templatePath)) {
            // Fallback to message if template doesn't exist
            return $notification->message;
        }

        $data = array_merge([
            'notification' => $notification,
            'message' => $notification->message,
            'subject' => $notification->subject,
            'recipient' => $notification->recipient,
            'channel' => $notification->channel,
        ], $notification->templateData);

        return $this->viewFactory->make($templatePath, $data)->render();
    }

    private function getTemplatePath(string $template): string
    {
        // Remove any path traversal attempts
        $template = str_replace(['../', '..\\', '/', '\\'], '.', $template);

        return "notifications.{$template}";
    }

    private function getCacheKey(NotificationDTO $notification): string
    {
        $dataHash = md5(serialize($notification->templateData));

        return "notify_template:{$notification->template}:{$dataHash}";
    }

    public function clearTemplateCache(?string $template = null): void
    {
        Cache::flush();
    }
}
