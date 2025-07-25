<?php

declare(strict_types=1);

namespace AutoGen\Packages\TestGenerator;

use Illuminate\Support\ServiceProvider;

class TestGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/test-generator.php',
            'autogen.test-generator'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/test-generator.php' => config_path('autogen/test-generator.php'),
            ], 'autogen-test-generator-config');
        }
    }
}