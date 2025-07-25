<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable;

use Illuminate\Support\ServiceProvider;

class DatatableServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/datatable.php',
            'autogen.datatable'
        );
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Register the command
        if ($this->app->runningInConsole()) {
            $this->commands([
                DatatableGeneratorCommand::class,
            ]);
        }

        // Publish configuration
        $this->publishes([
            __DIR__ . '/config/datatable.php' => config_path('autogen/datatable.php'),
        ], 'autogen-datatable-config');

        // Publish stub files
        $this->publishes([
            __DIR__ . '/Stubs' => resource_path('stubs/datatable'),
        ], 'autogen-datatable-stubs');

        // Register views if using Blade components
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'autogen-datatable');

        // Publish view files
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/autogen-datatable'),
        ], 'autogen-datatable-views');
    }
}