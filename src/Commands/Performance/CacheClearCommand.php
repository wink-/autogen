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
 * Cache management command for AutoGen
 */
class CacheClearCommand extends Command
{
    protected $signature = 'autogen:cache:clear 
                          {--type=* : Specific cache types to clear (schema, templates, performance, all)}
                          {--connection=* : Specific database connections to clear schema cache for}
                          {--force : Force clear without confirmation}';

    protected $description = 'Clear AutoGen caches';

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
        $types = $this->option('type') ?: ['all'];
        $connections = $this->option('connection');
        $force = $this->option('force');

        // Confirmation for clearing all caches
        if (in_array('all', $types) && !$force) {
            if (!$this->confirm('This will clear all AutoGen caches. Are you sure?')) {
                $this->info('Cache clearing cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('🧹 Clearing AutoGen caches...');
        $this->line('');

        $cleared = [];

        foreach ($types as $type) {
            switch ($type) {
                case 'schema':
                    if ($this->clearSchemaCache($connections)) {
                        $cleared[] = 'Schema Cache';
                    }
                    break;
                
                case 'templates':
                    if ($this->clearTemplateCache()) {
                        $cleared[] = 'Template Cache';
                    }
                    break;
                
                case 'performance':
                    if ($this->clearPerformanceCache()) {
                        $cleared[] = 'Performance Cache';
                    }
                    break;
                
                case 'all':
                    if ($this->clearAllCaches()) {
                        $cleared[] = 'All Caches';
                    }
                    break;
                
                default:
                    $this->warn("Unknown cache type: {$type}");
            }
        }

        if (!empty($cleared)) {
            $this->info('✅ Successfully cleared: ' . implode(', ', $cleared));
        } else {
            $this->warn('⚠️  No caches were cleared');
        }

        return Command::SUCCESS;
    }

    /**
     * Clear schema cache.
     */
    protected function clearSchemaCache(?array $connections = null): bool
    {
        try {
            if ($connections) {
                foreach ($connections as $connection) {
                    $this->schemaCache->invalidateConnection($connection);
                    $this->line("  Cleared schema cache for connection: {$connection}");
                }
            } else {
                Cache::tags(['autogen', 'schema'])->flush();
                $this->line("  Cleared all schema caches");
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to clear schema cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear template cache.
     */
    protected function clearTemplateCache(): bool
    {
        try {
            $this->templateCache->clearCache();
            $this->line("  Cleared template cache");
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to clear template cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear performance cache.
     */
    protected function clearPerformanceCache(): bool
    {
        try {
            $this->monitor->clearMetrics();
            Cache::tags(['autogen', 'performance'])->flush();
            $this->line("  Cleared performance metrics and cache");
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to clear performance cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear all AutoGen caches.
     */
    protected function clearAllCaches(): bool
    {
        $success = true;

        // Clear schema cache
        if (!$this->clearSchemaCache()) {
            $success = false;
        }

        // Clear template cache
        if (!$this->clearTemplateCache()) {
            $success = false;
        }

        // Clear performance cache
        if (!$this->clearPerformanceCache()) {
            $success = false;
        }

        // Clear other AutoGen-related caches
        try {
            Cache::tags(['autogen'])->flush();
            $this->line("  Cleared general AutoGen cache");
        } catch (\Exception $e) {
            $this->error("Failed to clear general cache: {$e->getMessage()}");
            $success = false;
        }

        // Clear compiled views that might be AutoGen-related
        try {
            $viewPath = storage_path('framework/views');
            if (File::exists($viewPath)) {
                $files = File::glob($viewPath . '/*autogen*');
                foreach ($files as $file) {
                    File::delete($file);
                }
                if (!empty($files)) {
                    $this->line("  Cleared " . count($files) . " AutoGen view cache files");
                }
            }
        } catch (\Exception $e) {
            $this->error("Failed to clear view cache: {$e->getMessage()}");
            $success = false;
        }

        // Clear AutoGen-specific storage cache
        try {
            $storageCachePath = storage_path('app/autogen');
            if (File::exists($storageCachePath)) {
                File::deleteDirectory($storageCachePath);
                $this->line("  Cleared AutoGen storage cache");
            }
        } catch (\Exception $e) {
            $this->error("Failed to clear storage cache: {$e->getMessage()}");
            $success = false;
        }

        return $success;
    }
}