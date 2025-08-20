<?php

declare(strict_types=1);

namespace AutoGen\Commands\Performance;

use AutoGen\Common\Performance\PerformanceMonitor;
use AutoGen\Common\Performance\SchemaCache;
use AutoGen\Common\Performance\TemplateCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Performance optimization command
 */
class OptimizeCommand extends Command
{
    protected $signature = 'autogen:optimize 
                          {--clear-cache : Clear all caches before optimizing}
                          {--warm-cache : Warm up caches after optimization}
                          {--analyze : Analyze current performance}
                          {--suggestions : Show optimization suggestions}';

    protected $description = 'Optimize AutoGen performance by warming caches and analyzing bottlenecks';

    protected PerformanceMonitor $monitor;
    protected SchemaCache $schemaCache;
    protected TemplateCache $templateCache;

    public function __construct()
    {
        parent::__construct();
        $this->monitor = new PerformanceMonitor(config('autogen.performance.monitoring', []));
        $this->schemaCache = new SchemaCache(config('autogen.database.introspection', []));
        $this->templateCache = new TemplateCache(config('autogen.templates.caching', []));
    }

    public function handle(): int
    {
        $this->info('🚀 AutoGen Performance Optimization');
        $this->line('');

        // Clear caches if requested
        if ($this->option('clear-cache')) {
            $this->clearCaches();
            $this->line('');
        }

        // Analyze current performance if requested
        if ($this->option('analyze')) {
            $this->analyzePerformance();
            $this->line('');
        }

        // Run optimizations
        $this->runOptimizations();

        // Warm caches if requested
        if ($this->option('warm-cache')) {
            $this->warmCaches();
            $this->line('');
        }

        // Show suggestions if requested
        if ($this->option('suggestions')) {
            $this->showOptimizationSuggestions();
        }

        $this->info('✅ Performance optimization completed!');
        return Command::SUCCESS;
    }

