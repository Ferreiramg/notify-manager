<?php

declare(strict_types=1);

namespace NotifyManager;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ServiceProvider;
use NotifyManager\Console\Commands\InstallCommand;
use NotifyManager\Contracts\NotificationChannelInterface;
use NotifyManager\Contracts\NotificationManagerInterface;
use NotifyManager\Services\NotificationManager;
use NotifyManager\Services\QueueService;
use NotifyManager\Services\TemplateService;

final class NotifyManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/notify-manager.php',
            'notify-manager'
        );

        // Register template service
        $this->app->singleton(TemplateService::class, function ($app) {
            return new TemplateService(
                $app->make(ViewFactory::class),
                config('notify-manager.templates', [])
            );
        });

        // Register queue service
        $this->app->singleton(QueueService::class, function ($app) {
            return new QueueService(
                $app->make(QueueFactory::class),
                config('notify-manager.queue', [])
            );
        });

        // Register notification manager with dependencies
        $this->app->singleton(NotificationManagerInterface::class, function ($app) {
            return new NotificationManager(
                $app->make(TemplateService::class),
                $app->make(QueueService::class)
            );
        });

        $this->app->alias(NotificationManagerInterface::class, 'notify-manager');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/notify-manager.php' => config_path('notify-manager.php'),
        ], 'notify-manager-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'notify-manager-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }

        $this->registerChannels();
    }

    private function registerChannels(): void
    {
        $channels = config('notify-manager.channels', []);

        foreach ($channels as $name => $channelClass) {
            if (class_exists($channelClass) && is_subclass_of($channelClass, NotificationChannelInterface::class)) {
                $this->app->singleton("notify-manager.channel.{$name}", $channelClass);
            }
        }
    }

    public function provides(): array
    {
        return [
            NotificationManagerInterface::class,
            'notify-manager',
        ];
    }
}
