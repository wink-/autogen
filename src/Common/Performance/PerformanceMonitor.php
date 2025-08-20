<?php

declare(strict_types=1);

namespace AutoGen\Common\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance monitoring for AutoGen operations
 */
class PerformanceMonitor
{
    protected array $config;
    protected array $activeOperations = [];
    protected array $metrics = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'track_generation_time' => true,
            'track_memory_usage' => true,
            'track_query_count' => true,
            'slow_query_threshold' => 1.0,
            'memory_usage_threshold' => 128 * 1024 * 1024,
        ], $config);
    }

    /**
     * Start monitoring an operation.
     */
    public function startOperation(string $operation, array $context = []): string
    {
        if (!$this->config['enabled']) {
            return '';
        }

        $operationId = uniqid($operation . '_', true);
        
        $this->activeOperations[$operationId] = [
            'operation' => $operation,
            'context' => $context,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'start_queries' => $this->getQueryCount(),
        ];

        return $operationId;
    }

    /**
     * End monitoring an operation.
     */
    public function endOperation(string $operationId): array
    {
        if (!$this->config['enabled'] || !isset($this->activeOperations[$operationId])) {
            return [];
        }

        $operation = $this->activeOperations[$operationId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endQueries = $this->getQueryCount();

        $metrics = [
            'operation' => $operation['operation'],
            'context' => $operation['context'],
            'execution_time' => $endTime - $operation['start_time'],
            'memory_usage' => $endMemory - $operation['start_memory'],
            'peak_memory' => memory_get_peak_usage(true),
            'query_count' => $endQueries - $operation['start_queries'],
            'timestamp' => now(),
        ];

        unset($this->activeOperations[$operationId]);

        // Store metrics
        $this->storeMetrics($metrics);

        // Log slow operations
        if ($metrics['execution_time'] > $this->config['slow_query_threshold']) {
            $this->logSlowOperation($metrics);
        }

        // Log high memory usage
        if ($metrics['memory_usage'] > $this->config['memory_usage_threshold']) {
            $this->logHighMemoryUsage($metrics);
        }

        return $metrics;
    }

    /**
     * Get current performance metrics.
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get aggregated performance statistics.
     */
    public function getStatistics(string $operation = null): array
    {
        try {
            // Check if cache is available
            if (!class_exists('\\Illuminate\\Support\\Facades\\Cache') || !Cache::getStore()) {
                return $this->calculateStatisticsFromMemory($operation);
            }

            $cacheKey = "autogen_performance_stats" . ($operation ? "_{$operation}" : "");
            
            return Cache::remember($cacheKey, 300, function () use ($operation) {
                $metrics = $this->loadStoredMetrics($operation);
                
                if (empty($metrics)) {
                    return [];
                }

                return [
                    'total_operations' => count($metrics),
                    'average_execution_time' => collect($metrics)->avg('execution_time'),
                    'max_execution_time' => collect($metrics)->max('execution_time'),
                    'min_execution_time' => collect($metrics)->min('execution_time'),
                    'average_memory_usage' => collect($metrics)->avg('memory_usage'),
                    'max_memory_usage' => collect($metrics)->max('memory_usage'),
                    'average_query_count' => collect($metrics)->avg('query_count'),
                    'max_query_count' => collect($metrics)->max('query_count'),
                    'slow_operations_count' => collect($metrics)->filter(function ($metric) {
                        return $metric['execution_time'] > $this->config['slow_query_threshold'];
                    })->count(),
                ];
            });
        } catch (\Exception $e) {
            return $this->calculateStatisticsFromMemory($operation);
        }
    }

    /**
     * Calculate statistics from in-memory metrics (fallback).
     */
    protected function calculateStatisticsFromMemory(string $operation = null): array
    {
        $metrics = $operation 
            ? array_filter($this->metrics, fn($m) => ($m['operation'] ?? '') === $operation)
            : $this->metrics;
        
        if (empty($metrics)) {
            return [];
        }

        $executionTimes = array_column($metrics, 'execution_time');
        $memoryUsages = array_column($metrics, 'memory_usage');
        $queryCounts = array_column($metrics, 'query_count');

        return [
            'total_operations' => count($metrics),
            'average_execution_time' => array_sum($executionTimes) / count($executionTimes),
            'max_execution_time' => max($executionTimes),
            'min_execution_time' => min($executionTimes),
            'average_memory_usage' => array_sum($memoryUsages) / count($memoryUsages),
            'max_memory_usage' => max($memoryUsages),
            'average_query_count' => array_sum($queryCounts) / count($queryCounts),
            'max_query_count' => max($queryCounts),
            'slow_operations_count' => count(array_filter($metrics, fn($m) => 
                $m['execution_time'] > $this->config['slow_query_threshold']
            )),
        ];
    }

    /**
     * Clear performance metrics.
     */
    public function clearMetrics(): void
    {
        $this->metrics = [];
        Cache::tags(['autogen', 'performance'])->flush();
    }

    /**
     * Store performance metrics.
     */
    protected function storeMetrics(array $metrics): void
    {
        $this->metrics[] = $metrics;
        
        // Store in cache with tags for easy cleanup
        $cacheKey = "autogen_performance_metric_" . time() . "_" . uniqid();
        Cache::tags(['autogen', 'performance'])->put($cacheKey, $metrics, 3600);
        
        // Keep only recent metrics in memory (last 100)
        if (count($this->metrics) > 100) {
            $this->metrics = array_slice($this->metrics, -100);
        }
    }

    /**
     * Load stored metrics from cache.
     */
    protected function loadStoredMetrics(string $operation = null): array
    {
        $pattern = "autogen_performance_metric_*";
        $keys = Cache::tags(['autogen', 'performance'])->getMany($pattern);
        
        $metrics = collect($keys)->filter(function ($metric) use ($operation) {
            return !$operation || ($metric['operation'] ?? '') === $operation;
        })->values()->toArray();

        return $metrics;
    }

    /**
     * Log slow operations.
     */
    protected function logSlowOperation(array $metrics): void
    {
        Log::warning('AutoGen slow operation detected', [
            'operation' => $metrics['operation'],
            'execution_time' => $metrics['execution_time'],
            'memory_usage' => $metrics['memory_usage'],
            'query_count' => $metrics['query_count'],
            'context' => $metrics['context'],
        ]);
    }

    /**
     * Log high memory usage.
     */
    protected function logHighMemoryUsage(array $metrics): void
    {
        Log::warning('AutoGen high memory usage detected', [
            'operation' => $metrics['operation'],
            'memory_usage' => $metrics['memory_usage'],
            'peak_memory' => $metrics['peak_memory'],
            'execution_time' => $metrics['execution_time'],
            'context' => $metrics['context'],
        ]);
    }

    /**
     * Get current query count (if query log is enabled).
     */
    protected function getQueryCount(): int
    {
        if (!$this->config['track_query_count']) {
            return 0;
        }

        try {
            // Check if Laravel DB facade is available and has been set
            if (!class_exists('\\Illuminate\\Support\\Facades\\DB')) {
                return 0;
            }
            
            // Check if facade root is set (needed for testing)
            if (!\DB::getFacadeRoot()) {
                return 0;
            }

            return count(\DB::getQueryLog());
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Start profiling session.
     */
    public function startProfiling(): void
    {
        if (!$this->config['enabled'] || !extension_loaded('xdebug')) {
            return;
        }

        if (function_exists('xdebug_start_trace')) {
            $traceFile = storage_path('logs/autogen_profile_' . date('Y-m-d_H-i-s') . '.xt');
            xdebug_start_trace($traceFile);
        }
    }

    /**
     * Stop profiling session.
     */
    public function stopProfiling(): void
    {
        if (!$this->config['enabled'] || !extension_loaded('xdebug')) {
            return;
        }

        if (function_exists('xdebug_stop_trace')) {
            xdebug_stop_trace();
        }
    }

    /**
     * Get memory usage formatted.
     */
    public function getFormattedMemoryUsage(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}