    /**
     * Clear all AutoGen caches.
     */
    protected function clearCaches(): void
    {
        $this->info('🧹 Clearing AutoGen caches...');

        $clearedItems = 0;

        // Clear schema cache
        $this->task('Schema cache', function () use (&$clearedItems) {
            try {
                Cache::tags(['autogen', 'schema'])->flush();
                $clearedItems++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Clear template cache
        $this->task('Template cache', function () use (&$clearedItems) {
            try {
                $this->templateCache->clearCache();
                $clearedItems++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Clear performance metrics
        $this->task('Performance metrics', function () use (&$clearedItems) {
            try {
                $this->monitor->clearMetrics();
                $clearedItems++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Clear compiled views
        $this->task('Compiled views', function () use (&$clearedItems) {
            try {
                $viewCachePath = storage_path('framework/views');
                if (File::exists($viewCachePath)) {
                    File::cleanDirectory($viewCachePath);
                }
                $clearedItems++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        $this->info("Cleared {$clearedItems} cache types");
    }

    /**
     * Analyze current performance.
     */
    protected function analyzePerformance(): void
    {
        $this->info('📊 Analyzing current performance...');

        // Get performance statistics
        $performanceStats = $this->monitor->getStatistics();
        $schemaCacheStats = $this->schemaCache->getCacheStats();
        $templateCacheStats = $this->templateCache->getCacheStats();

        // Display performance metrics
        if (!empty($performanceStats)) {
            $this->info('Performance Metrics:');
            $headers = ['Metric', 'Value'];
            $rows = [
                ['Total Operations', $performanceStats['total_operations'] ?? 0],
                ['Average Execution Time', number_format($performanceStats['average_execution_time'] ?? 0, 4) . 's'],
                ['Max Execution Time', number_format($performanceStats['max_execution_time'] ?? 0, 4) . 's'],
                ['Average Memory Usage', $this->formatBytes($performanceStats['average_memory_usage'] ?? 0)],
                ['Max Memory Usage', $this->formatBytes($performanceStats['max_memory_usage'] ?? 0)],
                ['Slow Operations', $performanceStats['slow_operations_count'] ?? 0],
            ];
            $this->table($headers, $rows);
        } else {
            $this->warn('No performance metrics available. Run some AutoGen operations first.');
        }

        // Display cache statistics
        $this->info('Cache Statistics:');
        $cacheHeaders = ['Cache Type', 'Items', 'Hit Ratio', 'Size'];
        $cacheRows = [
            [
                'Schema Cache',
                $schemaCacheStats['total_cached_items'] ?? 0,
                number_format(($schemaCacheStats['cache_hit_ratio'] ?? 0) * 100, 1) . '%',
                'N/A'
            ],
            [
                'Template Cache',
                $templateCacheStats['cached_templates'] ?? 0,
                'N/A',
                $this->formatBytes($templateCacheStats['cache_directory_size'] ?? 0)
            ],
        ];
        $this->table($cacheHeaders, $cacheRows);
    }

    /**
     * Run performance optimizations.
     */
    protected function runOptimizations(): void
    {
        $this->info('⚡ Running performance optimizations...');

        $optimizations = 0;

        // Optimize configuration
        $this->task('Configuration optimization', function () use (&$optimizations) {
            try {
                $this->optimizeConfiguration();
                $optimizations++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Precompile templates
        $this->task('Template precompilation', function () use (&$optimizations) {
            try {
                $stubPaths = $this->getStubPaths();
                $compiled = $this->templateCache->precompileStubs($stubPaths);
                $this->line("  Precompiled {$compiled} templates");
                $optimizations++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Optimize database connections
        $this->task('Database optimization', function () use (&$optimizations) {
            try {
                $this->optimizeDatabase();
                $optimizations++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Clean up old cache files
        $this->task('Cache cleanup', function () use (&$optimizations) {
            try {
                $this->cleanupOldCacheFiles();
                $optimizations++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        $this->info("Applied {$optimizations} optimizations");
    }

    /**
     * Warm up caches.
     */
    protected function warmCaches(): void
    {
        $this->info('🔥 Warming up caches...');

        $warmedItems = 0;

        // Warm schema cache
        $this->task('Schema cache warming', function () use (&$warmedItems) {
            try {
                $connections = config('database.connections', []);
                foreach (array_keys($connections) as $connection) {
                    try {
                        $this->schemaCache->warmCache($connection);
                        $warmedItems++;
                    } catch (\Exception $e) {
                        // Skip failed connections
                        continue;
                    }
                }
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Warm template cache
        $this->task('Template cache warming', function () use (&$warmedItems) {
            try {
                $patterns = [
                    'model' => ['model' => 'User', 'namespace' => 'App\\Models'],
                    'controller' => ['model' => 'User', 'controller' => 'UserController'],
                    'view' => ['model' => 'User', 'framework' => 'tailwind'],
                ];
                
                $warmed = $this->templateCache->warmCache($patterns);
                $this->line("  Warmed {$warmed} template patterns");
                $warmedItems += $warmed;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        // Warm application cache
        $this->task('Application cache warming', function () use (&$warmedItems) {
            try {
                // Cache configuration
                config()->all();
                
                // Cache routes
                if (function_exists('route')) {
                    try {
                        app('router')->getRoutes();
                    } catch (\Exception $e) {
                        // Skip if routes not available
                    }
                }
                
                $warmedItems++;
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        $this->info("Warmed {$warmedItems} cache types");
    }

    /**
     * Show optimization suggestions.
     */
    protected function showOptimizationSuggestions(): void
    {
        $this->info('💡 Optimization Suggestions:');

        $suggestions = $this->generateSuggestions();

        if (empty($suggestions)) {
            $this->info('🎉 Great! No specific optimization suggestions at this time.');
            return;
        }

        foreach ($suggestions as $category => $items) {
            $this->line('');
            $this->info("📋 {$category}:");
            foreach ($items as $suggestion) {
                $this->line("  • {$suggestion}");
            }
        }
    }

    /**
     * Generate optimization suggestions based on current state.
     */
    protected function generateSuggestions(): array
    {
        $suggestions = [];

        // Check cache configuration
        $cacheDriver = config('cache.default');
        if (in_array($cacheDriver, ['file', 'array'])) {
            $suggestions['Cache Configuration'][] = "Consider using Redis or Memcached instead of '{$cacheDriver}' for better performance";
        }

        // Check database configuration
        $dbDriver = config('database.default');
        $dbConnection = config("database.connections.{$dbDriver}");
        
        if ($dbConnection && !($dbConnection['options'] ?? [])) {
            $suggestions['Database Configuration'][] = 'Consider adding database connection options for better performance (e.g., persistent connections)';
        }

        // Check memory limits
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== '-1' && (int) $memoryLimit < 256) {
            $suggestions['PHP Configuration'][] = "Consider increasing memory_limit (currently {$memoryLimit}) for large code generation operations";
        }

        // Check OPCache
        if (!extension_loaded('opcache') || !ini_get('opcache.enable')) {
            $suggestions['PHP Configuration'][] = 'Enable OPCache for better PHP performance';
        }

        // Check performance monitoring
        if (!config('autogen.performance.monitoring.enabled')) {
            $suggestions['AutoGen Configuration'][] = 'Enable performance monitoring to track optimization impact';
        }

        // Check template caching
        if (!config('autogen.templates.caching.enabled')) {
            $suggestions['AutoGen Configuration'][] = 'Enable template caching for faster code generation';
        }

        // Check schema caching
        if (!config('autogen.database.introspection.cache_schema')) {
            $suggestions['AutoGen Configuration'][] = 'Enable schema caching to reduce database introspection overhead';
        }

        return $suggestions;
    }

    /**
     * Optimize configuration settings.
     */
    protected function optimizeConfiguration(): void
    {
        // This could dynamically adjust configuration based on system capabilities
        // For now, just ensure optimal defaults are being used
        
        $optimizations = [
            'autogen.performance.enable_caching' => true,
            'autogen.performance.enable_query_optimization' => true,
            'autogen.database.introspection.cache_schema' => true,
            'autogen.templates.caching.enabled' => true,
        ];

        foreach ($optimizations as $key => $value) {
            config([$key => $value]);
        }
    }

    /**
     * Get all stub file paths.
     */
    protected function getStubPaths(): array
    {
        $stubPaths = [];
        $packagesPath = __DIR__ . '/../../Packages';
        
        if (File::exists($packagesPath)) {
            $stubDirs = File::glob($packagesPath . '/*/Stubs');
            
            foreach ($stubDirs as $stubDir) {
                $stubs = File::glob($stubDir . '/*.stub');
                $stubPaths = array_merge($stubPaths, $stubs);
            }
        }
        
        return $stubPaths;
    }

    /**
     * Optimize database settings.
     */
    protected function optimizeDatabase(): void
    {
        // Could optimize database connection settings here
        // For example, adjusting timeouts, connection limits, etc.
        
        // For now, just verify connections are working
        $connections = config('database.connections', []);
        
        foreach (array_keys($connections) as $connection) {
            try {
                \DB::connection($connection)->getPdo();
            } catch (\Exception $e) {
                // Skip failed connections
                continue;
            }
        }
    }

    /**
     * Clean up old cache files.
     */
    protected function cleanupOldCacheFiles(): void
    {
        $cacheDirectories = [
            storage_path('framework/cache/autogen'),
            storage_path('framework/views'),
            storage_path('app/autogen'),
        ];
        
        $cutoff = now()->subDays(7); // Remove files older than 7 days
        
        foreach ($cacheDirectories as $dir) {
            if (!File::exists($dir)) {
                continue;
            }
            
            $files = File::allFiles($dir);
            
            foreach ($files as $file) {
                if ($file->getMTime() < $cutoff->timestamp) {
                    try {
                        File::delete($file->getPathname());
                    } catch (\Exception $e) {
                        // Skip files that can't be deleted
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Format bytes into human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}