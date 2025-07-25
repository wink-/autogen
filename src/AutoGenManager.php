<?php

declare(strict_types=1);

namespace AutoGen;

use AutoGen\Common\AI\AIProviderManager;
use AutoGen\Common\Analysis\CodeAnalyzer;
use AutoGen\Common\Formatting\CodeFormatter;
use AutoGen\Common\Templates\TemplateEngine;
use Illuminate\Contracts\Container\Container;

/**
 * AutoGen Manager
 * 
 * Central manager for the AutoGen package suite providing
 * access to all core services and utilities.
 */
class AutoGenManager
{
    /**
     * The application instance.
     */
    protected Container $app;

    /**
     * Create a new AutoGen manager instance.
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Get the AI provider manager.
     */
    public function ai(): AIProviderManager
    {
        return $this->app->make(AIProviderManager::class);
    }

    /**
     * Get the code analyzer.
     */
    public function analyzer(): CodeAnalyzer
    {
        return $this->app->make(CodeAnalyzer::class);
    }

    /**
     * Get the code formatter.
     */
    public function formatter(): CodeFormatter
    {
        return $this->app->make(CodeFormatter::class);
    }

    /**
     * Get the template engine.
     */
    public function templates(): TemplateEngine
    {
        return $this->app->make(TemplateEngine::class);
    }

    /**
     * Get package information.
     */
    public function getPackageInfo(): array
    {
        return [
            'name' => 'Laravel AutoGen Package Suite',
            'version' => $this->getVersion(),
            'description' => 'Comprehensive CRUD generation from existing database schemas',
            'author' => 'AutoGen Contributors',
            'license' => 'MIT',
            'homepage' => 'https://github.com/autogen/laravel-autogen',
            'documentation' => 'https://docs.autogen.dev',
        ];
    }

    /**
     * Get package version.
     */
    public function getVersion(): string
    {
        $composerFile = __DIR__ . '/../composer.json';
        
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? '1.0.0';
        }

