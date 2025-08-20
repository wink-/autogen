<?php

declare(strict_types=1);

namespace AutoGen\Common\Configuration;

use AutoGen\Common\Enums\AIProvider;
use AutoGen\Common\Enums\CssFramework;
use AutoGen\Common\Enums\DatabaseProvider;
use AutoGen\Common\Enums\GeneratorType;
use AutoGen\Common\Traits\EnumBackwardCompatibility;
use AutoGen\Common\Validation\ConfigurationValidator;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Cache;

class ConfigurationManager
{
    use EnumBackwardCompatibility;

    protected ConfigRepository $config;

    protected ConfigurationValidator $validator;

    protected array $cachedConfig = [];

    public function __construct(ConfigRepository $config, ConfigurationValidator $validator)
    {
        $this->config = $config;
        $this->validator = $validator;
    }

    /**
     * Get configuration with validation and caching.
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        $config = $this->getValidatedConfig();

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    /**
     * Get validated configuration with caching.
     */
    protected function getValidatedConfig(): array
    {
        if (! empty($this->cachedConfig)) {
            return $this->cachedConfig;
        }

        try {
            // Try to use cache if available
            if (class_exists('\\Illuminate\\Support\\Facades\\Cache') && Cache::getStore()) {
                $cacheKey = 'autogen.validated_config';

                return $this->cachedConfig = Cache::remember($cacheKey, 3600, function () {
                    $config = $this->config->get('autogen', []);
                    $this->validator->validate($config);

                    return $this->processConfig($config);
                });
            }
        } catch (\Exception $e) {
            // Fall back to non-cached version
        }

        // Non-cached fallback
        $config = $this->config->get('autogen', []);
        $this->validator->validate($config);

        return $this->cachedConfig = $this->processConfig($config);
    }

    /**
     * Process and normalize configuration.
     */
    protected function processConfig(array $config): array
    {
        // Ensure all required sections exist with defaults
        $config = array_merge($this->getDefaultConfig(), $config);

        // Process dynamic configuration
        $config = $this->processDynamicValues($config);

        // Resolve configuration dependencies
        $config = $this->resolveConfigDependencies($config);

        return $config;
    }

    /**
     * Get default configuration structure.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'tables_to_ignore' => [
                'migrations',
                'failed_jobs',
                'password_resets',
                'password_reset_tokens',
                'personal_access_tokens',
                'cache',
                'cache_locks',
                'sessions',
                'jobs',
                'job_batches',
            ],
            'base_model' => \Illuminate\Database\Eloquent\Model::class,
            'naming_conventions' => [
                'model_suffix' => '',
                'controller_suffix' => 'Controller',
                'request_suffix' => 'Request',
                'resource_suffix' => 'Resource',
                'policy_suffix' => 'Policy',
            ],
            'defaults' => [
                'views_framework' => 'tailwind',
                'controller_type' => 'resource',
                'pagination' => 15,
                'api_version' => 'v1',
            ],
            'generation' => [
                'use_strict_types' => true,
                'add_docblocks' => true,
                'php_version' => '8.3',
                'file_permissions' => 0644,
            ],
            'performance' => [
                'enable_query_optimization' => true,
                'enable_caching' => true,
                'cache_driver' => 'redis',
                'cache_prefix' => 'autogen',
                'eager_load_relationships' => true,
                'use_database_transactions' => true,
                'chunk_size' => 1000,
                'pagination_size' => 15,
            ],
            'security' => [
                'enable_authorization' => true,
                'generate_policies' => true,
                'mass_assignment_protection' => true,
                'sql_injection_protection' => true,
                'xss_protection' => true,
                'csrf_protection' => true,
                'rate_limiting' => [
                    'enabled' => true,
                    'max_attempts' => 60,
                    'decay_minutes' => 1,
                ],
            ],
        ];
    }

    /**
     * Process dynamic configuration values.
     */
    protected function processDynamicValues(array $config): array
    {
        // Replace environment variables in nested arrays
        array_walk_recursive($config, function (&$value) {
            if (is_string($value) && str_starts_with($value, 'env(')) {
                $envVar = str_replace(['env(', ')'], '', $value);
                $parts = explode(',', $envVar, 2);
                $key = trim($parts[0], " '\"");
                $default = isset($parts[1]) ? trim($parts[1], " '\"") : null;
                $value = env($key, $default);
            }
        });

        return $config;
    }

    /**
     * Resolve configuration dependencies.
     */
    protected function resolveConfigDependencies(array $config): array
    {
        // Ensure output paths are absolute
        if (isset($config['generation']['output_paths'])) {
            foreach ($config['generation']['output_paths'] as $type => $path) {
                if (! str_starts_with($path, '/')) {
                    $config['generation']['output_paths'][$type] = base_path($path);
                }
            }
        }

        // Ensure custom stubs path is absolute if set
        if (! empty($config['custom_stubs_path']) && ! str_starts_with($config['custom_stubs_path'], '/')) {
            $config['custom_stubs_path'] = base_path($config['custom_stubs_path']);
        }

        return $config;
    }

