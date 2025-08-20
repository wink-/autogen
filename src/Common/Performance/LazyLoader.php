<?php

declare(strict_types=1);

namespace AutoGen\Common\Performance;

use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Lazy loading implementation for expensive operations
 */
class LazyLoader
{
    protected array $config;
    protected array $lazyValues = [];
    protected array $loadingStates = [];
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'memory_limit' => 128 * 1024 * 1024, // 128MB
            'max_lazy_items' => 1000,
            'cleanup_threshold' => 0.8, // Clean up when 80% full
        ], $config);
    }

    /**
     * Create a lazy-loaded value.
     */
    public function lazy(string $key, Closure $loader, array $dependencies = []): LazyValue
    {
        if (!$this->config['enabled']) {
            return new LazyValue($key, $loader(), true);
        }

        // Check if already loaded
        if (isset($this->lazyValues[$key])) {
            return $this->lazyValues[$key];
        }

        // Create lazy value
        $lazyValue = new LazyValue($key, $loader, false, $dependencies);
        $this->lazyValues[$key] = $lazyValue;
        
        // Cleanup if needed
        $this->cleanupIfNeeded();
        
        return $lazyValue;
    }

    /**
     * Force load a lazy value.
     */
    public function load(string $key): mixed
    {
        if (!isset($this->lazyValues[$key])) {
            throw new \InvalidArgumentException("Lazy value not found: {$key}");
        }

        return $this->lazyValues[$key]->getValue();
    }

    /**
     * Check if a value is loaded.
     */
    public function isLoaded(string $key): bool
    {
        return isset($this->lazyValues[$key]) && $this->lazyValues[$key]->isLoaded();
    }

    /**
     * Preload multiple lazy values.
     */
    public function preload(array $keys): array
    {
        $results = [];
        $startTime = microtime(true);
        
        foreach ($keys as $key) {
            if (isset($this->lazyValues[$key])) {
                $results[$key] = $this->lazyValues[$key]->getValue();
            }
        }
        
        $executionTime = microtime(true) - $startTime;
        
        Log::info('Lazy values preloaded', [
            'keys' => $keys,
            'loaded_count' => count($results),
            'execution_time' => $executionTime,
        ]);
        
        return $results;
    }

    /**
     * Invalidate lazy values by dependencies.
     */
    public function invalidateByDependency(string $dependency): int
    {
        $invalidated = 0;
        
        foreach ($this->lazyValues as $key => $lazyValue) {
            if (in_array($dependency, $lazyValue->getDependencies())) {
                $this->invalidate($key);
                $invalidated++;
            }
        }
        
        Log::info("Invalidated {$invalidated} lazy values by dependency: {$dependency}");
        
        return $invalidated;
    }

    /**
     * Invalidate a specific lazy value.
     */
    public function invalidate(string $key): void
    {
        if (isset($this->lazyValues[$key])) {
            $this->lazyValues[$key]->invalidate();
            unset($this->lazyValues[$key]);
        }
    }

    /**
     * Clear all lazy values.
     */
    public function clear(): void
    {
        foreach ($this->lazyValues as $lazyValue) {
            $lazyValue->invalidate();
        }
        
        $this->lazyValues = [];
        $this->loadingStates = [];
        
        Log::info('All lazy values cleared');
    }

    /**
     * Get lazy loading statistics.
     */
    public function getStats(): array
    {
        $totalValues = count($this->lazyValues);
        $loadedValues = 0;
        $memoryUsage = 0;
        
        foreach ($this->lazyValues as $lazyValue) {
            if ($lazyValue->isLoaded()) {
                $loadedValues++;
                $memoryUsage += $lazyValue->getMemoryUsage();
            }
        }
        
        return [
            'total_values' => $totalValues,
            'loaded_values' => $loadedValues,
            'unloaded_values' => $totalValues - $loadedValues,
            'memory_usage' => $memoryUsage,
            'memory_limit' => $this->config['memory_limit'],
            'load_ratio' => $totalValues > 0 ? $loadedValues / $totalValues : 0,
        ];
    }

    /**
     * Cleanup if memory limits are reached.
     */
    protected function cleanupIfNeeded(): void
    {
        $stats = $this->getStats();
        
        // Cleanup if memory usage is high
        if ($stats['memory_usage'] > $this->config['memory_limit'] * $this->config['cleanup_threshold']) {
            $this->cleanup();
        }
        
        // Cleanup if too many items
        if ($stats['total_values'] > $this->config['max_lazy_items']) {
            $this->cleanup();
        }
    }

    /**
     * Cleanup least recently used values.
     */
    protected function cleanup(): void
    {
        $beforeCount = count($this->lazyValues);
        
        // Sort by last access time (LRU)
        $values = $this->lazyValues;
        uasort($values, function (LazyValue $a, LazyValue $b) {
            return $a->getLastAccess() <=> $b->getLastAccess();
        });
        
        $toRemove = (int) (count($values) * 0.3); // Remove 30%
        $removed = 0;
        
        foreach ($values as $key => $lazyValue) {
            if ($removed >= $toRemove) {
                break;
            }
            
            if ($lazyValue->isLoaded()) {
                $this->invalidate($key);
                $removed++;
            }
        }
        
        $afterCount = count($this->lazyValues);
        
        Log::info('Lazy loader cleanup completed', [
            'before_count' => $beforeCount,
            'after_count' => $afterCount,
            'removed_count' => $beforeCount - $afterCount,
        ]);
    }

    /**
     * Create a memoized function.
     */
    public function memoize(Closure $function, string $keyPrefix = 'memo'): Closure
    {
        return function (...$args) use ($function, $keyPrefix) {
            $key = $keyPrefix . '_' . md5(serialize($args));
            
            if (!isset($this->lazyValues[$key])) {
                $this->lazyValues[$key] = new LazyValue(
                    $key,
                    fn() => $function(...$args)
                );
            }
            
            return $this->lazyValues[$key]->getValue();
        };
    }
}

