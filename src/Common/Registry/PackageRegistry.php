<?php

declare(strict_types=1);

namespace AutoGen\Common\Registry;

use AutoGen\Common\Contracts\AutoGenPackageInterface;
use AutoGen\Common\Contracts\PackageRegistryInterface;
use AutoGen\Common\Exceptions\AutoGenException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class PackageRegistry implements PackageRegistryInterface
{
    /**
     * Container instance.
     */
    protected Container $container;

    /**
     * Registered packages.
     */
    protected Collection $packages;

    /**
     * Package configuration.
     */
    protected array $config;

    /**
     * Package load errors.
     */
    protected array $errors = [];

    /**
     * Create a new package registry instance.
     */
    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->packages = new Collection;
        $this->config = $config;
    }

    /**
     * Register a package.
     */
    public function register(AutoGenPackageInterface $package): void
    {
        $name = $package->getName();

        try {
            // Check if package can be registered
            if (!$package->canRegister($this->container)) {
                $this->errors[$name] = 'Package requirements not met';
                return;
            }

            // Store the package
            $this->packages->put($name, $package);

            // If enabled, register with container
            if ($package->isEnabled()) {
                $package->register($this->container);
            }
        } catch (\Throwable $e) {
            $this->errors[$name] = $e->getMessage();
            $package->handleFailure($e);
            
            if (app()->environment('local', 'testing')) {
                throw new AutoGenException(
                    "Failed to register package '{$name}': {$e->getMessage()}",
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Register a package by class name.
     */
    public function registerByClassName(string $className, array $config = []): void
    {
        try {
            if (!class_exists($className)) {
                $this->errors[$className] = "Class '{$className}' does not exist";
                return;
            }

            if (!is_subclass_of($className, AutoGenPackageInterface::class)) {
                $this->errors[$className] = "Class '{$className}' must implement AutoGenPackageInterface";
                return;
            }

            $package = new $className($config);
            $this->register($package);
        } catch (\Throwable $e) {
            $this->errors[$className] = $e->getMessage();
            
            if (app()->environment('local', 'testing')) {
                throw new AutoGenException(
                    "Failed to register package by class name '{$className}': {$e->getMessage()}",
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Get a registered package by name.
     */
    public function get(string $name): ?AutoGenPackageInterface
    {
        return $this->packages->get($name);
    }

    /**
     * Get all registered packages.
     */
    public function all(): Collection
    {
        return $this->packages->sortBy(function (AutoGenPackageInterface $package) {
            return $package->getPriority();
        });
    }

    /**
     * Get enabled packages only.
     */
    public function enabled(): Collection
    {
        return $this->packages->filter(function (AutoGenPackageInterface $package) {
            return $package->isEnabled();
        })->sortBy(function (AutoGenPackageInterface $package) {
            return $package->getPriority();
        });
    }

    /**
     * Check if a package is registered.
     */
    public function has(string $name): bool
    {
        return $this->packages->has($name);
    }

    /**
     * Check if a package is enabled.
     */
    public function isEnabled(string $name): bool
    {
        $package = $this->get($name);
        return $package ? $package->isEnabled() : false;
    }

    /**
     * Enable a package.
     */
    public function enable(string $name): bool
    {
        $package = $this->get($name);
        
        if (!$package) {
            return false;
        }

        try {
            $package->enable();
            
            // Register with container if not already registered
            if ($package->canRegister($this->container)) {
                $package->register($this->container);
                return true;
            }
            
            return false;
        } catch (\Throwable $e) {
            $this->errors[$name] = $e->getMessage();
            return false;
        }
    }

    /**
     * Disable a package.
     */
    public function disable(string $name): bool
    {
        $package = $this->get($name);
        
        if (!$package) {
            return false;
        }

        try {
            $package->disable();
            return true;
        } catch (\Throwable $e) {
            $this->errors[$name] = $e->getMessage();
            return false;
        }
    }

    /**
     * Discover packages in the given directory.
     */
    public function discover(string $directory): Collection
    {
        $discoveredPackages = new Collection;

        if (!File::exists($directory) || !File::isDirectory($directory)) {
            return $discoveredPackages;
        }

        try {
            $directories = File::directories($directory);

            foreach ($directories as $packageDir) {
                $packageName = basename($packageDir);
                $serviceProviderFile = $packageDir . '/' . ucfirst($packageName) . 'ServiceProvider.php';

                // Look for service provider files that implement our package interface
                if (File::exists($serviceProviderFile)) {
                    $className = $this->extractClassNameFromFile($serviceProviderFile);
                    
                    if ($className && class_exists($className) && 
                        is_subclass_of($className, AutoGenPackageInterface::class)) {
                        
                        $discoveredPackages->put($packageName, $className);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->errors['discovery'] = $e->getMessage();
        }

        return $discoveredPackages;
    }

    /**
     * Boot all enabled packages.
     */
    public function bootPackages(): void
    {
        foreach ($this->enabled() as $package) {
            try {
                $package->boot($this->container);
            } catch (\Throwable $e) {
                $name = $package->getName();
                $this->errors[$name] = "Boot failed: {$e->getMessage()}";
                $package->handleFailure($e);

                if (app()->environment('local', 'testing')) {
                    throw new AutoGenException(
                        "Failed to boot package '{$name}': {$e->getMessage()}",
                        0,
                        $e
                    );
                }
            }
        }
    }

    /**
     * Register all enabled packages with the container.
     */
    public function registerPackages(): void
    {
        foreach ($this->enabled() as $package) {
            try {
                if ($package->canRegister($this->container)) {
                    $package->register($this->container);
                }
            } catch (\Throwable $e) {
                $name = $package->getName();
                $this->errors[$name] = "Registration failed: {$e->getMessage()}";
                $package->handleFailure($e);

                if (app()->environment('local', 'testing')) {
                    throw new AutoGenException(
                        "Failed to register package '{$name}': {$e->getMessage()}",
                        0,
                        $e
                    );
                }
            }
        }
    }

    /**
     * Get package load errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Clear all registered packages.
     */
    public function clear(): void
    {
        $this->packages = new Collection;
        $this->errors = [];
    }

    /**
     * Extract class name from a PHP file.
     */
    protected function extractClassNameFromFile(string $filePath): ?string
    {
        try {
            $content = File::get($filePath);
            
            // Extract namespace
            preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
            $namespace = $namespaceMatches[1] ?? '';
            
            // Extract class name
            preg_match('/class\s+(\w+)/', $content, $classMatches);
            $className = $classMatches[1] ?? null;
            
            if ($className && $namespace) {
                return $namespace . '\\' . $className;
            }
            
            return $className;
        } catch (\Throwable $e) {
            return null;
        }
    }
}