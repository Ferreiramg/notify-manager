<?php

declare(strict_types=1);

namespace NotifyManager\Console\Commands;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'notify-manager:install 
                            {--force : Overwrite existing files}';

    protected $description = 'Install the NotifyManager package';

    public function handle(): int
    {
        $this->info('Installing NotifyManager...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'notify-manager-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'notify-manager-migrations',
            '--force' => $this->option('force'),
        ]);

        // Run migrations
        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->call('migrate');
        }

        $this->info('NotifyManager installed successfully!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('1. Configure your notification channels in config/notify-manager.php');
        $this->line('2. Implement your notification channels using NotificationChannelInterface');
        $this->line('3. Start sending notifications using the NotifyManager facade');

        return self::SUCCESS;
    }
}
