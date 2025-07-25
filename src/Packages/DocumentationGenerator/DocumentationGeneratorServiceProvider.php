<?php

declare(strict_types=1);

namespace AutoGen\Packages\DocumentationGenerator;

use Illuminate\Support\ServiceProvider;

class DocumentationGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/documentation-generator.php',
            'autogen.documentation-generator'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/documentation-generator.php' => config_path('autogen/documentation-generator.php'),
            ], 'autogen-documentation-generator-config');
        }
    }
}