        return '1.0.0';
    }

    /**
     * Get available AutoGen commands.
     */
    public function getAvailableCommands(): array
    {
        return [
            'Core Generators' => [
                'autogen:model' => 'Generate Eloquent models from database tables',
                'autogen:controller' => 'Generate resource controllers with form requests',
                'autogen:views' => 'Generate Blade view templates',
                'autogen:migration' => 'Generate migrations from existing schemas',
                'autogen:factory' => 'Generate model factories with realistic data',
            ],
            'Advanced Generators' => [
                'autogen:datatable' => 'Generate high-performance datatables',
                'autogen:test' => 'Generate comprehensive test suites',
                'autogen:docs' => 'Generate API documentation',
            ],
            'Orchestration' => [
                'autogen:scaffold' => 'Generate complete CRUD interfaces',
                'autogen:analyze' => 'Analyze code quality and performance',
                'autogen:optimize' => 'Optimize generated code',
            ],
            'Utilities' => [
                'autogen:list-tables' => 'List database tables',
                'autogen:test-connection' => 'Test database connections',
                'autogen:clear-cache' => 'Clear AutoGen caches',
            ],
        ];
    }

    /**
     * Check if a package is enabled.
     */
    public function isPackageEnabled(string $package): bool
    {
        return config("autogen.packages.{$package}.enabled", false);
    }

    /**
     * Get package configuration.
     */
    public function getPackageConfig(string $package): array
    {
        return config("autogen.packages.{$package}", []);
    }

    /**
     * Get all enabled packages.
     */
    public function getEnabledPackages(): array
    {
        $packages = config('autogen.packages', []);
        
        return array_filter($packages, function ($config) {
            return $config['enabled'] ?? false;
        });
    }

    /**
     * Get database connections configured for AutoGen.
     */
    public function getDatabaseConnections(): array
    {
        $connections = [];
        $defaultConnection = config('autogen.database.default_connection', 'mysql');
        $legacyConnections = config('autogen.database.legacy_connections', []);

        $connections[$defaultConnection] = config("database.connections.{$defaultConnection}");

        foreach ($legacyConnections as $name => $config) {
            $connections[$name] = $config;
        }

        return array_filter($connections);
    }

    /**
     * Get AI providers configuration.
     */
    public function getAIProviders(): array
    {
        return config('autogen.ai.providers', []);
    }

    /**
     * Get the default AI provider.
     */
    public function getDefaultAIProvider(): string
    {
        return config('autogen.ai.default_provider', 'openai');
    }

    /**
     * Check if AI features are enabled.
     */
    public function isAIEnabled(): bool
    {
        $providers = $this->getAIProviders();
        $defaultProvider = $this->getDefaultAIProvider();

        return !empty($providers[$defaultProvider]['api_key']);
    }

    /**
     * Get performance settings.
     */
    public function getPerformanceSettings(): array
    {
        return config('autogen.performance', []);
    }

    /**
     * Get security settings.
     */
    public function getSecuritySettings(): array
    {
        return config('autogen.security', []);
    }

    /**
     * Clear all AutoGen caches.
     */
    public function clearCaches(): bool
    {
        try {
            // Clear schema cache
            cache()->tags(['autogen', 'schema'])->flush();
            
            // Clear template cache
            cache()->tags(['autogen', 'templates'])->flush();
            
            // Clear AI response cache
            cache()->tags(['autogen', 'ai'])->flush();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        return [
            'schema_cache_size' => cache()->tags(['autogen', 'schema'])->get('size', 0),
            'template_cache_size' => cache()->tags(['autogen', 'templates'])->get('size', 0),
            'ai_cache_size' => cache()->tags(['autogen', 'ai'])->get('size', 0),
            'total_cached_items' => cache()->tags(['autogen'])->get('count', 0),
        ];
    }

    /**
     * Get system requirements status.
     */
    public function getSystemRequirements(): array
    {
        return [
            'php_version' => [
                'required' => '8.3.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 'pass' : 'fail',
            ],
            'laravel_version' => [
                'required' => '12.0.0',
                'current' => app()->version(),
                'status' => version_compare(app()->version(), '12.0.0', '>=') ? 'pass' : 'fail',
            ],
            'extensions' => [
                'pdo' => extension_loaded('pdo') ? 'pass' : 'fail',
                'json' => extension_loaded('json') ? 'pass' : 'fail',
                'mbstring' => extension_loaded('mbstring') ? 'pass' : 'fail',
                'openssl' => extension_loaded('openssl') ? 'pass' : 'fail',
                'curl' => extension_loaded('curl') ? 'pass' : 'fail',
            ],
            'database_drivers' => [
                'mysql' => extension_loaded('pdo_mysql') ? 'available' : 'missing',
                'pgsql' => extension_loaded('pdo_pgsql') ? 'available' : 'missing',
                'sqlite' => extension_loaded('pdo_sqlite') ? 'available' : 'missing',
            ],
        ];
    }

    /**
     * Get health check status.
     */
    public function getHealthStatus(): array
    {
        $requirements = $this->getSystemRequirements();
        $connections = $this->getDatabaseConnections();
        
        $status = [
            'overall' => 'healthy',
            'requirements' => 'pass',
            'database' => 'pass',
            'ai' => $this->isAIEnabled() ? 'enabled' : 'disabled',
            'cache' => 'working',
            'issues' => [],
        ];

        // Check requirements
        foreach ($requirements as $category => $checks) {
            if (is_array($checks)) {
                foreach ($checks as $check => $result) {
                    if (is_array($result) && $result['status'] === 'fail') {
                        $status['requirements'] = 'fail';
                        $status['overall'] = 'unhealthy';
                        $status['issues'][] = "Missing requirement: {$category}.{$check}";
                    } elseif ($result === 'fail' || $result === 'missing') {
                        $status['requirements'] = 'fail';
                        $status['overall'] = 'unhealthy';
                        $status['issues'][] = "Missing requirement: {$category}.{$check}";
                    }
                }
            }
        }

        // Check database connections
        foreach ($connections as $name => $config) {
            try {
                \DB::connection($name)->getPdo();
            } catch (\Exception $e) {
                $status['database'] = 'fail';
                $status['overall'] = 'unhealthy';
                $status['issues'][] = "Database connection failed: {$name}";
            }
        }

        // Check cache
        try {
            cache()->put('autogen_health_check', true, 60);
            if (!cache()->get('autogen_health_check')) {
                $status['cache'] = 'fail';
                $status['overall'] = 'degraded';
                $status['issues'][] = 'Cache system not working';
            }
        } catch (\Exception $e) {
            $status['cache'] = 'fail';
            $status['overall'] = 'degraded';
            $status['issues'][] = 'Cache system error: ' . $e->getMessage();
        }

        return $status;
    }
}