<?php

declare(strict_types=1);

namespace AutoGen\Common\Rollback;

use AutoGen\Common\Contracts\RollbackStrategyInterface;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Database rollback strategy for rolling back database operations.
 */
class DatabaseRollbackStrategy implements RollbackStrategyInterface
{
    protected string $backupDisk;
    protected string $backupPath;
    protected array $supportedConnections;

    public function __construct(
        string $backupDisk = 'local', 
        string $backupPath = 'autogen/db_backups',
        array $supportedConnections = ['mysql', 'pgsql', 'sqlite']
    ) {
        $this->backupDisk = $backupDisk;
        $this->backupPath = $backupPath;
        $this->supportedConnections = $supportedConnections;
    }

    public function createBackup(string $operationId, array $context): string
    {
        $backupId = $this->generateBackupId($operationId);
        $backupDir = $this->getBackupDirectory($backupId);

        try {
            Storage::disk($this->backupDisk)->makeDirectory($backupDir);

            // Create metadata
            $metadata = $this->createMetadata($operationId, $context);
            
            // Backup affected tables/schemas
            $this->backupDatabaseObjects($context, $backupDir, $metadata);

            // Save metadata
            Storage::disk($this->backupDisk)->put(
                "{$backupDir}/metadata.json",
                json_encode($metadata, JSON_PRETTY_PRINT)
            );

            return $backupId;
        } catch (\Exception $e) {
            throw RollbackException::backupCreationFailed($operationId, $e->getMessage());
        }
    }

    public function restore(string $backupId): bool
    {
        if (!$this->hasBackup($backupId)) {
            throw RollbackException::backupNotFound($backupId);
        }

        try {
            $metadata = $this->getBackupMetadata($backupId);
            if (!$metadata) {
                throw RollbackException::invalidBackupState($backupId, 'Metadata not found');
            }

            return $this->restoreDatabaseObjects($backupId, $metadata);
        } catch (\Exception $e) {
            throw RollbackException::restoreFailed($backupId, $e->getMessage());
        }
    }

    public function hasBackup(string $backupId): bool
    {
        $backupDir = $this->getBackupDirectory($backupId);
        return Storage::disk($this->backupDisk)->exists("{$backupDir}/metadata.json");
    }

    public function listBackups(): array
    {
        $backups = [];
        $directories = Storage::disk($this->backupDisk)->directories($this->backupPath);

        foreach ($directories as $dir) {
            $backupId = basename($dir);
            if ($metadata = $this->getBackupMetadata($backupId)) {
                $backups[] = [
                    'id' => $backupId,
                    'operation_id' => $metadata['operation_id'],
                    'created_at' => $metadata['created_at'],
                    'database_connection' => $metadata['database_connection'] ?? 'default',
                    'tables_count' => count($metadata['tables'] ?? []),
                    'size' => $this->getBackupSize($backupId),
                ];
            }
        }

        return collect($backups)->sortByDesc('created_at')->values()->all();
    }

    public function removeBackup(string $backupId): bool
    {
        if (!$this->hasBackup($backupId)) {
            return false;
        }

        $backupDir = $this->getBackupDirectory($backupId);
        return Storage::disk($this->backupDisk)->deleteDirectory($backupDir);
    }

