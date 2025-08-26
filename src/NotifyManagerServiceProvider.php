<?php

declare(strict_types=1);

namespace NotifyManager;

use Illuminate\Support\ServiceProvider;
use NotifyManager\Console\Commands\InstallCommand;
use NotifyManager\Contracts\NotificationChannelInterface;
use NotifyManager\Contracts\NotificationManagerInterface;
use NotifyManager\Services\NotificationManager;

final class NotifyManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/notify-manager.php',
            'notify-manager'
        );

        $this->app->singleton(NotificationManagerInterface::class, NotificationManager::class);
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
