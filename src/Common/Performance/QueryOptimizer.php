<?php

declare(strict_types=1);

namespace AutoGen\Common\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Database query optimization for AutoGen operations
 */
class QueryOptimizer
{
    protected array $config;
    protected array $connectionPools = [];
    protected array $queryStats = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'batch_size' => 50,
            'max_connections' => 10,
            'query_timeout' => 30,
            'use_connection_pooling' => true,
            'enable_query_logging' => true,
            'slow_query_threshold' => 1.0,
        ], $config);
    }

    /**
     * Execute queries in batches for better performance.
     */
    public function batchExecute(string $connection, array $queries, int $batchSize = null): array
    {
        if (!$this->config['enabled']) {
            return $this->executeQueries($connection, $queries);
        }

        $batchSize = $batchSize ?? $this->config['batch_size'];
        $results = [];
        $batches = array_chunk($queries, $batchSize);

        foreach ($batches as $batch) {
            $batchResults = $this->executeBatch($connection, $batch);
            $results = array_merge($results, $batchResults);
        }

        return $results;
    }

    /**
     * Get table information with query optimization.
     */
    public function getOptimizedTableInfo(string $connection, array $tables): array
    {
        if (!$this->config['enabled']) {
            return $this->getTableInfo($connection, $tables);
        }

        $startTime = microtime(true);
        $results = [];

        // Use prepared statements and batch queries
        $conn = $this->getOptimizedConnection($connection);
        
        try {
            // Start transaction for consistency
            DB::connection($connection)->beginTransaction();

            // Batch table queries
            $batches = array_chunk($tables, $this->config['batch_size']);
            
            foreach ($batches as $batch) {
                $batchResults = $this->getBatchTableInfo($connection, $batch);
                $results = array_merge($results, $batchResults);
            }

            DB::connection($connection)->commit();
        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();
            throw $e;
        }

        $executionTime = microtime(true) - $startTime;
        
        $this->logQueryPerformance('getOptimizedTableInfo', $executionTime, [
            'connection' => $connection,
            'tables_count' => count($tables),
            'batches' => count($batches ?? []),
        ]);

        return $results;
    }

    /**
     * Get relationships with optimized queries.
     */
    public function getOptimizedRelationships(string $connection): array
    {
        $startTime = microtime(true);
        
        $query = $this->buildOptimizedRelationshipQuery($connection);
        $results = DB::connection($connection)->select($query);
        
        $relationships = $this->processRelationshipResults($results);
        
        $executionTime = microtime(true) - $startTime;
        $this->logQueryPerformance('getOptimizedRelationships', $executionTime, [
            'connection' => $connection,
            'relationships_found' => count($relationships),
        ]);

        return $relationships;
    }

    /**
     * Execute batch of queries with connection pooling.
     */
    protected function executeBatch(string $connection, array $queries): array
    {
        $conn = $this->getOptimizedConnection($connection);
        $results = [];
        
        // Set query timeout
        if ($this->config['query_timeout'] > 0) {
            DB::connection($connection)->statement("SET SESSION wait_timeout = {$this->config['query_timeout']}");
        }

        foreach ($queries as $query) {
            $startTime = microtime(true);
            
            try {
                $result = DB::connection($connection)->select($query);
                $results[] = $result;
                
                $executionTime = microtime(true) - $startTime;
                $this->trackQueryStats($query, $executionTime, count($result));
                
            } catch (\Exception $e) {
                Log::error("Query execution failed", [
                    'query' => $query,
                    'connection' => $connection,
                    'error' => $e->getMessage(),
                ]);
                
                $results[] = [];
            }
        }

        return $results;
    }

    /**
     * Get batch table information.
     */
    protected function getBatchTableInfo(string $connection, array $tables): array
    {
        $driver = DB::connection($connection)->getDriverName();
        $results = [];

        switch ($driver) {
            case 'mysql':
                $results = $this->getMySQLBatchTableInfo($connection, $tables);
                break;
            case 'pgsql':
                $results = $this->getPostgreSQLBatchTableInfo($connection, $tables);
                break;
            case 'sqlite':
                $results = $this->getSQLiteBatchTableInfo($connection, $tables);
                break;
            default:
                $results = $this->getGenericBatchTableInfo($connection, $tables);
        }

        return $results;
    }

    /**
     * Get MySQL batch table information.
     */
    protected function getMySQLBatchTableInfo(string $connection, array $tables): array
    {
        $tableList = "'" . implode("','", $tables) . "'";
        
        $query = "
            SELECT 
                t.TABLE_NAME,
                t.ENGINE,
                t.TABLE_ROWS,
                t.DATA_LENGTH,
                t.INDEX_LENGTH,
                c.COLUMN_NAME,
                c.DATA_TYPE,
                c.IS_NULLABLE,
                c.COLUMN_DEFAULT,
                c.EXTRA,
                k.CONSTRAINT_NAME,
                k.CONSTRAINT_TYPE
            FROM INFORMATION_SCHEMA.TABLES t
            LEFT JOIN INFORMATION_SCHEMA.COLUMNS c ON t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_SCHEMA = c.TABLE_SCHEMA
            LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE ku ON c.TABLE_NAME = ku.TABLE_NAME AND c.COLUMN_NAME = ku.COLUMN_NAME AND c.TABLE_SCHEMA = ku.TABLE_SCHEMA
            LEFT JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS k ON ku.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND ku.TABLE_SCHEMA = k.TABLE_SCHEMA
            WHERE t.TABLE_NAME IN ({$tableList}) 
            AND t.TABLE_SCHEMA = DATABASE()
            ORDER BY t.TABLE_NAME, c.ORDINAL_POSITION
        ";

        $results = DB::connection($connection)->select($query);
        return $this->processTableInfoResults($results);
    }

    /**
     * Get PostgreSQL batch table information.
     */
    protected function getPostgreSQLBatchTableInfo(string $connection, array $tables): array
    {
        $tableList = "'" . implode("','", $tables) . "'";
        
        $query = "
            SELECT 
                t.table_name,
                c.column_name,
                c.data_type,
                c.is_nullable,
                c.column_default,
                tc.constraint_type
            FROM information_schema.tables t
            LEFT JOIN information_schema.columns c ON t.table_name = c.table_name AND t.table_schema = c.table_schema
            LEFT JOIN information_schema.key_column_usage kcu ON c.table_name = kcu.table_name AND c.column_name = kcu.column_name
            LEFT JOIN information_schema.table_constraints tc ON kcu.constraint_name = tc.constraint_name
            WHERE t.table_name IN ({$tableList})
            AND t.table_schema = 'public'
            ORDER BY t.table_name, c.ordinal_position
        ";

        $results = DB::connection($connection)->select($query);
        return $this->processTableInfoResults($results);
    }

    /**
     * Get SQLite batch table information.
     */
    protected function getSQLiteBatchTableInfo(string $connection, array $tables): array
    {
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $tableInfo = DB::connection($connection)->select("PRAGMA table_info({$table})");
                $indexes = DB::connection($connection)->select("PRAGMA index_list({$table})");
                $foreignKeys = DB::connection($connection)->select("PRAGMA foreign_key_list({$table})");
                
                $results[$table] = [
                    'columns' => $tableInfo,
                    'indexes' => $indexes,
                    'foreign_keys' => $foreignKeys,
                ];
            } catch (\Exception $e) {
                Log::warning("Failed to get SQLite table info for {$table}", [
                    'error' => $e->getMessage()
                ]);
                $results[$table] = [];
            }
        }

        return $results;
    }

    /**
     * Get generic batch table information.
     */
    protected function getGenericBatchTableInfo(string $connection, array $tables): array
    {
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $schema = DB::connection($connection)->getDoctrineSchemaManager();
                $tableSchema = $schema->introspectTable($table);
                
                $results[$table] = [
                    'columns' => collect($tableSchema->getColumns())->map(function ($column) {
                        return [
                            'name' => $column->getName(),
                            'type' => $column->getType()->getName(),
                            'nullable' => !$column->getNotnull(),
                            'default' => $column->getDefault(),
                        ];
                    })->toArray(),
                    'indexes' => collect($tableSchema->getIndexes())->map(function ($index) {
                        return [
                            'name' => $index->getName(),
                            'columns' => $index->getColumns(),
                            'unique' => $index->isUnique(),
                        ];
                    })->toArray(),
                ];
            } catch (\Exception $e) {
                Log::warning("Failed to get generic table info for {$table}", [
                    'error' => $e->getMessage()
                ]);
                $results[$table] = [];
            }
        }

        return $results;
    }

    /**
     * Build optimized relationship query.
     */
    protected function buildOptimizedRelationshipQuery(string $connection): string
    {
        $driver = DB::connection($connection)->getDriverName();

        switch ($driver) {
            case 'mysql':
                return "
                    SELECT 
                        kcu.TABLE_NAME,
                        kcu.COLUMN_NAME,
                        kcu.REFERENCED_TABLE_NAME,
                        kcu.REFERENCED_COLUMN_NAME,
                        rc.CONSTRAINT_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                    JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                    WHERE kcu.TABLE_SCHEMA = DATABASE()
                    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                    ORDER BY kcu.TABLE_NAME, kcu.ORDINAL_POSITION
                ";
                
            case 'pgsql':
                return "
                    SELECT 
                        kcu.table_name,
                        kcu.column_name,
                        ccu.table_name AS referenced_table_name,
                        ccu.column_name AS referenced_column_name,
                        tc.constraint_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                    JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_schema = 'public'
                ";
                
            default:
                return "SELECT 1"; // Fallback for unsupported drivers
        }
    }

    /**
     * Process relationship query results.
     */
    protected function processRelationshipResults(array $results): array
    {
        $relationships = [];
        
        foreach ($results as $row) {
            $row = (array) $row;
            $tableName = $row['table_name'] ?? $row['TABLE_NAME'] ?? '';
            
            if (!$tableName) continue;
            
            if (!isset($relationships[$tableName])) {
                $relationships[$tableName] = [];
            }
            
            $relationships[$tableName][] = [
                'column' => $row['column_name'] ?? $row['COLUMN_NAME'] ?? '',
                'referenced_table' => $row['referenced_table_name'] ?? $row['REFERENCED_TABLE_NAME'] ?? '',
                'referenced_column' => $row['referenced_column_name'] ?? $row['REFERENCED_COLUMN_NAME'] ?? '',
                'constraint' => $row['constraint_name'] ?? $row['CONSTRAINT_NAME'] ?? '',
            ];
        }

        return $relationships;
    }

    /**
     * Process table info results.
     */
    protected function processTableInfoResults(array $results): array
    {
        $tables = [];
        
        foreach ($results as $row) {
            $row = (array) $row;
            $tableName = $row['table_name'] ?? $row['TABLE_NAME'] ?? '';
            
            if (!$tableName) continue;
            
            if (!isset($tables[$tableName])) {
                $tables[$tableName] = [
                    'columns' => [],
                    'constraints' => [],
                ];
            }
            
            $columnName = $row['column_name'] ?? $row['COLUMN_NAME'] ?? '';
            if ($columnName && !isset($tables[$tableName]['columns'][$columnName])) {
                $tables[$tableName]['columns'][$columnName] = [
                    'name' => $columnName,
                    'type' => $row['data_type'] ?? $row['DATA_TYPE'] ?? '',
                    'nullable' => ($row['is_nullable'] ?? $row['IS_NULLABLE'] ?? '') === 'YES',
                    'default' => $row['column_default'] ?? $row['COLUMN_DEFAULT'] ?? null,
                ];
            }
        }

        return $tables;
    }

    /**
     * Get optimized connection with pooling.
     */
    protected function getOptimizedConnection(string $connection): \Illuminate\Database\Connection
    {
        if (!$this->config['use_connection_pooling']) {
            return DB::connection($connection);
        }

        // Simple connection pooling implementation
        if (!isset($this->connectionPools[$connection])) {
            $this->connectionPools[$connection] = [];
        }

        if (count($this->connectionPools[$connection]) < $this->config['max_connections']) {
            $conn = DB::connection($connection);
            $this->connectionPools[$connection][] = $conn;
            return $conn;
        }

        // Return existing connection from pool
        return $this->connectionPools[$connection][0];
    }

    /**
     * Track query statistics.
     */
    protected function trackQueryStats(string $query, float $executionTime, int $resultCount): void
    {
        if (!$this->config['enable_query_logging']) {
            return;
        }

        $queryHash = md5($query);
        
        if (!isset($this->queryStats[$queryHash])) {
            $this->queryStats[$queryHash] = [
                'query' => substr($query, 0, 100) . '...',
                'executions' => 0,
                'total_time' => 0,
                'max_time' => 0,
                'min_time' => PHP_FLOAT_MAX,
                'total_results' => 0,
            ];
        }

        $stats = &$this->queryStats[$queryHash];
        $stats['executions']++;
        $stats['total_time'] += $executionTime;
        $stats['max_time'] = max($stats['max_time'], $executionTime);
        $stats['min_time'] = min($stats['min_time'], $executionTime);
        $stats['total_results'] += $resultCount;

        // Log slow queries
        if ($executionTime > $this->config['slow_query_threshold']) {
            Log::warning('Slow query detected', [
                'query' => substr($query, 0, 200),
                'execution_time' => $executionTime,
                'result_count' => $resultCount,
            ]);
        }
    }

    /**
     * Log query performance.
     */
    protected function logQueryPerformance(string $operation, float $executionTime, array $context = []): void
    {
        Log::info("Query operation completed: {$operation}", array_merge($context, [
            'execution_time' => $executionTime,
            'slow' => $executionTime > $this->config['slow_query_threshold'],
        ]));
    }

    /**
     * Get query statistics.
     */
    public function getQueryStats(): array
    {
        return $this->queryStats;
    }

    /**
     * Clear query statistics.
     */
    public function clearStats(): void
    {
        $this->queryStats = [];
    }

    /**
     * Execute queries without optimization (fallback).
     */
    protected function executeQueries(string $connection, array $queries): array
    {
        $results = [];
        
        foreach ($queries as $query) {
            try {
                $results[] = DB::connection($connection)->select($query);
            } catch (\Exception $e) {
                Log::error("Query execution failed", [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
                $results[] = [];
            }
        }

        return $results;
    }

    /**
     * Get table information without optimization (fallback).
     */
    protected function getTableInfo(string $connection, array $tables): array
    {
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $schema = DB::connection($connection)->getDoctrineSchemaManager();
                $results[$table] = $schema->introspectTable($table);
            } catch (\Exception $e) {
                Log::warning("Failed to get table info for {$table}", [
                    'error' => $e->getMessage()
                ]);
                $results[$table] = null;
            }
        }

        return $results;
    }
}