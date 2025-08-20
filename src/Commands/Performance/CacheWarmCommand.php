<?php

declare(strict_types=1);

namespace AutoGen\Commands\Performance;

use AutoGen\Common\Performance\SchemaCache;
use AutoGen\Common\Performance\TemplateCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Cache warming command for AutoGen
 */
class CacheWarmCommand extends Command
{
    protected $signature = 'autogen:cache:warm 
                          {--type=* : Specific cache types to warm (schema, templates, all)}
                          {--connection=* : Specific database connections to warm schema cache for}
                          {--tables=* : Specific tables to warm schema cache for}
                          {--templates=* : Specific template types to warm}';

    protected $description = 'Warm up AutoGen caches for better performance';

    protected SchemaCache $schemaCache;
    protected TemplateCache $templateCache;

    public function __construct()
    {
        parent::__construct();
        $this->schemaCache = new SchemaCache(config('autogen.database.introspection', []));
        $this->templateCache = new TemplateCache(config('autogen.templates.caching', []));
    }

    public function handle(): int
    {
        $types = $this->option('type') ?: ['all'];
        
        $this->info('🔥 Warming up AutoGen caches...');
        $this->line('');

        $warmed = [];

        foreach ($types as $type) {
            switch ($type) {
                case 'schema':
                    if ($this->warmSchemaCache()) {
                        $warmed[] = 'Schema Cache';
                    }
                    break;
                
                case 'templates':
                    if ($this->warmTemplateCache()) {
                        $warmed[] = 'Template Cache';
                    }
                    break;
                
                case 'all':
                    if ($this->warmAllCaches()) {
                        $warmed[] = 'All Caches';
                    }
                    break;
                
                default:
                    $this->warn("Unknown cache type: {$type}");
            }
        }

        if (!empty($warmed)) {
            $this->info('✅ Successfully warmed: ' . implode(', ', $warmed));
            $this->line('');
            $this->showCacheStats();
        } else {
            $this->warn('⚠️  No caches were warmed');
        }

        return Command::SUCCESS;
    }

    /**
     * Warm schema cache.
     */
    protected function warmSchemaCache(): bool
    {
        $connections = $this->option('connection') ?: $this->getAvailableConnections();
        $specificTables = $this->option('tables');
        
        $warmedConnections = 0;
        $totalTables = 0;

        foreach ($connections as $connection) {
            try {
                $this->line("Warming schema cache for connection: {$connection}");
                
                $tables = $specificTables ?: $this->getTablesForConnection($connection);
                
                if (empty($tables)) {
                    $this->line("  No tables found for connection: {$connection}");
                    continue;
                }

                $progressBar = $this->output->createProgressBar(count($tables));
                $progressBar->setFormat('  %current%/%max% [%bar%] %percent:3s%% %message%');
                $progressBar->start();

                foreach ($tables as $table) {
                    try {
                        $progressBar->setMessage("Caching {$table}");
                        $this->schemaCache->getTableSchema($connection, $table);
                        $progressBar->advance();
                        $totalTables++;
                    } catch (\Exception $e) {
                        $progressBar->setMessage("Failed: {$table}");
                        $progressBar->advance();
                        continue;
                    }
                }

                $progressBar->finish();
                $this->line('');

                // Also warm relationships
                $this->schemaCache->getRelationships($connection);
                
                $warmedConnections++;
            } catch (\Exception $e) {
                $this->error("Failed to warm cache for connection {$connection}: {$e->getMessage()}");
                continue;
            }
        }

        if ($warmedConnections > 0) {
            $this->line("✅ Warmed schema cache for {$warmedConnections} connections ({$totalTables} tables)");
            return true;
        }

        return false;
    }

