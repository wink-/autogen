<?php

declare(strict_types=1);

namespace AutoGen\Common\Package;

use AutoGen\Common\Contracts\AutoGenPackageInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

abstract class AbstractAutoGenPackage implements AutoGenPackageInterface
{
    /**
     * Package configuration.
     */
    protected array $config;

    /**
     * Package enabled state.
     */
    protected bool $enabled;

    /**
     * Package name.
     */
    protected string $name;

    /**
     * Package description.
     */
    protected string $description = '';

    /**
     * Package version.
     */
    protected string $version = '1.0.0';

    /**
     * Package dependencies.
     */
    protected array $dependencies = [];

    /**
     * Package priority for loading order.
     * Lower numbers load first.
     */
    protected int $priority = 100;

    /**
     * Commands provided by this package.
     */
    protected array $commands = [];

    /**
     * Service providers provided by this package.
     */
    protected array $serviceProviders = [];

    /**
     * Configuration files.
     */
    protected array $configFiles = [];

    /**
     * View files.
     */
    protected array $views = [];

    /**
     * Asset files.
     */
    protected array $assets = [];

    /**
     * Create a new package instance.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->enabled = $config['enabled'] ?? true;
        $this->name = $this->name ?? $this->getDefaultName();
        
        $this->configure();
    }

    /**
     * Get the package name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the package description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the package version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get package dependencies.
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Get package configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Check if the package is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable the package.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable the package.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if package requirements are met.
     */
    public function canRegister(Container $container): bool
    {
        // Check if dependencies are satisfied
        foreach ($this->dependencies as $dependency) {
            if (is_string($dependency)) {
                if (!class_exists($dependency)) {
                    return false;
                }
            } elseif (is_array($dependency)) {
                $class = $dependency['class'] ?? null;
                $version = $dependency['version'] ?? null;
                
                if (!$class || !class_exists($class)) {
                    return false;
                }
                
                // Additional version checking could be implemented here
            }
        }

        return $this->checkRequirements($container);
    }

    /**
     * Register the package services.
     */
    public function register(Container $container): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->registerBindings($container);
            $this->registerCommands($container);
            $this->registerServiceProviders($container);
            $this->registerConfigurations($container);
        } catch (\Throwable $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Boot the package services.
     */
    public function boot(Container $container): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->bootPackage($container);
            $this->publishAssets();
            $this->registerViews($container);
        } catch (\Throwable $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Get commands provided by this package.
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get service providers provided by this package.
     */
    public function getServiceProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * Get configuration files that should be published.
     */
    public function getConfigFiles(): array
    {
        return $this->configFiles;
    }

    /**
     * Get view files that should be published.
     */
    public function getViews(): array
    {
        return $this->views;
    }

    /**
     * Get asset files that should be published.
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    /**
     * Handle package registration failure.
     */
    public function handleFailure(\Throwable $exception): void
    {
        Log::error("AutoGen Package '{$this->name}' failed to load", [
            'package' => $this->name,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Additional failure handling can be implemented in child classes
        $this->onFailure($exception);
    }

    /**
     * Get the package priority for loading order.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Configure the package.
     * Override this method in child classes to set up package properties.
     */
    protected function configure(): void
    {
        // Override in child classes
    }

    /**
     * Check package-specific requirements.
     */
    protected function checkRequirements(Container $container): bool
    {
        // Override in child classes for custom requirement checks
        return true;
    }

    /**
     * Register package bindings.
     */
    protected function registerBindings(Container $container): void
    {
        // Override in child classes
    }

    /**
     * Register package commands.
     */
    protected function registerCommands(Container $container): void
    {
        if (!empty($this->commands) && $container->bound('artisan')) {
            foreach ($this->commands as $command) {
                if (class_exists($command)) {
                    $container->make('artisan')->resolve($command);
                }
            }
        }
    }

    /**
     * Register package service providers.
     */
    protected function registerServiceProviders(Container $container): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (class_exists($provider)) {
                $container->register($provider);
            }
        }
    }

    /**
     * Register package configurations.
     */
    protected function registerConfigurations(Container $container): void
    {
        // Override in child classes
    }

    /**
     * Boot the package.
     */
    protected function bootPackage(Container $container): void
    {
        // Override in child classes
    }

    /**
     * Publish package assets.
     */
    protected function publishAssets(): void
    {
        // Override in child classes
    }

    /**
     * Register package views.
     */
    protected function registerViews(Container $container): void
    {
        // Override in child classes
    }

    /**
     * Handle package failure.
     */
    protected function onFailure(\Throwable $exception): void
    {
        // Override in child classes for custom failure handling
    }

    /**
     * Get the default package name from class name.
     */
    protected function getDefaultName(): string
    {
        $className = class_basename(static::class);
        
        // Remove common suffixes
        $suffixes = ['Package', 'ServiceProvider', 'Provider'];
        foreach ($suffixes as $suffix) {
            if (str_ends_with($className, $suffix)) {
                $className = substr($className, 0, -strlen($suffix));
                break;
            }
        }

        // Convert PascalCase to kebab-case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
    }
}