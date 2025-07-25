<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

use Illuminate\Support\ServiceProvider;

class ScaffoldServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Register configuration
        $this->mergeConfigFrom(
            __DIR__ . '/config/scaffold.php',
            'autogen.scaffold'
        );

        // Register core services
        $this->app->singleton(DependencyResolver::class);
        $this->app->singleton(ProgressTracker::class);
        $this->app->singleton(ConfigurationValidator::class);
        
        // Register workflow orchestrator with dependencies
        $this->app->singleton(WorkflowOrchestrator::class, function ($app) {
            return new WorkflowOrchestrator(
                $app->make(DependencyResolver::class),
                $app->make(ProgressTracker::class)
            );
        });

        // Register the main scaffold command
        $this->app->singleton(ScaffoldCommand::class, function ($app) {
            return new ScaffoldCommand(
                $app->make(WorkflowOrchestrator::class),
                $app->make(ConfigurationValidator::class),
                $app->make(ProgressTracker::class)
            );
        });
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        // Only register console commands when running in console
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/config/scaffold.php' => config_path('autogen/scaffold.php'),
            ], 'autogen-scaffold-config');

            // Register the scaffold command
            $this->commands([
                ScaffoldCommand::class,
            ]);

            // Register event listeners for progress tracking
            $this->registerEventListeners();
        }
    }

    /**
     * Register event listeners for progress tracking.
     */
    protected function registerEventListeners(): void
    {
        // Listen to progress events and log them
        $tracker = $this->app->make(ProgressTracker::class);
        
        $tracker->onProgress('started', function ($data) {
            logger()->info('Scaffold generation started', $data);
        });

        $tracker->onProgress('completed', function ($data) {
            logger()->info('Scaffold generation completed', $data);
        });

        $tracker->onProgress('failed', function ($data) {
            logger()->error('Scaffold generation failed', $data);
        });

        $tracker->onProgress('step_started', function ($data) {
            logger()->debug('Scaffold step started', [
                'package' => $data['step']->getPackage(),
                'command' => $data['step']->getCommand(),
            ]);
        });

        $tracker->onProgress('step_completed', function ($data) {
            logger()->debug('Scaffold step completed', [
                'package' => $data['step']->getPackage(),
                'generated_files' => count($data['result']->getGeneratedFiles()),
                'skipped_files' => count($data['result']->getSkippedFiles()),
            ]);
        });

        $tracker->onProgress('step_failed', function ($data) {
            logger()->warning('Scaffold step failed', [
                'package' => $data['step']->getPackage(),
                'error' => $data['error'],
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            DependencyResolver::class,
            ProgressTracker::class,
            ConfigurationValidator::class,
            WorkflowOrchestrator::class,
            ScaffoldCommand::class,
        ];
    }
}