    /**
     * Clear cached configuration.
     */
    public function clearCache(): void
    {
        $this->cachedConfig = [];
        Cache::forget('autogen.validated_config');
    }

    /**
     * Get configuration for a specific package.
     */
    public function getPackageConfig(string $package, array $defaults = []): array
    {
        $packageConfig = $this->get("packages.{$package}", []);

        return array_merge($defaults, $packageConfig);
    }

    /**
     * Check if a package is enabled.
     */
    public function isPackageEnabled(string $package): bool
    {
        return $this->get("packages.{$package}.enabled", true);
    }

    /**
     * Get AI provider configuration.
     */
    public function getAIProviderConfig(AIProvider|string|null $provider = null): array
    {
        if ($provider === null) {
            $provider = $this->get('ai.default_provider', 'openai');
        }

        $aiProvider = $this->toAIProvider($provider);
        return $this->get("ai.providers.{$aiProvider->value}", []);
    }

    /**
     * Get AI provider enum instance.
     */
    public function getAIProvider(AIProvider|string|null $provider = null): AIProvider
    {
        if ($provider === null) {
            $provider = $this->get('ai.default_provider', 'openai');
        }

        return $this->toAIProvider($provider);
    }

    /**
     * Get database configuration.
     */
    public function getDatabaseConfig(): array
    {
        return $this->get('database', []);
    }

    /**
     * Get database provider enum instance.
     */
    public function getDatabaseProvider(DatabaseProvider|string|null $provider = null): DatabaseProvider
    {
        if ($provider === null) {
            $provider = $this->get('database.default_connection', 'mysql');
        }

        return $this->toDatabaseProvider($provider);
    }

    /**
     * Get CSS framework enum instance for views.
     */
    public function getCssFramework(CssFramework|string|null $framework = null): CssFramework
    {
        if ($framework === null) {
            $framework = $this->get('packages.views.default_framework', 'tailwind');
        }

        return $this->toCssFramework($framework);
    }

    /**
     * Get generator types that are enabled.
     */
    public function getEnabledGeneratorTypes(): array
    {
        $enabledTypes = [];
        $packages = $this->get('packages', []);

        foreach (GeneratorType::cases() as $generatorType) {
            if ($this->isPackageEnabled($generatorType->value)) {
                $enabledTypes[] = $generatorType;
            }
        }

        return $enabledTypes;
    }

    /**
     * Get output path for a specific type.
     */
    public function getOutputPath(string $type): string
    {
        $paths = $this->get('generation.output_paths', []);

        if (! isset($paths[$type])) {
            throw new \InvalidArgumentException("Unknown output path type: {$type}");
        }

        return $paths[$type];
    }

    /**
     * Get namespace for a specific type.
     */
    public function getNamespace(string $type): string
    {
        $namespaces = $this->get('generation.namespaces', []);

        if (! isset($namespaces[$type])) {
            throw new \InvalidArgumentException("Unknown namespace type: {$type}");
        }

        return $namespaces[$type];
    }

    /**
     * Get validation warnings.
     */
    public function getWarnings(): array
    {
        return $this->validator->getWarnings($this->getValidatedConfig());
    }

    /**
     * Validate enum values in configuration.
     */
    public function validateEnumValues(): array
    {
        $warnings = [];
        $config = $this->getValidatedConfig();

        // Validate AI provider
        $defaultProvider = $config['ai']['default_provider'] ?? 'openai';
        if (!$this->isValidEnumValue(AIProvider::class, $defaultProvider)) {
            $warnings[] = "Invalid default AI provider '{$defaultProvider}'. Valid options: " . implode(', ', AIProvider::values());
        }

        // Validate CSS framework
        $defaultFramework = $config['packages']['views']['default_framework'] ?? 'tailwind';
        if (!$this->isValidEnumValue(CssFramework::class, $defaultFramework)) {
            $warnings[] = "Invalid CSS framework '{$defaultFramework}'. Valid options: " . implode(', ', CssFramework::values());
        }

        // Validate database provider
        $defaultConnection = $config['database']['default_connection'] ?? 'mysql';
        if (!$this->isValidEnumValue(DatabaseProvider::class, $defaultConnection)) {
            $warnings[] = "Invalid database provider '{$defaultConnection}'. Valid options: " . implode(', ', DatabaseProvider::values());
        }

        return $warnings;
    }

    /**
     * Get enum-safe configuration values.
     */
    public function getEnumSafeConfig(): array
    {
        $config = $this->getValidatedConfig();

        // Convert string values to enums where appropriate
        if (isset($config['ai']['default_provider'])) {
            $config['ai']['default_provider'] = $this->toAIProvider($config['ai']['default_provider']);
        }

        if (isset($config['packages']['views']['default_framework'])) {
            $config['packages']['views']['default_framework'] = $this->toCssFramework($config['packages']['views']['default_framework']);
        }

        if (isset($config['database']['default_connection'])) {
            $config['database']['default_connection'] = $this->toDatabaseProvider($config['database']['default_connection']);
        }

        return $config;
    }
}
