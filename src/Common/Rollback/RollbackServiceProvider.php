<?php

declare(strict_types=1);

namespace AutoGen\Common\Rollback;

use AutoGen\Commands\Rollback\BackupCleanCommand;
use AutoGen\Commands\Rollback\BackupListCommand;
use AutoGen\Commands\Rollback\RollbackCommand;
use AutoGen\Common\Contracts\BackupManagerInterface;
use AutoGen\Common\Contracts\RollbackStrategyInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for rollback functionality.
 */
class RollbackServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register backup strategies
        $this->app->bind('autogen.rollback.file_backup', function ($app) {
            return new BackupStrategy(
                config('autogen.rollback.backup_disk', 'local'),
                config('autogen.rollback.backup_path', 'autogen/backups')
            );
        });

        $this->app->bind('autogen.rollback.database_rollback', function ($app) {
            return new DatabaseRollbackStrategy(
                config('autogen.rollback.backup_disk', 'local'),
                config('autogen.rollback.db_backup_path', 'autogen/db_backups'),
                config('autogen.rollback.supported_connections', ['mysql', 'pgsql', 'sqlite'])
            );
        });

        $this->app->bind('autogen.rollback.composite', function ($app) {
            return new CompositeRollbackStrategy([
                $app->make('autogen.rollback.file_backup'),
                $app->make('autogen.rollback.database_rollback'),
            ]);
        });

        // Register backup manager
        $this->app->singleton(BackupManagerInterface::class, function ($app) {
            $manager = new BackupManager(
                config('autogen.rollback.backup_disk', 'local'),
                config('autogen.rollback.index_path', 'autogen/backup_index.json')
            );

            // Register strategies
            $manager->registerStrategy('file_backup', $app->make('autogen.rollback.file_backup'));
            $manager->registerStrategy('database_rollback', $app->make('autogen.rollback.database_rollback'));
            $manager->registerStrategy('composite', $app->make('autogen.rollback.composite'));

            return $manager;
        });

        // Register error recovery manager
        $this->app->singleton(ErrorRecoveryManager::class, function ($app) {
            return new ErrorRecoveryManager($app->make(BackupManagerInterface::class));
        });

        // Register commands
        $this->commands([
            RollbackCommand::class,
            BackupListCommand::class,
            BackupCleanCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../../config/rollback.php' => config_path('autogen/rollback.php'),
        ], 'autogen-rollback-config');

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/rollback.php',
            'autogen.rollback'
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            BackupManagerInterface::class,
            ErrorRecoveryManager::class,
            'autogen.rollback.file_backup',
            'autogen.rollback.database_rollback',
            'autogen.rollback.composite',
        ];
    }
}