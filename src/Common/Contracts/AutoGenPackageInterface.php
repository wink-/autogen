<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

use Illuminate\Contracts\Container\Container;

interface AutoGenPackageInterface
{
    /**
     * Get the package name.
     */
    public function getName(): string;

    /**
     * Get the package description.
     */
    public function getDescription(): string;

    /**
     * Get the package version.
     */
    public function getVersion(): string;

    /**
     * Get package dependencies.
     */
    public function getDependencies(): array;

    /**
     * Get package configuration.
     */
    public function getConfig(): array;

    /**
     * Check if the package is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Enable the package.
     */
    public function enable(): void;

    /**
     * Disable the package.
     */
    public function disable(): void;

    /**
     * Check if package requirements are met.
     */
    public function canRegister(Container $container): bool;

    /**
     * Register the package services.
     */
    public function register(Container $container): void;

    /**
     * Boot the package services.
     */
    public function boot(Container $container): void;

    /**
     * Get commands provided by this package.
     */
    public function getCommands(): array;

    /**
     * Get service providers provided by this package.
     */
    public function getServiceProviders(): array;

    /**
     * Get configuration files that should be published.
     */
    public function getConfigFiles(): array;

    /**
     * Get view files that should be published.
     */
    public function getViews(): array;

    /**
     * Get asset files that should be published.
     */
    public function getAssets(): array;

    /**
     * Handle package registration failure.
     */
    public function handleFailure(\Throwable $exception): void;

    /**
     * Get the package priority for loading order.
     * Lower numbers load first.
     */
    public function getPriority(): int;
}