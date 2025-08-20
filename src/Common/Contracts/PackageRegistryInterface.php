<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

use AutoGen\Common\Exceptions\AutoGenException;
use Illuminate\Support\Collection;

interface PackageRegistryInterface
{
    /**
     * Register a package.
     */
    public function register(AutoGenPackageInterface $package): void;

    /**
     * Register a package by class name.
     */
    public function registerByClassName(string $className, array $config = []): void;

    /**
     * Get a registered package by name.
     */
    public function get(string $name): ?AutoGenPackageInterface;

    /**
     * Get all registered packages.
     */
    public function all(): Collection;

    /**
     * Get enabled packages only.
     */
    public function enabled(): Collection;

    /**
     * Check if a package is registered.
     */
    public function has(string $name): bool;

    /**
     * Check if a package is enabled.
     */
    public function isEnabled(string $name): bool;

    /**
     * Enable a package.
     */
    public function enable(string $name): bool;

    /**
     * Disable a package.
     */
    public function disable(string $name): bool;

    /**
     * Discover packages in the given directory.
     */
    public function discover(string $directory): Collection;

    /**
     * Boot all enabled packages.
     * 
     * @throws AutoGenException
     */
    public function bootPackages(): void;

    /**
     * Register all enabled packages with the container.
     * 
     * @throws AutoGenException
     */
    public function registerPackages(): void;

    /**
     * Get package load errors.
     */
    public function getErrors(): array;

    /**
     * Clear all registered packages.
     */
    public function clear(): void;
}