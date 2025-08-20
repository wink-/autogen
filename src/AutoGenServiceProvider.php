<?php

declare(strict_types=1);

namespace AutoGen;

use AutoGen\Common\AI\AIProviderManager;
use AutoGen\Common\Analysis\CodeAnalyzer;
use AutoGen\Common\Configuration\ConfigurationManager;
use AutoGen\Common\Contracts\PackageRegistryInterface;
use AutoGen\Common\Formatting\CodeFormatter;
use AutoGen\Common\Performance\PerformanceMonitor;
use AutoGen\Common\Performance\SchemaCache;
use AutoGen\Common\Performance\TemplateCache;
use AutoGen\Common\Performance\QueryOptimizer;
use AutoGen\Common\Performance\LazyLoader;
// Core Services
use AutoGen\Common\Registry\PackageRegistry;
use AutoGen\Common\Templates\TemplateEngine;
use AutoGen\Common\Validation\ConfigurationValidator;
use AutoGen\Packages\AnalysisTools\AnalysisToolsServiceProvider;
use AutoGen\Packages\CodeGenerator\CodeGeneratorServiceProvider;
use AutoGen\Packages\Controller\ControllerGeneratorServiceProvider;
// Package Service Providers
use AutoGen\Packages\Datatable\DatatableServiceProvider;
use AutoGen\Packages\DocumentationGenerator\DocumentationGeneratorServiceProvider;
use AutoGen\Packages\Factory\FactoryPackageServiceProvider;
use AutoGen\Packages\Migration\MigrationServiceProvider;
use AutoGen\Packages\Model\ModelPackageServiceProvider;
use AutoGen\Packages\OptimizationEngine\OptimizationEngineServiceProvider;
use AutoGen\Packages\Scaffold\ScaffoldCommand;
use AutoGen\Packages\Scaffold\ScaffoldServiceProvider;
use AutoGen\Commands\Performance\BenchmarkCommand;
use AutoGen\Commands\Performance\OptimizeCommand;
use AutoGen\Commands\Performance\CacheClearCommand;
use AutoGen\Commands\Performance\CacheWarmCommand;
use AutoGen\Packages\TestGenerator\TestGeneratorServiceProvider;
use AutoGen\Packages\Views\ViewsServiceProvider;
use AutoGen\Packages\WorkflowOrchestrator\WorkflowOrchestratorServiceProvider;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
// Commands
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
                BenchmarkCommand::class,
                OptimizeCommand::class,
                CacheClearCommand::class,
                CacheWarmCommand::class,
            ])
            ->publishesServiceProvider('AutoGenServiceProvider');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        parent::register();

        // Register configuration validator and manager first
        $this->registerConfigurationServices();

        // Register package registry before other services
        $this->registerPackageRegistry();

        // Register performance services
        $this->registerPerformanceServices();
        
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

        // Validate configuration on boot (in non-production environments)
        if (! app()->environment('production')) {
            $this->validateConfiguration();
        }

        // Publish configurations
        $this->publishConfigurations();

        // Publish stubs
        $this->publishStubs();

        // Publish assets
        $this->publishAssets();

        // Register Blade components and directives
        $this->registerBladeComponents();

        // Register view composers
        $this->registerViewComposers();
        
        // Boot packages using the registry
        $this->bootRegisteredPackages();
        
        // Initialize performance optimizations
        $this->initializePerformanceOptimizations();
    }

    /**
     * Register configuration services.
     */
    protected function registerConfigurationServices(): void
    {
        // Configuration Validator
        $this->app->singleton(ConfigurationValidator::class, function ($app) {
            return new ConfigurationValidator;
        });

        // Configuration Manager
        $this->app->singleton(ConfigurationManager::class, function ($app) {
            return new ConfigurationManager(
                $app['config'],
                $app[ConfigurationValidator::class]
            );
        });

        // Alias for easier access
        $this->app->alias(ConfigurationManager::class, 'autogen.config');
    }

    /**
     * Validate configuration.
     */
    protected function validateConfiguration(): void
    {
        try {
            $configManager = $this->app->make(ConfigurationManager::class);
            $warnings = $configManager->getWarnings();

            if (! empty($warnings)) {
                foreach ($warnings as $warning) {
                    logger()->warning("AutoGen Configuration Warning: {$warning}");
                }
            }
        } catch (\Exception $e) {
            // In non-production, throw configuration errors
            if (app()->environment('local', 'testing')) {
                throw $e;
            }

            logger()->error("AutoGen Configuration Error: {$e->getMessage()}");
        }
    }

    /**
     * Register performance services.
     */
    protected function registerPerformanceServices(): void
    {
        // Performance Monitor
        $this->app->singleton(PerformanceMonitor::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];
            return new PerformanceMonitor($configManager->get('performance.monitoring', []));
        });

        // Schema Cache
        $this->app->singleton(SchemaCache::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];
            return new SchemaCache($configManager->get('database.introspection', []));
        });

        // Template Cache
        $this->app->singleton(TemplateCache::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];
            return new TemplateCache($configManager->get('templates.caching', []));
        });

        // Query Optimizer
        $this->app->singleton(QueryOptimizer::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];
            return new QueryOptimizer($configManager->get('database.introspection', []));
        });

        // Lazy Loader
        $this->app->singleton(LazyLoader::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];
            return new LazyLoader($configManager->get('performance', []));
        });

        // Register performance aliases
        $this->app->alias(PerformanceMonitor::class, 'autogen.performance');
        $this->app->alias(SchemaCache::class, 'autogen.schema_cache');
        $this->app->alias(TemplateCache::class, 'autogen.template_cache');
        $this->app->alias(QueryOptimizer::class, 'autogen.query_optimizer');
        $this->app->alias(LazyLoader::class, 'autogen.lazy_loader');
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
            $configManager = $app[ConfigurationManager::class];

            return new AIProviderManager($configManager->get('ai', []));
        });

        // Code Analyzer
        $this->app->singleton(CodeAnalyzer::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];

            return new CodeAnalyzer($configManager->get('analysis', []));
        });

        // Code Formatter
        $this->app->singleton(CodeFormatter::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];

            return new CodeFormatter($configManager->get('formatting', []));
        });

        // Template Engine
        $this->app->singleton(TemplateEngine::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];

            return new TemplateEngine(
                $configManager->get('templates', []),
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
     * Register package service providers using the registry system.
     */
    protected function registerPackageProviders(): void
    {
        // Get the package registry
        $registry = $this->app->make(PackageRegistryInterface::class);
        
        // Legacy packages that haven't been converted yet
        $legacyProviders = [
            // Analysis and optimization
            AnalysisToolsServiceProvider::class,
            OptimizationEngineServiceProvider::class,
            
            // Documentation and testing (not yet converted)
            DocumentationGeneratorServiceProvider::class,
            TestGeneratorServiceProvider::class,
            
            // Orchestration
            WorkflowOrchestratorServiceProvider::class,
            ScaffoldServiceProvider::class,
            CodeGeneratorServiceProvider::class,
        ];

        // Register legacy providers the old way
        foreach ($legacyProviders as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
        
        // Auto-discover and register new-style packages
        if ($this->shouldAutoDiscoverPackages()) {
            $this->autoDiscoverPackages($registry);
        }
        
        // Manually register specific packages
        $this->registerCorePackages($registry);
        
        // Register all packages with the container
        $registry->registerPackages();
    }

    /**
     * Register facade aliases.
     */
    protected function registerFacades(): void
    {
        $this->app->alias(ConfigurationManager::class, 'autogen.config');
        $this->app->alias(AIProviderManager::class, 'autogen.ai');
        $this->app->alias(CodeAnalyzer::class, 'autogen.analyzer');
        $this->app->alias(CodeFormatter::class, 'autogen.formatter');
        $this->app->alias(TemplateEngine::class, 'autogen.templates');
        $this->app->alias(PerformanceMonitor::class, 'autogen.performance');
        $this->app->alias(SchemaCache::class, 'autogen.schema_cache');
        $this->app->alias(TemplateCache::class, 'autogen.template_cache');
        $this->app->alias(QueryOptimizer::class, 'autogen.query_optimizer');
        $this->app->alias(LazyLoader::class, 'autogen.lazy_loader');
    }

    /**
     * Publish configurations.
     */
    protected function publishConfigurations(): void
    {
        // Main configuration
        $this->publishes([
            __DIR__.'/../config/autogen.php' => config_path('autogen.php'),
        ], ['autogen-config', 'config']);

        // Views configuration
        $this->publishes([
            __DIR__.'/../config/autogen-views.php' => config_path('autogen-views.php'),
        ], ['autogen-views-config', 'config']);

        // Database provider configurations
        $this->publishes([
            __DIR__.'/../config/database-providers' => config_path('autogen/database-providers'),
        ], ['autogen-database-config', 'config']);

        // Merge configurations
        $this->mergeConfigFrom(__DIR__.'/../config/autogen.php', 'autogen');
        $this->mergeConfigFrom(__DIR__.'/../config/autogen-views.php', 'autogen-views');
    }

    /**
     * Publish stubs.
     */
    protected function publishStubs(): void
    {
        // Core stubs
        $this->publishes([
            __DIR__.'/../src/Packages/Model/Stubs' => resource_path('stubs/autogen/model'),
            __DIR__.'/../src/Packages/Controller/Stubs' => resource_path('stubs/autogen/controller'),
            __DIR__.'/../src/Packages/Views/Stubs' => resource_path('stubs/autogen/views'),
            __DIR__.'/../src/Packages/Migration/Stubs' => resource_path('stubs/autogen/migration'),
            __DIR__.'/../src/Packages/Factory/Stubs' => resource_path('stubs/autogen/factory'),
            __DIR__.'/../src/Packages/Datatable/Stubs' => resource_path('stubs/autogen/datatable'),
        ], ['autogen-stubs', 'stubs']);
    }

    /**
     * Publish assets.
     */
    protected function publishAssets(): void
    {
        // CSS frameworks
        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/autogen/css'),
        ], ['autogen-assets', 'assets']);

        // JavaScript assets
        $this->publishes([
            __DIR__.'/../resources/js' => public_path('vendor/autogen/js'),
        ], ['autogen-assets', 'assets']);

        // Example files
        $this->publishes([
            __DIR__.'/../examples' => base_path('vendor/autogen/examples'),
        ], ['autogen-examples', 'examples']);
    }

    /**
     * Register Blade components and directives.
     */
    protected function registerBladeComponents(): void
    {
        if (! class_exists(Blade::class)) {
            return;
        }

        try {
            // Register AutoGen Blade components namespace
            Blade::componentNamespace('AutoGen\\Components', 'autogen');

            // Register common directives with improved error handling
            $this->registerBladeDirectives();
        } catch (\Exception $e) {
            logger()->error("Failed to register AutoGen Blade components: {$e->getMessage()}");

            // In development, we want to know about this
            if (app()->environment('local', 'testing')) {
                throw $e;
            }
        }
    }

    /**
     * Register Blade directives following Laravel 12 patterns.
     */
    protected function registerBladeDirectives(): void
    {
        // AutoGen Form Directive
        Blade::directive('autogenForm', function ($expression) {
            return "<?php 
                try {
                    echo app('autogen.templates')->renderForm({$expression});
                } catch (\Exception \$e) {
                    if (app()->environment('local', 'testing')) {
                        throw \$e;
                    }
                    logger()->error('AutoGen Form Directive Error: ' . \$e->getMessage());
                    echo '<!-- AutoGen Form Error: ' . htmlspecialchars(\$e->getMessage()) . ' -->';
                }
            ?>";
        });

        // AutoGen Table Directive
        Blade::directive('autogenTable', function ($expression) {
            return "<?php 
                try {
                    echo app('autogen.templates')->renderTable({$expression});
                } catch (\Exception \$e) {
                    if (app()->environment('local', 'testing')) {
                        throw \$e;
                    }
                    logger()->error('AutoGen Table Directive Error: ' . \$e->getMessage());
                    echo '<!-- AutoGen Table Error: ' . htmlspecialchars(\$e->getMessage()) . ' -->';
                }
            ?>";
        });

        // AutoGen Filters Directive
        Blade::directive('autogenFilters', function ($expression) {
            return "<?php 
                try {
                    echo app('autogen.templates')->renderFilters({$expression});
                } catch (\Exception \$e) {
                    if (app()->environment('local', 'testing')) {
                        throw \$e;
                    }
                    logger()->error('AutoGen Filters Directive Error: ' . \$e->getMessage());
                    echo '<!-- AutoGen Filters Error: ' . htmlspecialchars(\$e->getMessage()) . ' -->';
                }
            ?>";
        });

        // AutoGen Config Directive - for accessing configuration in templates
        Blade::directive('autogenConfig', function ($expression) {
            return "<?php 
                try {
                    echo json_encode(app('autogen.config')->get({$expression}));
                } catch (\Exception \$e) {
                    if (app()->environment('local', 'testing')) {
                        throw \$e;
                    }
                    logger()->error('AutoGen Config Directive Error: ' . \$e->getMessage());
                    echo 'null';
                }
            ?>";
        });
    }

    /**
     * Register view composers.
     */
    protected function registerViewComposers(): void
    {
        // Global AutoGen variables available in all views
        view()->composer('*', function ($view) {
            try {
                $configManager = app(ConfigurationManager::class);
                $view->with('autogen', [
                    'version' => $this->getPackageVersion(),
                    'config' => $configManager->get(),
                    'isEnabled' => true,
                ]);
            } catch (\Exception $e) {
                // Fallback for when AutoGen is not properly configured
                $view->with('autogen', [
                    'version' => $this->getPackageVersion(),
                    'config' => [],
                    'isEnabled' => false,
                ]);

                if (app()->environment('local', 'testing')) {
                    logger()->warning("AutoGen view composer error: {$e->getMessage()}");
                }
            }
        });
    }

    /**
     * Register the package registry.
     */
    protected function registerPackageRegistry(): void
    {
        // Register the package registry interface
        $this->app->singleton(PackageRegistryInterface::class, function ($app) {
            $configManager = $app[ConfigurationManager::class];
            $registryConfig = $configManager->get('registry', []);
            
            return new PackageRegistry($app, $registryConfig);
        });
        
        // Alias for easier access
        $this->app->alias(PackageRegistryInterface::class, 'autogen.registry');
    }
    
    /**
     * Auto-discover packages in configured paths.
     */
    protected function autoDiscoverPackages(PackageRegistryInterface $registry): void
    {
        $configManager = $this->app->make(ConfigurationManager::class);
        $discoveryPaths = $configManager->get('registry.discovery_paths', []);
        
        foreach ($discoveryPaths as $path) {
            try {
                $packages = $registry->discover($path);
                
                foreach ($packages as $packageName => $packageClass) {
                    $packageConfig = $configManager->get("packages.{$packageName}", []);
                    $registry->registerByClassName($packageClass, $packageConfig);
                }
            } catch (\Throwable $e) {
                logger()->warning("Failed to discover packages in path: {$path}", [
                    'error' => $e->getMessage(),
                    'path' => $path
                ]);
            }
        }
    }
    
    /**
     * Register core packages manually.
     */
    protected function registerCorePackages(PackageRegistryInterface $registry): void
    {
        $configManager = $this->app->make(ConfigurationManager::class);
        
        // Core packages that should always be available
        $corePackages = [
            'AutoGen\Packages\Model\ModelPackage',
            'AutoGen\Packages\Controller\ControllerPackage',
            'AutoGen\Packages\Views\ViewsPackage',
            // Legacy providers that haven't been converted yet
            MigrationServiceProvider::class,
            FactoryPackageServiceProvider::class,
            DatatableServiceProvider::class,
        ];
        
        foreach ($corePackages as $packageClass) {
            try {
                // Extract package name from class name for config lookup
                $packageName = $this->extractPackageNameFromClass($packageClass);
                $packageConfig = $configManager->get("packages.{$packageName}", ['enabled' => true]);
                
                $registry->registerByClassName($packageClass, $packageConfig);
            } catch (\Throwable $e) {
                logger()->warning("Failed to register core package: {$packageClass}", [
                    'error' => $e->getMessage(),
                    'package' => $packageClass
                ]);
                
                // Fallback to old registration method for backward compatibility
                if (class_exists($packageClass)) {
                    $this->app->register($packageClass);
                }
            }
        }
    }
    
    /**
     * Boot packages using the registry system.
     */
    protected function bootRegisteredPackages(): void
    {
        try {
            $registry = $this->app->make(PackageRegistryInterface::class);
            $registry->bootPackages();
            
            // Log any package errors
            $errors = $registry->getErrors();
            if (!empty($errors)) {
                foreach ($errors as $packageName => $error) {
                    logger()->error("Package '{$packageName}' failed to load: {$error}");
                }
            }
        } catch (\Throwable $e) {
            logger()->error('Failed to boot registered packages', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // In development, we want to know about this
            if (app()->environment('local', 'testing')) {
                throw $e;
            }
        }
    }
    
    /**
     * Check if auto-discovery should be enabled.
     */
    protected function shouldAutoDiscoverPackages(): bool
    {
        $configManager = $this->app->make(ConfigurationManager::class);
        return $configManager->get('registry.enabled', true) && 
               $configManager->get('registry.auto_discovery', true);
    }
    
    /**
     * Extract package name from service provider class name.
     */
    protected function extractPackageNameFromClass(string $className): string
    {
        $baseName = class_basename($className);
        
        // Remove common suffixes
        $suffixes = ['ServiceProvider', 'PackageServiceProvider', 'Provider'];
        foreach ($suffixes as $suffix) {
            if (str_ends_with($baseName, $suffix)) {
                $baseName = substr($baseName, 0, -strlen($suffix));
                break;
            }
        }
        
        // Convert PascalCase to lowercase
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName));
    }
    
    /**
     * Initialize performance optimizations.
     */
    protected function initializePerformanceOptimizations(): void
    {
        if (!config('autogen.performance.monitoring.enabled', true)) {
            return;
        }

        try {
            // Warm caches if configured
            if (config('autogen.registry.package_cache.warm_on_boot', true)) {
                $this->warmPackageCache();
            }

            if (config('autogen.templates.caching.enabled', true)) {
                $this->warmTemplateCache();
            }

            // Initialize performance monitoring
            $monitor = $this->app->make(PerformanceMonitor::class);
            
            // Log successful initialization
            if (app()->environment('local')) {
                logger()->info('AutoGen performance optimizations initialized');
            }
        } catch (\Exception $e) {
            logger()->warning('Failed to initialize AutoGen performance optimizations', [
                'error' => $e->getMessage()
            ]);
            
            // Don't fail the application boot for performance optimization errors
        }
    }

    /**
     * Warm package cache on boot.
     */
    protected function warmPackageCache(): void
    {
        try {
            $registry = $this->app->make(PackageRegistryInterface::class);
            // The registry will warm itself during registration
        } catch (\Exception $e) {
            logger()->debug('Package cache warming skipped', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm template cache on boot.
     */
    protected function warmTemplateCache(): void
    {
        try {
            $templateCache = $this->app->make(TemplateCache::class);
            
            // Only warm basic patterns on boot to avoid slowing down startup
            $patterns = [
                'model' => ['model' => 'DefaultModel', 'namespace' => 'App\\Models'],
            ];
            
            $templateCache->warmCache($patterns);
        } catch (\Exception $e) {
            logger()->debug('Template cache warming skipped', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get package version.
     */
    protected function getPackageVersion(): string
    {
        $composerFile = __DIR__.'/../composer.json';

        if (! file_exists($composerFile)) {
            return '1.0.0';
        }

        if (! is_readable($composerFile)) {
            return '1.0.0';
        }

        try {
            $content = file_get_contents($composerFile);
            if ($content === false) {
                return '1.0.0';
            }

            $composer = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($composer)) {
                return '1.0.0';
            }

            return $composer['version'] ?? '1.0.0';
        } catch (\JsonException|\Exception) {
            return '1.0.0';
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'autogen',
            'autogen.config',
            'autogen.registry',
            'autogen.ai',
            'autogen.analyzer',
            'autogen.formatter',
            'autogen.templates',
            ConfigurationManager::class,
            ConfigurationValidator::class,
            PackageRegistryInterface::class,
            AIProviderManager::class,
            CodeAnalyzer::class,
            CodeFormatter::class,
            TemplateEngine::class,
            PerformanceMonitor::class,
            SchemaCache::class,
            TemplateCache::class,
            QueryOptimizer::class,
            LazyLoader::class,
        ];
    }
}
