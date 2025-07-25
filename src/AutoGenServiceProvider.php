<?php

declare(strict_types=1);

namespace AutoGen;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

// Core Services
use AutoGen\AutoGenManager;
use AutoGen\Common\AI\AIProviderManager;
use AutoGen\Common\Analysis\CodeAnalyzer;
use AutoGen\Common\Formatting\CodeFormatter;
use AutoGen\Common\Templates\TemplateEngine;

// Package Service Providers
use AutoGen\Packages\Views\ViewsServiceProvider;
use AutoGen\Packages\Factory\FactoryPackageServiceProvider;
use AutoGen\Packages\Datatable\DatatableServiceProvider;
use AutoGen\Packages\Model\ModelPackageServiceProvider;
use AutoGen\Packages\Controller\ControllerGeneratorServiceProvider;
use AutoGen\Packages\Migration\MigrationServiceProvider;
use AutoGen\Packages\TestGenerator\TestGeneratorServiceProvider;
use AutoGen\Packages\DocumentationGenerator\DocumentationGeneratorServiceProvider;
use AutoGen\Packages\AnalysisTools\AnalysisToolsServiceProvider;
use AutoGen\Packages\OptimizationEngine\OptimizationEngineServiceProvider;
use AutoGen\Packages\WorkflowOrchestrator\WorkflowOrchestratorServiceProvider;
use AutoGen\Packages\Scaffold\ScaffoldServiceProvider;
use AutoGen\Packages\CodeGenerator\CodeGeneratorServiceProvider;

// Commands
use AutoGen\Packages\Scaffold\ScaffoldCommand;

class AutoGenServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-autogen')
            ->hasConfigFile(['autogen', 'autogen-views'])
            ->hasViews()
            ->hasMigrations()
            ->hasCommands([
                ScaffoldCommand::class,
            ])
            ->publishesServiceProvider('AutoGenServiceProvider');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        parent::register();

        // Register core services
        $this->registerCoreServices();
        
        // Register package service providers
        $this->registerPackageProviders();

        // Register facade aliases
        $this->registerFacades();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();

        // Publish configurations
        $this->publishConfigurations();

        // Publish stubs
        $this->publishStubs();

        // Publish assets
        $this->publishAssets();

        // Register Blade components
        $this->registerBladeComponents();

        // Register view composers
        $this->registerViewComposers();
    }

    /**
     * Register core services.
     */
    protected function registerCoreServices(): void
    {
        // AutoGen Manager
        $this->app->singleton('autogen', function ($app) {
            return new AutoGenManager($app);
        });

        // AI Provider Manager
        $this->app->singleton(AIProviderManager::class, function ($app) {
            return new AIProviderManager($app['config']->get('autogen.ai', []));
        });

        // Code Analyzer
        $this->app->singleton(CodeAnalyzer::class, function ($app) {
            return new CodeAnalyzer($app['config']->get('autogen.analysis', []));
        });

        // Code Formatter
        $this->app->singleton(CodeFormatter::class, function ($app) {
            return new CodeFormatter($app['config']->get('autogen.formatting', []));
        });

        // Template Engine
        $this->app->singleton(TemplateEngine::class, function ($app) {
            return new TemplateEngine(
                $app['config']->get('autogen.templates', []),
                $app['files'],
                $app['view']
            );
        });

        // Register core interfaces
        $this->app->bind(
            \AutoGen\Common\Contracts\AIProviderInterface::class,
            AIProviderManager::class
        );

        $this->app->bind(
            \AutoGen\Common\Contracts\CodeAnalyzerInterface::class,
            CodeAnalyzer::class
        );

        $this->app->bind(
            \AutoGen\Common\Contracts\CodeFormatterInterface::class,
            CodeFormatter::class
        );

        $this->app->bind(
            \AutoGen\Common\Contracts\TemplateEngineInterface::class,
            TemplateEngine::class
        );
    }

    /**
     * Register package service providers.
     */
    protected function registerPackageProviders(): void
    {
        $providers = [
            // Core generators
            ModelPackageServiceProvider::class,
            ControllerGeneratorServiceProvider::class,
            ViewsServiceProvider::class,
            MigrationServiceProvider::class,
            FactoryPackageServiceProvider::class,
            
            // Advanced generators
            DatatableServiceProvider::class,
            TestGeneratorServiceProvider::class,
            DocumentationGeneratorServiceProvider::class,
            
            // Analysis and optimization
            AnalysisToolsServiceProvider::class,
            OptimizationEngineServiceProvider::class,
            
            // Orchestration
            WorkflowOrchestratorServiceProvider::class,
            ScaffoldServiceProvider::class,
            CodeGeneratorServiceProvider::class,
        ];

        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Register facade aliases.
     */
    protected function registerFacades(): void
    {
        $this->app->alias(AIProviderManager::class, 'autogen.ai');
        $this->app->alias(CodeAnalyzer::class, 'autogen.analyzer');
        $this->app->alias(CodeFormatter::class, 'autogen.formatter');
        $this->app->alias(TemplateEngine::class, 'autogen.templates');
    }

    /**
     * Publish configurations.
     */
    protected function publishConfigurations(): void
    {
        // Main configuration
        $this->publishes([
            __DIR__ . '/../config/autogen.php' => config_path('autogen.php'),
        ], ['autogen-config', 'config']);

        // Views configuration
        $this->publishes([
            __DIR__ . '/../config/autogen-views.php' => config_path('autogen-views.php'),
        ], ['autogen-views-config', 'config']);

        // Database provider configurations
        $this->publishes([
            __DIR__ . '/../config/database-providers' => config_path('autogen/database-providers'),
        ], ['autogen-database-config', 'config']);

        // Merge configurations
        $this->mergeConfigFrom(__DIR__ . '/../config/autogen.php', 'autogen');
        $this->mergeConfigFrom(__DIR__ . '/../config/autogen-views.php', 'autogen-views');
    }

    /**
     * Publish stubs.
     */
    protected function publishStubs(): void
    {
        // Core stubs
        $this->publishes([
            __DIR__ . '/../src/Packages/Model/Stubs' => resource_path('stubs/autogen/model'),
            __DIR__ . '/../src/Packages/Controller/Stubs' => resource_path('stubs/autogen/controller'),
            __DIR__ . '/../src/Packages/Views/Stubs' => resource_path('stubs/autogen/views'),
            __DIR__ . '/../src/Packages/Migration/Stubs' => resource_path('stubs/autogen/migration'),
            __DIR__ . '/../src/Packages/Factory/Stubs' => resource_path('stubs/autogen/factory'),
            __DIR__ . '/../src/Packages/Datatable/Stubs' => resource_path('stubs/autogen/datatable'),
        ], ['autogen-stubs', 'stubs']);
    }

    /**
     * Publish assets.
     */
    protected function publishAssets(): void
    {
        // CSS frameworks
        $this->publishes([
            __DIR__ . '/../resources/css' => public_path('vendor/autogen/css'),
        ], ['autogen-assets', 'assets']);

        // JavaScript assets
        $this->publishes([
            __DIR__ . '/../resources/js' => public_path('vendor/autogen/js'),
        ], ['autogen-assets', 'assets']);

        // Example files
        $this->publishes([
            __DIR__ . '/../examples' => base_path('vendor/autogen/examples'),
        ], ['autogen-examples', 'examples']);
    }

    /**
     * Register Blade components.
     */
    protected function registerBladeComponents(): void
    {
        if (class_exists(Blade::class)) {
            // Register AutoGen Blade components
            Blade::componentNamespace('AutoGen\\Components', 'autogen');

            // Register common directives
            Blade::directive('autogenForm', function ($expression) {
                return "<?php echo app('autogen.templates')->renderForm({$expression}); ?>";
            });

            Blade::directive('autogenTable', function ($expression) {
                return "<?php echo app('autogen.templates')->renderTable({$expression}); ?>";
            });

            Blade::directive('autogenFilters', function ($expression) {
                return "<?php echo app('autogen.templates')->renderFilters({$expression}); ?>";
            });
        }
    }

    /**
     * Register view composers.
     */
    protected function registerViewComposers(): void
    {
        // Global AutoGen variables
        view()->composer('*', function ($view) {
            $view->with('autogen', [
                'version' => $this->getPackageVersion(),
                'config' => config('autogen', []),
            ]);
        });
    }

    /**
     * Get package version.
     */
    protected function getPackageVersion(): string
    {
        $composerFile = __DIR__ . '/../composer.json';
        
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? '1.0.0';
        }

        return '1.0.0';
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'autogen',
            'autogen.ai',
            'autogen.analyzer',
            'autogen.formatter',
            'autogen.templates',
            AIProviderManager::class,
            CodeAnalyzer::class,
            CodeFormatter::class,
            TemplateEngine::class,
        ];
    }
}