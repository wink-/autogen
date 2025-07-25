<?php

declare(strict_types=1);

namespace AutoGen\Packages\Migration;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use AutoGen\Common\Exceptions\GenerationException;

class MigrationGenerator
{
    /**
     * The migration template engine instance.
     *
     * @var MigrationTemplateEngine
     */
    protected MigrationTemplateEngine $templateEngine;

    /**
     * Create a new migration generator instance.
     */
    public function __construct(MigrationTemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Generate migration file for a table.
     */
    public function generateMigration(
        string $connection,
        string $table,
        array $tableStructure,
        array $schema,
        array $config
    ): array {
        $migrationName = $this->generateMigrationName($table, $config);
        $fileName = $this->generateFileName($migrationName, $config);
        $filePath = $this->getFilePath($fileName, $config);

        // Check if migration already exists
        if (file_exists($filePath) && !$config['force']) {
            return [
                'generated' => false,
                'file' => $fileName,
                'reason' => 'Migration already exists (use --force to overwrite)'
            ];
        }

        // Generate migration content
        $content = $this->templateEngine->generateMigrationContent(
            $migrationName,
            $table,
            $tableStructure,
            $schema,
            $config
        );

        // Ensure output directory exists
        $directory = dirname($filePath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Write migration file
        if (file_put_contents($filePath, $content) === false) {
            throw new GenerationException("Failed to write migration file: {$filePath}");
        }

        return [
            'generated' => true,
            'file' => $fileName,
            'path' => $filePath,
            'size' => filesize($filePath)
        ];
    }

    /**
     * Generate all migrations for schema.
     */
    public function generateAllMigrations(array $schema, array $config): array
    {
        $results = [];
        $orderedTables = array_keys($schema['tables']);

        // Generate table creation migrations first
        foreach ($orderedTables as $table) {
            $result = $this->generateMigration(
                $schema['connection'],
                $table,
                $schema['tables'][$table],
                $schema,
                $config
            );
            $results[] = $result;
        }

        // Generate foreign key migrations if needed
        if ($config['with_foreign_keys'] && $this->shouldSeparateForeignKeys($schema)) {
            $foreignKeyResults = $this->generateForeignKeyMigrations($schema, $config);
            $results = array_merge($results, $foreignKeyResults);
        }

        return $results;
    }

    /**
     * Generate migration name.
     */
    protected function generateMigrationName(string $table, array $config): string
    {
        $name = 'create_' . $table . '_table';
        
        // Convert to camel case class name
        return 'Create' . Str::studly($table) . 'Table';
    }

    /**
     * Generate migration file name.
     */
    protected function generateFileName(string $migrationName, array $config): string
    {
        $timestamp = '';
        
        if ($config['timestamp_prefix']) {
            $timestamp = Carbon::now()->format('Y_m_d_His') . '_';
        }
        
        $snakeName = Str::snake($migrationName);
        
        return $timestamp . $snakeName . '.php';
    }

    /**
     * Get full file path for migration.
     */
    protected function getFilePath(string $fileName, array $config): string
    {
        return rtrim($config['output_path'], '/') . '/' . $fileName;
    }

    /**
     * Check if foreign keys should be in separate migrations.
     */
    protected function shouldSeparateForeignKeys(array $schema): bool
    {
        // Separate if there are potential circular dependencies
        $dependencies = $schema['dependencies'];
        
        foreach ($dependencies as $table => $deps) {
            foreach ($deps as $dep) {
                if (isset($dependencies[$dep]) && in_array($table, $dependencies[$dep])) {
                    return true; // Circular dependency found
                }
            }
        }
        
        return false;
    }

    /**
     * Generate separate foreign key migrations.
     */
    protected function generateForeignKeyMigrations(array $schema, array $config): array
    {
        $results = [];
        
        foreach ($schema['tables'] as $table => $tableStructure) {
            if (empty($tableStructure['foreign_keys'])) {
                continue;
            }

            $migrationName = 'AddForeignKeysTo' . Str::studly($table) . 'Table';
            $fileName = $this->generateFileName($migrationName, $config);
            $filePath = $this->getFilePath($fileName, $config);

            // Check if migration already exists
            if (file_exists($filePath) && !$config['force']) {
                $results[] = [
                    'generated' => false,
                    'file' => $fileName,
                    'reason' => 'Foreign key migration already exists'
                ];
                continue;
            }

            // Generate foreign key migration content
            $content = $this->templateEngine->generateForeignKeyMigrationContent(
                $migrationName,
                $table,
                $tableStructure['foreign_keys'],
                $config
            );

            // Write migration file
            if (file_put_contents($filePath, $content) === false) {
                throw new GenerationException("Failed to write foreign key migration file: {$filePath}");
            }

            $results[] = [
                'generated' => true,
                'file' => $fileName,
                'path' => $filePath,
                'type' => 'foreign_keys'
            ];
        }

        return $results;
    }

    /**
     * Generate rollback migration.
     */
    public function generateRollbackMigration(string $table, array $config): array
    {
        $migrationName = 'Drop' . Str::studly($table) . 'Table';
        $fileName = $this->generateFileName($migrationName, $config);
        $filePath = $this->getFilePath($fileName, $config);

        // Check if migration already exists
        if (file_exists($filePath) && !$config['force']) {
            return [
                'generated' => false,
                'file' => $fileName,
                'reason' => 'Rollback migration already exists'
            ];
        }

        // Generate rollback migration content
        $content = $this->templateEngine->generateRollbackMigrationContent(
            $migrationName,
            $table,
            $config
        );

        // Write migration file
        if (file_put_contents($filePath, $content) === false) {
            throw new GenerationException("Failed to write rollback migration file: {$filePath}");
        }

        return [
            'generated' => true,
            'file' => $fileName,
            'path' => $filePath,
            'type' => 'rollback'
        ];
    }

    /**
     * Get existing migration files.
     */
    public function getExistingMigrations(array $config): array
    {
        $migrationPath = $config['output_path'];
        
        if (!File::isDirectory($migrationPath)) {
            return [];
        }

        $files = File::files($migrationPath);
        $migrations = [];

        foreach ($files as $file) {
            if (Str::endsWith($file->getFilename(), '.php')) {
                $migrations[] = [
                    'file' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        return $migrations;
    }

    /**
     * Backup existing migrations.
     */
    public function backupExistingMigrations(array $config): string
    {
        $backupDir = $config['output_path'] . '/backup_' . Carbon::now()->format('Y_m_d_His');
        
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $existing = $this->getExistingMigrations($config);
        $backed = 0;

        foreach ($existing as $migration) {
            $backupPath = $backupDir . '/' . $migration['file'];
            if (File::copy($migration['path'], $backupPath)) {
                $backed++;
            }
        }

        return $backupDir;
    }

    /**
     * Clean up generated migrations.
     */
    public function cleanupMigrations(array $config): int
    {
        $cleaned = 0;
        $existing = $this->getExistingMigrations($config);

        foreach ($existing as $migration) {
            // Only delete migrations with autogen pattern
            if (str_contains($migration['file'], 'create_') && 
                str_contains($migration['file'], '_table.php')) {
                if (File::delete($migration['path'])) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Validate migration syntax.
     */
    public function validateMigration(string $filePath): array
    {
        $errors = [];
        
        if (!file_exists($filePath)) {
            $errors[] = 'Migration file does not exist';
            return $errors;
        }

        $content = file_get_contents($filePath);
        
        // Basic syntax check
        if (php_check_syntax($filePath, $output) === false) {
            $errors[] = "PHP syntax error: {$output}";
        }

        // Check for required methods
        if (!str_contains($content, 'public function up()')) {
            $errors[] = 'Missing up() method';
        }

        if (!str_contains($content, 'public function down()')) {
            $errors[] = 'Missing down() method';
        }

        // Check for Laravel migration class structure
        if (!str_contains($content, 'use Illuminate\Database\Migrations\Migration')) {
            $errors[] = 'Missing Migration import';
        }

        if (!str_contains($content, 'use Illuminate\Database\Schema\Blueprint')) {
            $errors[] = 'Missing Blueprint import';
        }

        return $errors;
    }

    /**
     * Get migration statistics.
     */
    public function getMigrationStats(array $results): array
    {
        $stats = [
            'total' => count($results),
            'generated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'total_size' => 0,
            'types' => [],
        ];

        foreach ($results as $result) {
            if ($result['generated']) {
                $stats['generated']++;
                if (isset($result['size'])) {
                    $stats['total_size'] += $result['size'];
                }
            } else {
                $stats['skipped']++;
            }

            $type = $result['type'] ?? 'table';
            $stats['types'][$type] = ($stats['types'][$type] ?? 0) + 1;
        }

        return $stats;
    }
}