<?php

declare(strict_types=1);

namespace AutoGen\Packages\OptimizationEngine;

use Illuminate\Support\ServiceProvider;

class OptimizationEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/optimization-engine.php',
            'autogen.optimization-engine'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/optimization-engine.php' => config_path('autogen/optimization-engine.php'),
            ], 'autogen-optimization-engine-config');
        }
    }
}