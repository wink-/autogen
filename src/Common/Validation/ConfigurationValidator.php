<?php

declare(strict_types=1);

namespace AutoGen\Common\Validation;

use AutoGen\Common\Enums\AIProvider;
use AutoGen\Common\Enums\CssFramework;
use AutoGen\Common\Enums\DatatableType;
use AutoGen\Common\Exceptions\AutoGenException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ConfigurationValidator
{
    /**
     * Validate AutoGen configuration.
     */
    public function validate(array $config): void
    {
        $this->validateRequiredKeys($config);
        $this->validateDatabaseConnections($config);
        $this->validateFilePaths($config);
        $this->validateAIProviders($config);
        $this->validatePackageSettings($config);
    }

    /**
     * Validate required configuration keys exist.
     */
    protected function validateRequiredKeys(array $config): void
    {
        $requiredKeys = [
            'defaults',
            'generation.output_paths',
            'generation.namespaces',
            'database.default_connection',
            'ai.default_provider',
            'ai.providers',
        ];

        foreach ($requiredKeys as $key) {
            if (! data_get($config, $key)) {
                throw new AutoGenException("Required configuration key '{$key}' is missing.");
            }
        }
    }

    /**
     * Validate database connections are valid.
     */
    protected function validateDatabaseConnections(array $config): void
    {
        $defaultConnection = $config['database']['default_connection'];

        try {
            DB::connection($defaultConnection)->getPdo();
        } catch (\Exception $e) {
            throw new AutoGenException(
                "Default database connection '{$defaultConnection}' is invalid: {$e->getMessage()}"
            );
        }

        // Validate legacy connections if configured
        $legacyConnections = $config['database']['legacy_connections'] ?? [];
        foreach ($legacyConnections as $name => $connectionConfig) {
            if (empty($connectionConfig['driver'])) {
                throw new AutoGenException(
                    "Legacy connection '{$name}' is missing required 'driver' configuration."
                );
            }
        }
    }

    /**
     * Validate file paths are writable.
     */
    protected function validateFilePaths(array $config): void
    {
        $outputPaths = $config['generation']['output_paths'] ?? [];

        foreach ($outputPaths as $type => $path) {
            $fullPath = base_path($path);
            $directory = dirname($fullPath);

            if (! File::isDirectory($directory)) {
                throw new AutoGenException(
                    "Output directory for '{$type}' does not exist: {$directory}"
                );
            }

            if (! File::isWritable($directory)) {
                throw new AutoGenException(
                    "Output directory for '{$type}' is not writable: {$directory}"
                );
            }
        }

        // Validate custom stubs path if set
        if ($config['custom_stubs_path']) {
            if (! File::isDirectory($config['custom_stubs_path'])) {
                throw new AutoGenException(
                    "Custom stubs path does not exist: {$config['custom_stubs_path']}"
                );
            }
        }
    }

    /**
     * Validate AI provider configurations.
     */
    protected function validateAIProviders(array $config): void
    {
        $defaultProvider = $config['ai']['default_provider'];
        $providers = $config['ai']['providers'] ?? [];

        if (! isset($providers[$defaultProvider])) {
            throw new AutoGenException(
                "Default AI provider '{$defaultProvider}' is not configured in providers list."
            );
        }

        foreach ($providers as $name => $providerConfig) {
            $aiProvider = AIProvider::tryFrom($name);
            if ($aiProvider === null) {
                throw new AutoGenException(
                    "Unknown AI provider '{$name}'. Valid providers: " . implode(', ', AIProvider::values())
                );
            }

            foreach ($aiProvider->requiredEnvVars() as $envVar) {
                $configKey = strtolower(str_replace('_', '_', $envVar));
                if (empty($providerConfig['api_key']) && str_contains($configKey, 'api_key')) {
                    throw new AutoGenException(
                        "{$aiProvider->label()} provider is missing API key. Please set {$envVar} environment variable."
                    );
                }
                if (empty($providerConfig['endpoint']) && str_contains($configKey, 'endpoint')) {
                    throw new AutoGenException(
                        "{$aiProvider->label()} provider is missing endpoint. Please set {$envVar} environment variable."
                    );
                }
            }
        }
    }

    /**
     * Validate package-specific settings.
     */
    protected function validatePackageSettings(array $config): void
    {
        $packages = $config['packages'] ?? [];

        foreach ($packages as $packageName => $packageConfig) {
            if (! is_array($packageConfig)) {
                throw new AutoGenException(
                    "Package configuration for '{$packageName}' must be an array."
                );
            }

            // Validate framework-specific settings
            if ($packageName === 'views' && isset($packageConfig['default_framework'])) {
                if (CssFramework::tryFrom($packageConfig['default_framework']) === null) {
                    throw new AutoGenException(
                        "Invalid view framework '{$packageConfig['default_framework']}'. Valid options: ".
                        implode(', ', CssFramework::values())
                    );
                }
            }

            if ($packageName === 'datatable' && isset($packageConfig['default_type'])) {
                if (DatatableType::tryFrom($packageConfig['default_type']) === null) {
                    throw new AutoGenException(
                        "Invalid datatable type '{$packageConfig['default_type']}'. Valid options: ".
                        implode(', ', DatatableType::values())
                    );
                }
            }
        }
    }

    /**
     * Get validation warnings (non-critical issues).
     */
    public function getWarnings(array $config): array
    {
        $warnings = [];

        // Check for performance optimizations
        if (! ($config['performance']['enable_caching'] ?? true)) {
            $warnings[] = 'Caching is disabled. Consider enabling for better performance.';
        }

        if (! ($config['security']['enable_authorization'] ?? true)) {
            $warnings[] = 'Authorization is disabled. This may create security vulnerabilities.';
        }

        // Check for deprecated settings
        if (isset($config['column_type_mappings'])) {
            $warnings[] = 'column_type_mappings configuration is deprecated. Use package-specific mappings instead.';
        }

        return $warnings;
    }
}
