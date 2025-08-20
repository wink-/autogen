<?php

declare(strict_types=1);

namespace AutoGen\Common\Rollback;

use AutoGen\Common\Contracts\BackupManagerInterface;
use AutoGen\Common\Contracts\RollbackStrategyInterface;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Manages backup operations across multiple rollback strategies.
 */
class BackupManager implements BackupManagerInterface
{
    protected Collection $strategies;
    protected array $operationBackups = [];
    protected string $backupDisk;
    protected string $indexPath;

    public function __construct(string $backupDisk = 'local', string $indexPath = 'autogen/backup_index.json')
    {
        $this->strategies = collect();
        $this->backupDisk = $backupDisk;
        $this->indexPath = $indexPath;
        
        $this->loadBackupIndex();
    }

    public function registerStrategy(string $name, RollbackStrategyInterface $strategy): void
    {
        $this->strategies->put($name, $strategy);
    }

    public function getStrategy(string $name): ?RollbackStrategyInterface
    {
        return $this->strategies->get($name);
    }

    public function createBackup(string $operationId, array $context, array $strategies = []): array
    {
        // Use all strategies if none specified
        if (empty($strategies)) {
            $strategies = $this->strategies->keys()->all();
        }

        $backupIds = [];
        $errors = [];
        $createdBackups = [];

        foreach ($strategies as $strategyName) {
            $strategy = $this->getStrategy($strategyName);
            
            if (!$strategy) {
                $errors[$strategyName] = "Strategy not found";
                continue;
            }

            try {
                $backupId = $strategy->createBackup($operationId, $context);
                $backupIds[$strategyName] = $backupId;
                $createdBackups[] = ['strategy' => $strategyName, 'backup_id' => $backupId];
            } catch (\Exception $e) {
                $errors[$strategyName] = $e->getMessage();
                
                // If this is a critical error, rollback already created backups
                if ($this->isCriticalError($e, $strategyName, $context)) {
                    $this->rollbackCreatedBackups($createdBackups);
                    throw RollbackException::backupCreationFailed(
                        $operationId, 
                        "Critical strategy '{$strategyName}' failed: " . $e->getMessage()
                    );
                }
            }
        }

        // Store operation backup mapping
        $this->operationBackups[$operationId] = [
            'operation_id' => $operationId,
            'created_at' => now()->toISOString(),
            'context' => $context,
            'strategy_backups' => $backupIds,
            'errors' => $errors,
        ];

        $this->saveBackupIndex();

        return [
            'operation_id' => $operationId,
            'strategy_backups' => $backupIds,
            'errors' => $errors,
            'success_count' => count($backupIds),
            'error_count' => count($errors),
        ];
    }

    public function restore(string $operationId): bool
    {
        if (!isset($this->operationBackups[$operationId])) {
            throw RollbackException::backupNotFound($operationId);
        }

        $operationBackup = $this->operationBackups[$operationId];
        $strategyBackups = $operationBackup['strategy_backups'];
        $errors = [];
        $restored = [];

        // Restore in reverse order for proper rollback sequence
        $strategyBackups = array_reverse($strategyBackups, true);

        foreach ($strategyBackups as $strategyName => $backupId) {
            $strategy = $this->getStrategy($strategyName);
            
            if (!$strategy) {
                $errors[$strategyName] = "Strategy no longer available";
                continue;
            }

            try {
                if ($strategy->restore($backupId)) {
                    $restored[] = $strategyName;
                } else {
                    $errors[$strategyName] = "Restore operation failed";
                }
            } catch (\Exception $e) {
                $errors[$strategyName] = $e->getMessage();
            }
        }

        // Check if critical strategies failed
        $criticalStrategies = $this->getCriticalStrategiesForOperation($operationId);
        $failedCritical = array_intersect($criticalStrategies, array_keys($errors));

        if (!empty($failedCritical)) {
            throw RollbackException::restoreFailed(
                $operationId, 
                'Critical strategies failed: ' . implode(', ', $failedCritical)
            );
        }

        return true;
    }

    public function listAllBackups(): array
    {
        $allBackups = [];

        foreach ($this->operationBackups as $operationId => $operationBackup) {
            $backupInfo = [
                'operation_id' => $operationId,
                'created_at' => $operationBackup['created_at'],
                'context' => $operationBackup['context'],
                'strategies' => [],
                'total_size' => 0,
                'errors' => $operationBackup['errors'] ?? [],
            ];

            foreach ($operationBackup['strategy_backups'] as $strategyName => $backupId) {
                $strategy = $this->getStrategy($strategyName);
                
                if ($strategy && $strategy->hasBackup($backupId)) {
                    $metadata = $strategy->getBackupMetadata($backupId);
                    $size = 0;
                    
                    // Get size if strategy supports it
                    if (method_exists($strategy, 'getBackupSize')) {
                        $size = $strategy->getBackupSize($backupId);
                        $backupInfo['total_size'] += $size;
                    }
                    
                    $backupInfo['strategies'][$strategyName] = [
                        'backup_id' => $backupId,
                        'metadata' => $metadata,
                        'size' => $size,
                        'can_restore' => $strategy->canRestore($backupId),
                    ];
                }
            }

            $allBackups[] = $backupInfo;
        }

        return collect($allBackups)->sortByDesc('created_at')->values()->all();
    }

