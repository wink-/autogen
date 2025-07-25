<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views;

use Illuminate\Support\ServiceProvider;
use AutoGen\Packages\Views\Commands\ViewGeneratorCommand;

class ViewsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the command
        $this->commands([
            ViewGeneratorCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/autogen-views.php' => config_path('autogen-views.php'),
        ], 'autogen-views-config');

        // Publish stubs
        $this->publishes([
            __DIR__ . '/Stubs' => resource_path('stubs/autogen/views'),
        ], 'autogen-views-stubs');

        // Merge default configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/autogen-views.php',
            'autogen-views'
        );
    }
}