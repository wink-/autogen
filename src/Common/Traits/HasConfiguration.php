<?php

declare(strict_types=1);

namespace AutoGen\Common\Traits;

trait HasConfiguration
{
    /**
     * The configuration array.
     */
    protected array $config = [];

    /**
     * Set the configuration.
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Get the configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get a configuration value.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Set a configuration value.
     */
    public function setConfigValue(string $key, mixed $value): void
    {
        data_set($this->config, $key, $value);
    }

    /**
     * Check if a configuration key exists.
     */
    public function hasConfig(string $key): bool
    {
        return data_get($this->config, $key) !== null;
    }

    /**
     * Merge configuration with defaults.
     */
    protected function mergeWithDefaults(array $defaults): void
    {
        $this->config = array_merge($defaults, $this->config);
    }
}