<?php

declare(strict_types=1);

namespace AutoGen\Packages\Controller;

use AutoGen\Common\Package\AbstractAutoGenPackage;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\File;

class ControllerPackage extends AbstractAutoGenPackage
{
    /**
     * Configure the package.
     */
    protected function configure(): void
    {
        $this->name = 'controller';
        $this->description = 'AutoGen Controller Generation Package - Generates Laravel controllers, form requests, and policies';
        $this->version = '1.0.0';
        $this->priority = 20; // Load after model package
        
        $this->commands = [
            ControllerGeneratorCommand::class,
        ];
        
        $this->configFiles = [
            __DIR__ . '/../../config/controller.php' => config_path('autogen/controller.php'),
        ];
        
        $this->assets = [
            __DIR__ . '/Stubs' => resource_path('stubs/autogen/controller'),
        ];
        
        $this->dependencies = [
            ControllerGenerator::class,
            FormRequestGenerator::class,
            PolicyGenerator::class,
        ];
    }

    /**
     * Check package-specific requirements.
     */
    protected function checkRequirements(Container $container): bool
    {
        // Check if Laravel's routing system is available
        try {
            $router = $container->make('router');
            return $router !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Register package bindings.
     */
    protected function registerBindings(Container $container): void
    {
        // Register controller generator
        $container->singleton(ControllerGenerator::class, function ($app) {
            return new ControllerGenerator(
                $app['autogen.templates'],
                $app['files']
            );
        });
        
        // Register form request generator
        $container->singleton(FormRequestGenerator::class, function ($app) {
            return new FormRequestGenerator(
                $app['autogen.templates'],
                $app['files']
            );
        });
        
        // Register policy generator
        $container->singleton(PolicyGenerator::class, function ($app) {
            return new PolicyGenerator(
                $app['autogen.templates'],
                $app['files']
            );
        });
        
        // Register aliases for easier access
        $container->alias(ControllerGenerator::class, 'autogen.controller.generator');
        $container->alias(FormRequestGenerator::class, 'autogen.controller.request');
        $container->alias(PolicyGenerator::class, 'autogen.controller.policy');
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
        
        // Publish controller stubs
        if (File::exists(__DIR__ . '/Stubs')) {
            app()->make('Illuminate\Foundation\Application')->publishes([
                __DIR__ . '/Stubs' => resource_path('stubs/autogen/controller'),
            ], 'autogen-controller-stubs');
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
                ], 'autogen-controller-config');
            }
        }
    }
    
    /**
     * Load package configuration.
     */
    protected function loadConfiguration(Container $container): void
    {
        $configPath = __DIR__ . '/../../config/controller.php';
        if (File::exists($configPath)) {
            $container->make('config')->set('autogen.controller', require $configPath);
        }
    }
    
    /**
     * Handle package failure with specific controller package context.
     */
    protected function onFailure(\Throwable $exception): void
    {
        // Log specific controller package failure information
        logger()->error('Controller Package specific failure details', [
            'package' => $this->name,
            'version' => $this->version,
            'error' => $exception->getMessage(),
            'controllers_path' => config('autogen.generation.output_paths.controllers', 'app/Http/Controllers'),
            'requests_path' => config('autogen.generation.output_paths.requests', 'app/Http/Requests'),
            'policies_path' => config('autogen.generation.output_paths.policies', 'app/Policies'),
        ]);
        
        // Notify about graceful degradation
        if (app()->environment('local', 'testing')) {
            logger()->info('Controller Package disabled - controller generation commands will not be available');
        }
    }
}