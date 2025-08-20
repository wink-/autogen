<?php

declare(strict_types=1);

namespace AutoGen\Common\Rollback;

use AutoGen\Common\Contracts\RollbackStrategyInterface;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * File-based backup strategy for rolling back file operations.
 */
class BackupStrategy implements RollbackStrategyInterface
{
    protected string $backupDisk;
    protected string $backupPath;

    public function __construct(string $backupDisk = 'local', string $backupPath = 'autogen/backups')
    {
        $this->backupDisk = $backupDisk;
        $this->backupPath = $backupPath;
    }

    public function createBackup(string $operationId, array $context): string
    {
        $backupId = $this->generateBackupId($operationId);
        $backupDir = $this->getBackupDirectory($backupId);

        try {
            Storage::disk($this->backupDisk)->makeDirectory($backupDir);

            // Create metadata file
            $metadata = $this->createMetadata($operationId, $context);
            Storage::disk($this->backupDisk)->put(
                "{$backupDir}/metadata.json",
                json_encode($metadata, JSON_PRETTY_PRINT)
            );

            // Backup files that might be affected
            $this->backupFiles($context, $backupDir);

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

        $backupDir = $this->getBackupDirectory($backupId);
        
        try {
            $metadata = $this->getBackupMetadata($backupId);
            if (!$metadata) {
                throw RollbackException::invalidBackupState($backupId, 'Metadata not found');
            }

            // Restore files
            return $this->restoreFiles($backupDir, $metadata);
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
                    'size' => $this->getBackupSize($backupId),
                    'files_count' => count($metadata['files'] ?? []),
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

        // Check if backup is not corrupted
        return isset($metadata['operation_id']) && isset($metadata['created_at']);
    }

    public function getName(): string
    {
        return 'file_backup';
    }

    /**
     * Generate a unique backup ID.
     */
    protected function generateBackupId(string $operationId): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $hash = substr(md5($operationId . microtime()), 0, 8);
        return "backup_{$timestamp}_{$hash}";
    }

    /**
     * Get backup directory path.
     */
    protected function getBackupDirectory(string $backupId): string
    {
        return "{$this->backupPath}/{$backupId}";
    }

    /**
     * Create metadata for the backup.
     */
    protected function createMetadata(string $operationId, array $context): array
    {
        return [
            'operation_id' => $operationId,
            'created_at' => now()->toISOString(),
            'context' => $context,
            'files' => [],
            'strategy' => $this->getName(),
            'version' => '1.0',
        ];
    }

    /**
     * Backup files that might be affected by the operation.
     */
    protected function backupFiles(array $context, string $backupDir): void
    {
        $filesToBackup = $this->determineFilesToBackup($context);
        $metadata = [];

        foreach ($filesToBackup as $file) {
            if (File::exists($file)) {
                $relativePath = str_replace(base_path() . '/', '', $file);
                $backupFilePath = "{$backupDir}/files/{$relativePath}";
                
                // Ensure directory exists
                $backupFileDir = dirname($backupFilePath);
                Storage::disk($this->backupDisk)->makeDirectory($backupFileDir);
                
                // Copy file to backup
                Storage::disk($this->backupDisk)->put(
                    $backupFilePath,
                    File::get($file)
                );

                $metadata['files'][] = [
                    'original_path' => $file,
                    'relative_path' => $relativePath,
                    'backup_path' => $backupFilePath,
                    'size' => File::size($file),
                    'hash' => md5_file($file),
                ];
            }
        }

        // Update metadata with file information
        $metadataPath = "{$backupDir}/metadata.json";
        $existingMetadata = json_decode(
            Storage::disk($this->backupDisk)->get($metadataPath),
            true
        );
        $existingMetadata['files'] = $metadata['files'] ?? [];

        Storage::disk($this->backupDisk)->put(
            $metadataPath,
            json_encode($existingMetadata, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Determine which files need to be backed up based on context.
     */
    protected function determineFilesToBackup(array $context): array
    {
        $files = [];

        // Backup model files
        if (isset($context['model_name'])) {
            $files[] = app_path("Models/{$context['model_name']}.php");
        }

        // Backup controller files
        if (isset($context['controller_name'])) {
            $files[] = app_path("Http/Controllers/{$context['controller_name']}.php");
        }

        // Backup migration files
        if (isset($context['table_name'])) {
            $migrationFiles = File::glob(database_path("migrations/*_create_{$context['table_name']}_table.php"));
            $files = array_merge($files, $migrationFiles);
        }

        // Backup factory files
        if (isset($context['factory_name'])) {
            $files[] = database_path("factories/{$context['factory_name']}.php");
        }

        // Add any explicitly specified files
        if (isset($context['files_to_backup'])) {
            $files = array_merge($files, (array) $context['files_to_backup']);
        }

        return array_filter($files);
    }

    /**
     * Restore files from backup.
     */
    protected function restoreFiles(string $backupDir, array $metadata): bool
    {
        if (!isset($metadata['files'])) {
            return true; // No files to restore
        }

        foreach ($metadata['files'] as $fileInfo) {
            $backupFilePath = $fileInfo['backup_path'];
            $originalPath = $fileInfo['original_path'];

            if (Storage::disk($this->backupDisk)->exists($backupFilePath)) {
                $content = Storage::disk($this->backupDisk)->get($backupFilePath);
                
                // Ensure directory exists
                $directory = dirname($originalPath);
                if (!File::isDirectory($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                File::put($originalPath, $content);
            }
        }

        return true;
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