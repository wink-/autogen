<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model;

use AutoGen\Common\Package\AbstractAutoGenPackage;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\File;

class ModelPackage extends AbstractAutoGenPackage
{
    /**
     * Configure the package.
     */
    protected function configure(): void
    {
        $this->name = 'model';
        $this->description = 'AutoGen Model Generation Package - Generates Eloquent models from database schemas';
        $this->version = '1.0.0';
        $this->priority = 10; // Load early as other packages may depend on models
        
        $this->commands = [
            ModelGeneratorCommand::class,
        ];
        
        $this->configFiles = [
            __DIR__ . '/../../config/model.php' => config_path('autogen/model.php'),
        ];
        
        $this->assets = [
            __DIR__ . '/Stubs' => resource_path('stubs/autogen/model'),
        ];
        
        $this->dependencies = [
            DatabaseIntrospector::class,
            RelationshipAnalyzer::class,
            ModelGenerator::class,
            ValidationRuleGenerator::class,
            TypeMapper::class,
        ];
    }

    /**
     * Check package-specific requirements.
     */
    protected function checkRequirements(Container $container): bool
    {
        // Check if required database connection exists
        try {
            $db = $container->make('db');
            $connection = $db->connection();
            $connection->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Register package bindings.
     */
    protected function registerBindings(Container $container): void
    {
        // Register core model generation services
        $container->singleton(DatabaseIntrospector::class, function ($app) {
            return new DatabaseIntrospector($app['db']);
        });
        
        $container->singleton(RelationshipAnalyzer::class, function ($app) {
            return new RelationshipAnalyzer($app[DatabaseIntrospector::class]);
        });
        
        $container->singleton(TypeMapper::class, function ($app) {
            $configManager = $app->make('autogen.config');
            return new TypeMapper($configManager->get('column_type_mappings', []));
        });
        
        $container->singleton(ValidationRuleGenerator::class, function ($app) {
            $configManager = $app->make('autogen.config');
            return new ValidationRuleGenerator($configManager->get('validation_rules', []));
        });
        
        $container->singleton(ModelGenerator::class, function ($app) {
            return new ModelGenerator(
                $app[DatabaseIntrospector::class],
                $app[RelationshipAnalyzer::class],
                $app[TypeMapper::class],
                $app['autogen.templates']
            );
        });
        
        // Register aliases for easier access
        $container->alias(ModelGenerator::class, 'autogen.model.generator');
        $container->alias(DatabaseIntrospector::class, 'autogen.model.introspector');
        $container->alias(RelationshipAnalyzer::class, 'autogen.model.relationships');
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
        
        // Publish model stubs
        if (File::exists(__DIR__ . '/Stubs')) {
            app()->make('Illuminate\Foundation\Application')->publishes([
                __DIR__ . '/Stubs' => resource_path('stubs/autogen/model'),
            ], 'autogen-model-stubs');
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
                ], 'autogen-model-config');
            }
        }
    }
    
    /**
     * Load package configuration.
     */
    protected function loadConfiguration(Container $container): void
    {
        $configPath = __DIR__ . '/../../config/model.php';
        if (File::exists($configPath)) {
            $container->make('config')->set('autogen.model', require $configPath);
        }
    }
    
    /**
     * Handle package failure with specific model package context.
     */
    protected function onFailure(\Throwable $exception): void
    {
        // Log specific model package failure information
        logger()->error('Model Package specific failure details', [
            'package' => $this->name,
            'version' => $this->version,
            'error' => $exception->getMessage(),
            'database_connection' => config('database.default'),
            'models_path' => config('autogen.generation.output_paths.models', 'app/Models'),
        ]);
        
        // Notify about graceful degradation
        if (app()->environment('local', 'testing')) {
            logger()->info('Model Package disabled - model generation commands will not be available');
        }
    }
}