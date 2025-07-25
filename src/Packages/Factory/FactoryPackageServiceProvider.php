<?php

declare(strict_types=1);

namespace AutoGen\Packages\Factory;

use Illuminate\Support\ServiceProvider;

class FactoryPackageServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        FakeDataMapper::class => FakeDataMapper::class,
        RelationshipFactoryHandler::class => RelationshipFactoryHandler::class,
        FactoryGenerator::class => FactoryGenerator::class,
    ];

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/config/factory.php',
            'autogen.factory'
        );

        // Register services
        $this->app->singleton(FakeDataMapper::class, function ($app) {
            $mapper = new FakeDataMapper();
            
            // Add custom patterns from config
            $customPatterns = config('autogen.factory.custom_field_patterns', []);
            foreach ($customPatterns as $pattern => $faker) {
                $mapper->addCustomPattern($pattern, $faker);
            }
            
            // Add custom data types from config
            $customDataTypes = config('autogen.factory.custom_data_types', []);
            foreach ($customDataTypes as $type => $faker) {
                $mapper->addCustomDataType($type, $faker);
            }
            
            return $mapper;
        });

        $this->app->singleton(RelationshipFactoryHandler::class);
        
        $this->app->singleton(FactoryGenerator::class, function ($app) {
            return new FactoryGenerator(
                $app->make(FakeDataMapper::class),
                $app->make(RelationshipFactoryHandler::class),
                $app->make(\AutoGen\Packages\Model\DatabaseIntrospector::class)
            );
        });
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/factory.php' => config_path('autogen/factory.php'),
            ], 'autogen-factory-config');

            // Publish stubs
            $this->publishes([
                __DIR__ . '/Stubs' => resource_path('stubs/autogen/factory'),
            ], 'autogen-factory-stubs');

            // Register commands
            $this->commands([
                FactoryGeneratorCommand::class,
            ]);
        }

        // Load views if needed
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'autogen-factory');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            FakeDataMapper::class,
            RelationshipFactoryHandler::class,
            FactoryGenerator::class,
            FactoryGeneratorCommand::class,
        ];
    }
}