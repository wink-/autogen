<?php

declare(strict_types=1);

namespace AutoGen\Common\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Schema caching system with intelligent invalidation
 */
class SchemaCache
{
    protected array $config;
    protected array $memoryCache = [];
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'ttl' => 3600,
            'prefix' => 'autogen_schema',
            'warm_on_boot' => true,
            'track_changes' => true,
            'compression' => true,
        ], $config);
    }

    /**
     * Get table schema with caching.
     */
    public function getTableSchema(string $connection, string $table): ?array
    {
        if (!$this->config['enabled']) {
            return $this->fetchTableSchema($connection, $table);
        }

        $cacheKey = $this->getCacheKey('table', $connection, $table);
        
        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            return $this->memoryCache[$cacheKey];
        }

        // Check persistent cache
        $schema = Cache::tags(['autogen', 'schema', $connection])->get($cacheKey);
        
        if ($schema !== null) {
            // Decompress if needed
            if ($this->config['compression'] && is_string($schema)) {
                $schema = unserialize(gzuncompress($schema));
            }
            
            $this->memoryCache[$cacheKey] = $schema;
            return $schema;
        }

        // Fetch from database
        $schema = $this->fetchTableSchema($connection, $table);
        
        if ($schema) {
            $this->cacheTableSchema($connection, $table, $schema);
        }

        return $schema;
    }

    /**
     * Get all tables schema for a connection.
     */
    public function getAllTablesSchema(string $connection): array
    {
        $cacheKey = $this->getCacheKey('all_tables', $connection);
        
        if (!$this->config['enabled']) {
            return $this->fetchAllTablesSchema($connection);
        }

        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            return $this->memoryCache[$cacheKey];
        }

        // Check persistent cache
        $schemas = Cache::tags(['autogen', 'schema', $connection])->get($cacheKey);
        
        if ($schemas !== null) {
            if ($this->config['compression'] && is_string($schemas)) {
                $schemas = unserialize(gzuncompress($schemas));
            }
            
            $this->memoryCache[$cacheKey] = $schemas;
            return $schemas;
        }

        // Fetch from database
        $schemas = $this->fetchAllTablesSchema($connection);
        
        if (!empty($schemas)) {
            $this->cacheAllTablesSchema($connection, $schemas);
        }

        return $schemas;
    }

    /**
     * Get relationship information with caching.
     */
    public function getRelationships(string $connection): array
    {
        $cacheKey = $this->getCacheKey('relationships', $connection);
        
        if (!$this->config['enabled']) {
            return $this->fetchRelationships($connection);
        }

        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            return $this->memoryCache[$cacheKey];
        }

        // Check persistent cache
        $relationships = Cache::tags(['autogen', 'schema', $connection])->get($cacheKey);
        
        if ($relationships !== null) {
            if ($this->config['compression'] && is_string($relationships)) {
                $relationships = unserialize(gzuncompress($relationships));
            }
            
            $this->memoryCache[$cacheKey] = $relationships;
            return $relationships;
        }

        // Fetch from database
        $relationships = $this->fetchRelationships($connection);
        
        if (!empty($relationships)) {
            $this->cacheRelationships($connection, $relationships);
        }

        return $relationships;
    }

    /**
     * Cache table schema.
     */
    protected function cacheTableSchema(string $connection, string $table, array $schema): void
    {
        $cacheKey = $this->getCacheKey('table', $connection, $table);
        
        // Store in memory cache
        $this->memoryCache[$cacheKey] = $schema;
        
        // Prepare data for persistent cache
        $data = $schema;
        if ($this->config['compression']) {
            $data = gzcompress(serialize($schema));
        }
        
        // Store in persistent cache
        Cache::tags(['autogen', 'schema', $connection, $table])->put(
            $cacheKey, 
            $data, 
            $this->config['ttl']
        );
        
        // Update cache metadata
        $this->updateCacheMetadata($connection, $table);
    }

    /**
     * Cache all tables schema.
     */
    protected function cacheAllTablesSchema(string $connection, array $schemas): void
    {
        $cacheKey = $this->getCacheKey('all_tables', $connection);
        
        // Store in memory cache
        $this->memoryCache[$cacheKey] = $schemas;
        
        // Prepare data for persistent cache
        $data = $schemas;
        if ($this->config['compression']) {
            $data = gzcompress(serialize($schemas));
        }
        
        // Store in persistent cache
        Cache::tags(['autogen', 'schema', $connection])->put(
            $cacheKey, 
            $data, 
            $this->config['ttl']
        );
    }

    /**
     * Cache relationships.
     */
    protected function cacheRelationships(string $connection, array $relationships): void
    {
        $cacheKey = $this->getCacheKey('relationships', $connection);
        
        // Store in memory cache
        $this->memoryCache[$cacheKey] = $relationships;
        
        // Prepare data for persistent cache
        $data = $relationships;
        if ($this->config['compression']) {
            $data = gzcompress(serialize($relationships));
        }
        
        // Store in persistent cache
        Cache::tags(['autogen', 'schema', $connection])->put(
            $cacheKey, 
            $data, 
            $this->config['ttl']
        );
    }

    /**
     * Invalidate cache for specific table.
     */
    public function invalidateTable(string $connection, string $table): void
    {
        // Clear from memory cache
        $tableKey = $this->getCacheKey('table', $connection, $table);
        unset($this->memoryCache[$tableKey]);
        
        $allTablesKey = $this->getCacheKey('all_tables', $connection);
        unset($this->memoryCache[$allTablesKey]);
        
        // Clear from persistent cache
        Cache::tags(['autogen', 'schema', $connection, $table])->flush();
        Cache::tags(['autogen', 'schema', $connection])->forget($allTablesKey);
        
        Log::info("Schema cache invalidated for table: {$connection}.{$table}");
    }

    /**
     * Invalidate cache for entire connection.
     */
    public function invalidateConnection(string $connection): void
    {
        // Clear memory cache for this connection
        $this->memoryCache = array_filter($this->memoryCache, function ($key) use ($connection) {
            return !str_contains($key, "_{$connection}_");
        }, ARRAY_FILTER_USE_KEY);
        
        // Clear persistent cache
        Cache::tags(['autogen', 'schema', $connection])->flush();
        
        Log::info("Schema cache invalidated for connection: {$connection}");
    }

    /**
     * Warm cache for connection.
     */
    public function warmCache(string $connection, array $tables = []): void
    {
        if (empty($tables)) {
            $tables = $this->getTableNames($connection);
        }

        foreach ($tables as $table) {
            $this->getTableSchema($connection, $table);
        }
        
        // Cache relationships
        $this->getRelationships($connection);
        
        Log::info("Schema cache warmed for connection: {$connection}", [
            'tables_cached' => count($tables)
        ]);
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $stats = [
            'memory_cache_size' => count($this->memoryCache),
            'connections' => [],
            'total_cached_items' => 0,
            'cache_hit_ratio' => 0.0,
        ];

        // Get cache metadata
        $metadata = Cache::tags(['autogen', 'schema'])->get('cache_metadata', []);
        
        foreach ($metadata as $connection => $data) {
            $stats['connections'][$connection] = [
                'tables_cached' => $data['tables_count'] ?? 0,
                'last_updated' => $data['last_updated'] ?? null,
                'cache_hits' => $data['hits'] ?? 0,
                'cache_misses' => $data['misses'] ?? 0,
            ];
            
            $stats['total_cached_items'] += $data['tables_count'] ?? 0;
            
            $totalRequests = ($data['hits'] ?? 0) + ($data['misses'] ?? 0);
            if ($totalRequests > 0) {
                $stats['cache_hit_ratio'] = ($data['hits'] ?? 0) / $totalRequests;
            }
        }

        return $stats;
    }

    /**
     * Update cache metadata.
     */
    protected function updateCacheMetadata(string $connection, string $table): void
    {
        $metadataKey = 'cache_metadata';
        $metadata = Cache::tags(['autogen', 'schema'])->get($metadataKey, []);
        
        if (!isset($metadata[$connection])) {
            $metadata[$connection] = [
                'tables_count' => 0,
                'tables' => [],
                'hits' => 0,
                'misses' => 0,
            ];
        }
        
        if (!in_array($table, $metadata[$connection]['tables'])) {
            $metadata[$connection]['tables'][] = $table;
            $metadata[$connection]['tables_count'] = count($metadata[$connection]['tables']);
        }
        
        $metadata[$connection]['last_updated'] = now()->toISOString();
        
        Cache::tags(['autogen', 'schema'])->put($metadataKey, $metadata, $this->config['ttl'] * 2);
    }

    /**
     * Generate cache key.
     */
    protected function getCacheKey(string $type, string $connection, string $table = ''): string
    {
        return implode('_', array_filter([
            $this->config['prefix'],
            $type,
            $connection,
            $table
        ]));
    }

    /**
     * Fetch table schema from database.
     */
    protected function fetchTableSchema(string $connection, string $table): ?array
    {
        try {
            $schema = DB::connection($connection)->getDoctrineSchemaManager();
            $tableSchema = $schema->introspectTable($table);
            
            return [
                'name' => $table,
                'columns' => collect($tableSchema->getColumns())->map(function ($column) {
                    return [
                        'name' => $column->getName(),
                        'type' => $column->getType()->getName(),
                        'length' => $column->getLength(),
                        'nullable' => !$column->getNotnull(),
                        'default' => $column->getDefault(),
                        'auto_increment' => $column->getAutoincrement(),
                    ];
                })->toArray(),
                'indexes' => collect($tableSchema->getIndexes())->map(function ($index) {
                    return [
                        'name' => $index->getName(),
                        'columns' => $index->getColumns(),
                        'unique' => $index->isUnique(),
                        'primary' => $index->isPrimary(),
                    ];
                })->toArray(),
                'foreign_keys' => collect($tableSchema->getForeignKeys())->map(function ($foreignKey) {
                    return [
                        'name' => $foreignKey->getName(),
                        'local_columns' => $foreignKey->getLocalColumns(),
                        'foreign_table' => $foreignKey->getForeignTableName(),
                        'foreign_columns' => $foreignKey->getForeignColumns(),
                    ];
                })->toArray(),
                'cached_at' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            Log::warning("Failed to fetch schema for table {$table}", [
                'connection' => $connection,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Fetch all tables schema from database.
     */
    protected function fetchAllTablesSchema(string $connection): array
    {
        try {
            $tableNames = $this->getTableNames($connection);
            $schemas = [];
            
            foreach ($tableNames as $table) {
                $schema = $this->fetchTableSchema($connection, $table);
                if ($schema) {
                    $schemas[$table] = $schema;
                }
            }
            
            return $schemas;
        } catch (\Exception $e) {
            Log::warning("Failed to fetch all tables schema", [
                'connection' => $connection,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get table names for connection.
     */
    protected function getTableNames(string $connection): array
    {
        try {
            return DB::connection($connection)->getDoctrineSchemaManager()->listTableNames();
        } catch (\Exception $e) {
            Log::warning("Failed to get table names", [
                'connection' => $connection,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Fetch relationship information.
     */
    protected function fetchRelationships(string $connection): array
    {
        try {
            $relationships = [];
            $tableNames = $this->getTableNames($connection);
            
            foreach ($tableNames as $table) {
                $schema = $this->fetchTableSchema($connection, $table);
                if ($schema && !empty($schema['foreign_keys'])) {
                    $relationships[$table] = $schema['foreign_keys'];
                }
            }
            
            return $relationships;
        } catch (\Exception $e) {
            Log::warning("Failed to fetch relationships", [
                'connection' => $connection,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}