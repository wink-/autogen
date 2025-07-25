<?php

declare(strict_types=1);

namespace AutoGen\Packages\AnalysisTools;

use Illuminate\Support\ServiceProvider;

class AnalysisToolsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/analysis-tools.php',
            'autogen.analysis-tools'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/analysis-tools.php' => config_path('autogen/analysis-tools.php'),
            ], 'autogen-analysis-tools-config');
        }
    }
}