    public function cleanupBackups(int $retentionDays = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        $cleaned = 0;

        foreach ($this->listBackups() as $backup) {
            $createdAt = Carbon::parse($backup['created_at']);
            if ($createdAt->lt($cutoffDate)) {
                if ($this->removeBackup($backup['id'])) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    public function getBackupMetadata(string $backupId): ?array
    {
        $metadataPath = $this->getBackupDirectory($backupId) . '/metadata.json';
        
        if (!Storage::disk($this->backupDisk)->exists($metadataPath)) {
            return null;
        }

        $content = Storage::disk($this->backupDisk)->get($metadataPath);
        return json_decode($content, true);
    }

    public function canRestore(string $backupId): bool
    {
        if (!$this->hasBackup($backupId)) {
            return false;
        }

        $metadata = $this->getBackupMetadata($backupId);
        if (!$metadata) {
            return false;
        }

        // Check if database connection is still available
        $connection = $metadata['database_connection'] ?? config('database.default');
        try {
            DB::connection($connection)->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'database_rollback';
    }

    /**
     * Generate a unique backup ID.
     */
    protected function generateBackupId(string $operationId): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $hash = substr(md5($operationId . microtime()), 0, 8);
        return "db_backup_{$timestamp}_{$hash}";
    }

    /**
     * Get backup directory path.
     */
    protected function getBackupDirectory(string $backupId): string
    {
        return "{$this->backupPath}/{$backupId}";
    }

    /**
     * Create metadata for the database backup.
     */
    protected function createMetadata(string $operationId, array $context): array
    {
        $connection = $context['database_connection'] ?? config('database.default');
        
        return [
            'operation_id' => $operationId,
            'created_at' => now()->toISOString(),
            'context' => $context,
            'database_connection' => $connection,
            'database_driver' => config("database.connections.{$connection}.driver"),
            'tables' => [],
            'migrations' => [],
            'strategy' => $this->getName(),
            'version' => '1.0',
        ];
    }

    /**
     * Backup database objects that might be affected.
     */
    protected function backupDatabaseObjects(array $context, string $backupDir, array &$metadata): void
    {
        $connection = $context['database_connection'] ?? config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if (!in_array($driver, $this->supportedConnections)) {
            throw RollbackException::backupCreationFailed(
                $metadata['operation_id'],
                "Unsupported database driver: {$driver}"
            );
        }

        // Backup tables
        $tablesToBackup = $this->determineTablesToBackup($context);
        foreach ($tablesToBackup as $tableName) {
            $this->backupTable($connection, $tableName, $backupDir, $metadata);
        }

        // Backup migrations state
        $this->backupMigrationsState($connection, $backupDir, $metadata);
    }

    /**
     * Determine which tables need to be backed up.
     */
    protected function determineTablesToBackup(array $context): array
    {
        $tables = [];

        if (isset($context['table_name'])) {
            $tables[] = $context['table_name'];
        }

        if (isset($context['tables'])) {
            $tables = array_merge($tables, (array) $context['tables']);
        }

        // Add related tables based on context
        if (isset($context['model_name'])) {
            $modelClass = "App\\Models\\{$context['model_name']}";
            if (class_exists($modelClass)) {
                $model = new $modelClass;
                $tables[] = $model->getTable();
            }
        }

        return array_unique($tables);
    }

    /**
     * Backup a specific table.
     */
    protected function backupTable(string $connection, string $tableName, string $backupDir, array &$metadata): void
    {
        if (!Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        try {
            // Get table schema
            $schema = $this->getTableSchema($connection, $tableName);
            Storage::disk($this->backupDisk)->put(
                "{$backupDir}/schema_{$tableName}.json",
                json_encode($schema, JSON_PRETTY_PRINT)
            );

            // Get table data
            $data = DB::connection($connection)->table($tableName)->get()->toArray();
            Storage::disk($this->backupDisk)->put(
                "{$backupDir}/data_{$tableName}.json",
                json_encode($data, JSON_PRETTY_PRINT)
            );

            $metadata['tables'][] = [
                'name' => $tableName,
                'rows_count' => count($data),
                'schema_file' => "schema_{$tableName}.json",
                'data_file' => "data_{$tableName}.json",
                'backed_up_at' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            // Log error but continue with other tables
            $metadata['errors'][] = [
                'table' => $tableName,
                'error' => $e->getMessage(),
                'occurred_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get table schema information.
     */
    protected function getTableSchema(string $connection, string $tableName): array
    {
        $columns = Schema::connection($connection)->getColumnListing($tableName);
        $schema = [];

        foreach ($columns as $column) {
            $columnType = Schema::connection($connection)->getColumnType($tableName, $column);
            $schema[] = [
                'name' => $column,
                'type' => $columnType,
            ];
        }

        return [
            'table' => $tableName,
            'columns' => $schema,
            'indexes' => $this->getTableIndexes($connection, $tableName),
        ];
    }

    /**
     * Get table indexes.
     */
    protected function getTableIndexes(string $connection, string $tableName): array
    {
        try {
            $driver = config("database.connections.{$connection}.driver");
            
            switch ($driver) {
                case 'mysql':
                    return DB::connection($connection)
                        ->select("SHOW INDEX FROM {$tableName}");
                        
                case 'pgsql':
                    return DB::connection($connection)
                        ->select("
                            SELECT * FROM pg_indexes 
                            WHERE tablename = ?
                        ", [$tableName]);
                        
                default:
                    return [];
            }
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Backup migrations state.
     */
    protected function backupMigrationsState(string $connection, string $backupDir, array &$metadata): void
    {
        try {
            if (Schema::connection($connection)->hasTable('migrations')) {
                $migrations = DB::connection($connection)
                    ->table('migrations')
                    ->orderBy('id')
                    ->get()
                    ->toArray();

                Storage::disk($this->backupDisk)->put(
                    "{$backupDir}/migrations.json",
                    json_encode($migrations, JSON_PRETTY_PRINT)
                );

                $metadata['migrations'] = [
                    'count' => count($migrations),
                    'file' => 'migrations.json',
                    'backed_up_at' => now()->toISOString(),
                ];
            }
        } catch (\Exception $e) {
            $metadata['errors'][] = [
                'migrations' => $e->getMessage(),
                'occurred_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Restore database objects from backup.
     */
    protected function restoreDatabaseObjects(string $backupId, array $metadata): bool
    {
        $backupDir = $this->getBackupDirectory($backupId);
        $connection = $metadata['database_connection'] ?? config('database.default');

        DB::connection($connection)->beginTransaction();

        try {
            // Restore tables
            foreach ($metadata['tables'] ?? [] as $tableInfo) {
                $this->restoreTable($connection, $tableInfo, $backupDir);
            }

            // Restore migrations state if needed
            if (isset($metadata['migrations'])) {
                $this->restoreMigrationsState($connection, $backupDir);
            }

            DB::connection($connection)->commit();
            return true;
        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();
            throw $e;
        }
    }

    /**
     * Restore a specific table.
     */
    protected function restoreTable(string $connection, array $tableInfo, string $backupDir): void
    {
        $tableName = $tableInfo['name'];
        
        // Drop existing table if it exists
        Schema::connection($connection)->dropIfExists($tableName);

        // Recreate table from schema (simplified - in real implementation, 
        // you'd want to properly recreate the table structure)
        $schemaContent = Storage::disk($this->backupDisk)->get("{$backupDir}/{$tableInfo['schema_file']}");
        $schema = json_decode($schemaContent, true);
        
        // Create table with basic structure
        Schema::connection($connection)->create($tableName, function ($table) use ($schema) {
            foreach ($schema['columns'] as $column) {
                $table->addColumn($column['type'], $column['name'])->nullable();
            }
        });

        // Insert data
        $dataContent = Storage::disk($this->backupDisk)->get("{$backupDir}/{$tableInfo['data_file']}");
        $data = json_decode($dataContent, true);
        
        if (!empty($data)) {
            // Convert objects to arrays if needed
            $data = array_map(function($row) {
                return (array) $row;
            }, $data);
            
            DB::connection($connection)->table($tableName)->insert($data);
        }
    }

    /**
     * Restore migrations state.
     */
    protected function restoreMigrationsState(string $connection, string $backupDir): void
    {
        $migrationsContent = Storage::disk($this->backupDisk)->get("{$backupDir}/migrations.json");
        $migrations = json_decode($migrationsContent, true);

        if (!empty($migrations)) {
            // Clear current migrations
            DB::connection($connection)->table('migrations')->truncate();
            
            // Insert backed up migrations
            $migrations = array_map(function($migration) {
                return (array) $migration;
            }, $migrations);
            
            DB::connection($connection)->table('migrations')->insert($migrations);
        }
    }

    /**
     * Get backup size in bytes.
     */
    protected function getBackupSize(string $backupId): int
    {
        $backupDir = $this->getBackupDirectory($backupId);
        $files = Storage::disk($this->backupDisk)->allFiles($backupDir);
        $size = 0;

        foreach ($files as $file) {
            $size += Storage::disk($this->backupDisk)->size($file);
        }

        return $size;
    }
}