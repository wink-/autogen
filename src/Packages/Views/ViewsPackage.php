<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views;

use AutoGen\Common\Package\AbstractAutoGenPackage;
use AutoGen\Packages\Views\Commands\ViewGeneratorCommand;
use AutoGen\Packages\Views\Generators\ViewGenerator;
use AutoGen\Packages\Views\Generators\TailwindGenerator;
use AutoGen\Packages\Views\Generators\BootstrapGenerator;
use AutoGen\Packages\Views\Generators\PlainCssGenerator;
use AutoGen\Packages\Views\Generators\StubManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\File;

class ViewsPackage extends AbstractAutoGenPackage
{
    /**
     * Configure the package.
     */
    protected function configure(): void
    {
        $this->name = 'views';
        $this->description = 'AutoGen Views Generation Package - Generates Blade templates with various CSS frameworks';
        $this->version = '1.0.0';
        $this->priority = 30; // Load after model and controller packages
        
        $this->commands = [
            ViewGeneratorCommand::class,
        ];
        
        $this->configFiles = [
            __DIR__ . '/../../config/views.php' => config_path('autogen/views.php'),
        ];
        
        $this->assets = [
            __DIR__ . '/Stubs' => resource_path('stubs/autogen/views'),
        ];
        
        $this->dependencies = [
            ViewGenerator::class,
            TailwindGenerator::class,
            BootstrapGenerator::class,
            PlainCssGenerator::class,
            StubManager::class,
        ];
    }

    /**
     * Check package-specific requirements.
     */
    protected function checkRequirements(Container $container): bool
    {
        // Check if Laravel's view system is available
        try {
            $viewFactory = $container->make('view');
            return $viewFactory !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Register package bindings.
     */
    protected function registerBindings(Container $container): void
    {
        // Register stub manager
        $container->singleton(StubManager::class, function ($app) {
            return new StubManager($app['files']);
        });
        
        // Register CSS framework generators
        $container->singleton(TailwindGenerator::class, function ($app) {
            return new TailwindGenerator(
                $app[StubManager::class],
                $app['autogen.templates']
            );
        });
        
        $container->singleton(BootstrapGenerator::class, function ($app) {
            return new BootstrapGenerator(
                $app[StubManager::class],
                $app['autogen.templates']
            );
        });
        
        $container->singleton(PlainCssGenerator::class, function ($app) {
            return new PlainCssGenerator(
                $app[StubManager::class],
                $app['autogen.templates']
            );
        });
        
        // Register main view generator
        $container->singleton(ViewGenerator::class, function ($app) {
            return new ViewGenerator(
                $app[TailwindGenerator::class],
                $app[BootstrapGenerator::class],
                $app[PlainCssGenerator::class],
                $app['autogen.config'],
                $app['files']
            );
        });
        
        // Register aliases for easier access
        $container->alias(ViewGenerator::class, 'autogen.views.generator');
        $container->alias(StubManager::class, 'autogen.views.stubs');
        $container->alias(TailwindGenerator::class, 'autogen.views.tailwind');
        $container->alias(BootstrapGenerator::class, 'autogen.views.bootstrap');
        $container->alias(PlainCssGenerator::class, 'autogen.views.css');
    }

    /**
     * Boot the package.
     */
    protected function bootPackage(Container $container): void
    {
        // Register commands if running in console
        if ($container->bound('artisan') && $container->make('app')->runningInConsole()) {
            $this->registerCommands($container);
        }
        
        // Publish configuration files
        $this->publishConfigFiles();
        
        // Load package configuration
        $this->loadConfiguration($container);
    }

    /**
     * Publish package assets.
     */
    protected function publishAssets(): void
    {
        if (!app()->runningInConsole()) {
            return;
        }
        
        // Publish view stubs
        if (File::exists(__DIR__ . '/Stubs')) {
            app()->make('Illuminate\Foundation\Application')->publishes([
                __DIR__ . '/Stubs' => resource_path('stubs/autogen/views'),
            ], 'autogen-views-stubs');
        }
    }
    
    /**
     * Publish configuration files.
     */
    protected function publishConfigFiles(): void
    {
        if (!app()->runningInConsole()) {
            return;
        }
        
        foreach ($this->configFiles as $source => $destination) {
            if (File::exists($source)) {
                app()->make('Illuminate\Foundation\Application')->publishes([
                    $source => $destination,
                ], 'autogen-views-config');
            }
        }
    }
    
    /**
     * Load package configuration.
     */
    protected function loadConfiguration(Container $container): void
    {
        $configPath = __DIR__ . '/../../config/views.php';
        if (File::exists($configPath)) {
            $container->make('config')->set('autogen.views', require $configPath);
        }
    }
    
    /**
     * Handle package failure with specific views package context.
     */
    protected function onFailure(\Throwable $exception): void
    {
        // Log specific views package failure information
        logger()->error('Views Package specific failure details', [
            'package' => $this->name,
            'version' => $this->version,
            'error' => $exception->getMessage(),
            'views_path' => config('autogen.generation.output_paths.views', 'resources/views'),
            'default_framework' => config('autogen.packages.views.default_framework', 'tailwind'),
        ]);
        
        // Notify about graceful degradation
        if (app()->environment('local', 'testing')) {
            logger()->info('Views Package disabled - view generation commands will not be available');
        }
    }
}