    /**
     * Warm template cache.
     */
    protected function warmTemplateCache(): bool
    {
        $templateTypes = $this->option('templates') ?: $this->getAvailableTemplateTypes();
        
        $this->line("Warming template cache...");
        
        try {
            // First, precompile all stub files
            $stubPaths = $this->getAllStubPaths();
            $precompiled = $this->templateCache->precompileStubs($stubPaths);
            
            $this->line("  Precompiled {$precompiled} stub files");

            // Then warm cache with common patterns
            $patterns = $this->getTemplatePatterns($templateTypes);
            $warmed = $this->templateCache->warmCache($patterns);
            
            $this->line("  Warmed {$warmed} template patterns");
            
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to warm template cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Warm all caches.
     */
    protected function warmAllCaches(): bool
    {
        $success = true;

        // Warm schema cache
        $this->line("=== Schema Cache ===");
        if (!$this->warmSchemaCache()) {
            $success = false;
        }
        $this->line('');

        // Warm template cache
        $this->line("=== Template Cache ===");
        if (!$this->warmTemplateCache()) {
            $success = false;
        }
        $this->line('');

        // Warm configuration cache
        $this->line("=== Configuration Cache ===");
        try {
            config()->all(); // Load all configuration
            $this->line("✅ Configuration cache warmed");
        } catch (\Exception $e) {
            $this->error("Failed to warm configuration cache: {$e->getMessage()}");
            $success = false;
        }

        return $success;
    }

    /**
     * Get available database connections.
     */
    protected function getAvailableConnections(): array
    {
        $connections = array_keys(config('database.connections', []));
        $available = [];

        foreach ($connections as $connection) {
            try {
                DB::connection($connection)->getPdo();
                $available[] = $connection;
            } catch (\Exception $e) {
                $this->warn("Skipping unavailable connection: {$connection}");
                continue;
            }
        }

        return $available;
    }

    /**
     * Get tables for a database connection.
     */
    protected function getTablesForConnection(string $connection): array
    {
        try {
            return DB::connection($connection)->getDoctrineSchemaManager()->listTableNames();
        } catch (\Exception $e) {
            $this->warn("Failed to get tables for connection {$connection}: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Get all stub file paths.
     */
    protected function getAllStubPaths(): array
    {
        $stubPaths = [];
        $packagesPath = __DIR__ . '/../../Packages';
        
        if (File::exists($packagesPath)) {
            $stubDirs = File::glob($packagesPath . '/*/Stubs');
            
            foreach ($stubDirs as $stubDir) {
                $stubs = File::allFiles($stubDir);
                foreach ($stubs as $stub) {
                    if ($stub->getExtension() === 'stub') {
                        $stubPaths[] = $stub->getPathname();
                    }
                }
            }
        }
        
        return $stubPaths;
    }

    /**
     * Get available template types.
     */
    protected function getAvailableTemplateTypes(): array
    {
        return [
            'model',
            'controller',
            'view',
            'migration',
            'factory',
            'datatable',
            'test',
        ];
    }

    /**
     * Get template patterns for warming cache.
     */
    protected function getTemplatePatterns(array $templateTypes): array
    {
        $patterns = [];

        foreach ($templateTypes as $type) {
            switch ($type) {
                case 'model':
                    $patterns['model'] = [
                        'model' => 'User',
                        'namespace' => 'App\\Models',
                        'fillable' => ['name', 'email'],
                        'casts' => ['email_verified_at' => 'datetime'],
                    ];
                    break;
                
                case 'controller':
                    $patterns['controller'] = [
                        'model' => 'User',
                        'controller' => 'UserController',
                        'namespace' => 'App\\Http\\Controllers',
                        'resource' => true,
                    ];
                    break;
                
                case 'view':
                    $patterns['view_index'] = [
                        'model' => 'User',
                        'view_type' => 'index',
                        'framework' => config('autogen.defaults.views_framework', 'tailwind'),
                    ];
                    $patterns['view_form'] = [
                        'model' => 'User',
                        'view_type' => 'form',
                        'framework' => config('autogen.defaults.views_framework', 'tailwind'),
                    ];
                    break;
                
                case 'migration':
                    $patterns['migration'] = [
                        'table' => 'users',
                        'action' => 'create',
                        'fields' => ['name', 'email', 'password'],
                    ];
                    break;
                
                case 'factory':
                    $patterns['factory'] = [
                        'model' => 'User',
                        'namespace' => 'Database\\Factories',
                        'fields' => ['name', 'email'],
                    ];
                    break;
                
                case 'datatable':
                    $patterns['datatable'] = [
                        'model' => 'User',
                        'type' => config('autogen.packages.datatable.default_type', 'yajra'),
                        'searchable' => ['name', 'email'],
                    ];
                    break;
                
                case 'test':
                    $patterns['test_feature'] = [
                        'model' => 'User',
                        'type' => 'feature',
                        'test_methods' => ['index', 'show', 'store'],
                    ];
                    break;
            }
        }

        return $patterns;
    }

    /**
     * Show cache statistics after warming.
     */
    protected function showCacheStats(): void
    {
        $this->info('📊 Cache Statistics:');

        try {
            // Schema cache stats
            $schemaStats = $this->schemaCache->getCacheStats();
            if (!empty($schemaStats)) {
                $this->line("Schema Cache:");
                $this->line("  • Total cached items: " . ($schemaStats['total_cached_items'] ?? 0));
                $this->line("  • Connections: " . count($schemaStats['connections'] ?? []));
                
                foreach ($schemaStats['connections'] ?? [] as $conn => $data) {
                    $this->line("    - {$conn}: " . ($data['tables_cached'] ?? 0) . ' tables');
                }
            }

            // Template cache stats
            $templateStats = $this->templateCache->getCacheStats();
            if (!empty($templateStats)) {
                $this->line("Template Cache:");
                $this->line("  • Cached templates: " . ($templateStats['cached_templates'] ?? 0));
                $this->line("  • Memory cache size: " . ($templateStats['memory_cache_size'] ?? 0));
                $this->line("  • Cache directory size: " . $this->formatBytes($templateStats['cache_directory_size'] ?? 0));
            }
            
        } catch (\Exception $e) {
            $this->warn("Could not retrieve cache statistics: {$e->getMessage()}");
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