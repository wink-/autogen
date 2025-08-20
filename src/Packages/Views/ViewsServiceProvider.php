<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views;

use AutoGen\Packages\Views\Commands\ViewGeneratorCommand;
use Illuminate\Support\ServiceProvider;

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
        // Publish stubs
        $this->publishes([
            __DIR__.'/Stubs' => resource_path('stubs/autogen/views'),
        ], 'autogen-views-stubs');
    }
}