    public function removeAllBackups(string $operationId): bool
    {
        if (!isset($this->operationBackups[$operationId])) {
            return false;
        }

        $operationBackup = $this->operationBackups[$operationId];
        $allRemoved = true;

        foreach ($operationBackup['strategy_backups'] as $strategyName => $backupId) {
            $strategy = $this->getStrategy($strategyName);
            
            if ($strategy && !$strategy->removeBackup($backupId)) {
                $allRemoved = false;
            }
        }

        // Remove from index
        unset($this->operationBackups[$operationId]);
        $this->saveBackupIndex();

        return $allRemoved;
    }

    public function performCleanup(int $retentionDays = 30): array
    {
        $results = [];

        // Clean up each strategy
        foreach ($this->strategies as $strategyName => $strategy) {
            try {
                $cleaned = $strategy->cleanupBackups($retentionDays);
                $results[$strategyName] = [
                    'cleaned' => $cleaned,
                    'error' => null,
                ];
            } catch (\Exception $e) {
                $results[$strategyName] = [
                    'cleaned' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Clean up operation backups index
        $cutoffDate = now()->subDays($retentionDays);
        $removedOperations = 0;

        foreach ($this->operationBackups as $operationId => $operationBackup) {
            $createdAt = \Carbon\Carbon::parse($operationBackup['created_at']);
            
            if ($createdAt->lt($cutoffDate)) {
                unset($this->operationBackups[$operationId]);
                $removedOperations++;
            }
        }

        if ($removedOperations > 0) {
            $this->saveBackupIndex();
        }

        $results['_operation_index'] = [
            'cleaned' => $removedOperations,
            'error' => null,
        ];

        return $results;
    }

    /**
     * Get backup information for a specific operation.
     */
    public function getOperationBackup(string $operationId): ?array
    {
        return $this->operationBackups[$operationId] ?? null;
    }

    /**
     * Check if operation has backups.
     */
    public function hasOperationBackups(string $operationId): bool
    {
        return isset($this->operationBackups[$operationId]);
    }

    /**
     * Get all registered strategy names.
     */
    public function getRegisteredStrategies(): array
    {
        return $this->strategies->keys()->all();
    }

    /**
     * Validate if operation can be restored.
     */
    public function canRestoreOperation(string $operationId): bool
    {
        if (!$this->hasOperationBackups($operationId)) {
            return false;
        }

        $operationBackup = $this->operationBackups[$operationId];
        $criticalStrategies = $this->getCriticalStrategiesForOperation($operationId);

        foreach ($criticalStrategies as $strategyName) {
            if (!isset($operationBackup['strategy_backups'][$strategyName])) {
                return false;
            }

            $strategy = $this->getStrategy($strategyName);
            $backupId = $operationBackup['strategy_backups'][$strategyName];

            if (!$strategy || !$strategy->canRestore($backupId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Load backup index from storage.
     */
    protected function loadBackupIndex(): void
    {
        if (Storage::disk($this->backupDisk)->exists($this->indexPath)) {
            try {
                $content = Storage::disk($this->backupDisk)->get($this->indexPath);
                $this->operationBackups = json_decode($content, true) ?? [];
            } catch (\Exception) {
                $this->operationBackups = [];
            }
        }
    }

    /**
     * Save backup index to storage.
     */
    protected function saveBackupIndex(): void
    {
        try {
            Storage::disk($this->backupDisk)->put(
                $this->indexPath,
                json_encode($this->operationBackups, JSON_PRETTY_PRINT)
            );
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            \Log::warning('Failed to save backup index: ' . $e->getMessage());
        }
    }

    /**
     * Rollback already created backups in case of failure.
     */
    protected function rollbackCreatedBackups(array $createdBackups): void
    {
        foreach (array_reverse($createdBackups) as $backupInfo) {
            $strategy = $this->getStrategy($backupInfo['strategy']);
            
            if ($strategy) {
                try {
                    $strategy->removeBackup($backupInfo['backup_id']);
                } catch (\Exception) {
                    // Ignore cleanup errors
                }
            }
        }
    }

    /**
     * Determine if an error is critical.
     */
    protected function isCriticalError(\Exception $e, string $strategyName, array $context): bool
    {
        // File backup failures are usually critical
        if ($strategyName === 'file_backup') {
            return true;
        }

        // Database backup failures are critical for database operations
        if ($strategyName === 'database_rollback' && 
            (isset($context['table_name']) || isset($context['migration']))) {
            return true;
        }

        return false;
    }

    /**
     * Get critical strategies for an operation.
     */
    protected function getCriticalStrategiesForOperation(string $operationId): array
    {
        if (!isset($this->operationBackups[$operationId])) {
            return [];
        }

        $operationBackup = $this->operationBackups[$operationId];
        $context = $operationBackup['context'] ?? [];
        $critical = [];

        foreach (array_keys($operationBackup['strategy_backups']) as $strategyName) {
            if ($strategyName === 'file_backup') {
                $critical[] = $strategyName;
            }

            if ($strategyName === 'database_rollback' && 
                (isset($context['table_name']) || isset($context['migration']))) {
                $critical[] = $strategyName;
            }
        }

        return $critical;
    }
}