/**
 * Lazy value container
 */
class LazyValue
{
    protected string $key;
    protected mixed $value;
    protected bool $loaded;
    protected Closure|null $loader;
    protected array $dependencies;
    protected float $lastAccess;
    protected int $memoryUsage = 0;

    public function __construct(string $key, mixed $valueOrLoader, bool $loaded = false, array $dependencies = [])
    {
        $this->key = $key;
        $this->dependencies = $dependencies;
        $this->lastAccess = microtime(true);
        
        if ($loaded || !($valueOrLoader instanceof Closure)) {
            $this->value = $valueOrLoader;
            $this->loaded = true;
            $this->loader = null;
            $this->calculateMemoryUsage();
        } else {
            $this->loader = $valueOrLoader;
            $this->loaded = false;
            $this->value = null;
        }
    }

    /**
     * Get the value, loading if necessary.
     */
    public function getValue(): mixed
    {
        if (!$this->loaded) {
            $this->load();
        }
        
        $this->lastAccess = microtime(true);
        return $this->value;
    }

    /**
     * Check if value is loaded.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Get dependencies.
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Get last access time.
     */
    public function getLastAccess(): float
    {
        return $this->lastAccess;
    }

    /**
     * Get estimated memory usage.
     */
    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }

    /**
     * Invalidate the lazy value.
     */
    public function invalidate(): void
    {
        $this->value = null;
        $this->loaded = false;
        $this->memoryUsage = 0;
    }

    /**
     * Load the value using the loader.
     */
    protected function load(): void
    {
        if ($this->loaded || !$this->loader) {
            return;
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            $this->value = ($this->loader)();
            $this->loaded = true;
            
            $endMemory = memory_get_usage(true);
            $this->memoryUsage = $endMemory - $startMemory;
            
            $executionTime = microtime(true) - $startTime;
            
            Log::debug("Lazy value loaded: {$this->key}", [
                'execution_time' => $executionTime,
                'memory_usage' => $this->memoryUsage,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to load lazy value: {$this->key}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        } finally {
            $this->loader = null; // Free the closure
        }
    }

    /**
     * Calculate memory usage of the stored value.
     */
    protected function calculateMemoryUsage(): void
    {
        if ($this->value === null) {
            $this->memoryUsage = 0;
            return;
        }

        // Rough estimation of memory usage
        if (is_string($this->value)) {
            $this->memoryUsage = strlen($this->value);
        } elseif (is_array($this->value)) {
            $this->memoryUsage = count($this->value) * 100; // Rough estimate
        } elseif (is_object($this->value)) {
            $this->memoryUsage = 1000; // Rough estimate for objects
        } else {
            $this->memoryUsage = 100; // Default estimate
        }
    }
}