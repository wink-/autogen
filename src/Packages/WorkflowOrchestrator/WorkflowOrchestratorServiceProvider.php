<?php

declare(strict_types=1);

namespace AutoGen\Packages\WorkflowOrchestrator;

use Illuminate\Support\ServiceProvider;

class WorkflowOrchestratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/workflow-orchestrator.php',
            'autogen.workflow-orchestrator'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/workflow-orchestrator.php' => config_path('autogen/workflow-orchestrator.php'),
            ], 'autogen-workflow-orchestrator-config');
        }
    }
}