<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model;

use Illuminate\Support\ServiceProvider;

class ModelPackageServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/config/autogen.php',
            'autogen'
        );

        // Register services
        $this->app->singleton(DatabaseIntrospector::class);
        $this->app->singleton(RelationshipAnalyzer::class);
        $this->app->singleton(ModelGenerator::class);
        $this->app->singleton(ValidationRuleGenerator::class);
        $this->app->singleton(TypeMapper::class);
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/autogen.php' => config_path('autogen.php'),
            ], 'autogen-config');

            // Publish stubs
            $this->publishes([
                __DIR__ . '/Stubs' => resource_path('stubs/autogen/model'),
            ], 'autogen-stubs');

            // Register commands
            $this->commands([
                ModelGeneratorCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            DatabaseIntrospector::class,
            RelationshipAnalyzer::class,
            ModelGenerator::class,
            ValidationRuleGenerator::class,
            TypeMapper::class,
            ModelGeneratorCommand::class,
        ];
    }
}