<?php

declare(strict_types=1);

namespace AutoGen\Packages\Migration;

use Illuminate\Support\ServiceProvider;
use AutoGen\Packages\Model\DatabaseIntrospector;
use AutoGen\Common\Templates\TemplateEngine;

class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/config/migration.php',
            'autogen.migration'
        );

        // Register the database schema analyzer
        $this->app->singleton(DatabaseSchemaAnalyzer::class, function ($app) {
            return new DatabaseSchemaAnalyzer(
                $app->make(DatabaseIntrospector::class)
            );
        });

        // Register the migration template engine
        $this->app->singleton(MigrationTemplateEngine::class, function ($app) {
            return new MigrationTemplateEngine(
                $app->make(TemplateEngine::class)
            );
        });

        // Register the migration generator
        $this->app->singleton(MigrationGenerator::class, function ($app) {
            return new MigrationGenerator(
                $app->make(MigrationTemplateEngine::class)
            );
        });

        // Register the command
        $this->app->singleton(MigrationGeneratorCommand::class, function ($app) {
            return new MigrationGeneratorCommand(
                $app->make(DatabaseSchemaAnalyzer::class),
                $app->make(MigrationGenerator::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the command
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrationGeneratorCommand::class,
            ]);
        }

        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/migration.php' => config_path('autogen/migration.php'),
        ], 'autogen-migration-config');

        // Publish stubs
        $this->publishes([
            __DIR__ . '/Stubs' => resource_path('stubs/autogen/migration'),
        ], 'autogen-migration-stubs');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            DatabaseSchemaAnalyzer::class,
            MigrationTemplateEngine::class,
            MigrationGenerator::class,
            MigrationGeneratorCommand::class,
        ];
    }
}