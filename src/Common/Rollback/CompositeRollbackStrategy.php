<?php

declare(strict_types=1);

namespace AutoGen\Common\Rollback;

use AutoGen\Common\Contracts\RollbackStrategyInterface;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Support\Collection;

/**
 * Composite rollback strategy that coordinates multiple rollback strategies.
 */
class CompositeRollbackStrategy implements RollbackStrategyInterface
{
    protected Collection $strategies;
    protected array $strategyBackups = [];

    public function __construct(array $strategies = [])
    {
        $this->strategies = collect();
        
        foreach ($strategies as $strategy) {
            $this->addStrategy($strategy);
        }
    }

    /**
     * Add a strategy to the composite.
     */
    public function addStrategy(RollbackStrategyInterface $strategy): self
    {
        $this->strategies->put($strategy->getName(), $strategy);
        return $this;
    }

    /**
     * Remove a strategy from the composite.
     */
    public function removeStrategy(string $strategyName): self
    {
        $this->strategies->forget($strategyName);
        return $this;
    }

    /**
     * Get a specific strategy.
     */
    public function getStrategy(string $strategyName): ?RollbackStrategyInterface
    {
        return $this->strategies->get($strategyName);
    }

    /**
     * Get all strategies.
     */
    public function getStrategies(): Collection
    {
        return $this->strategies;
    }

    public function createBackup(string $operationId, array $context): string
    {
        $compositeBackupId = $this->generateCompositeBackupId($operationId);
        $strategyBackups = [];
        $errors = [];

        foreach ($this->strategies as $strategyName => $strategy) {
            try {
                $backupId = $strategy->createBackup($operationId, $context);
                $strategyBackups[$strategyName] = $backupId;
            } catch (\Exception $e) {
                $errors[$strategyName] = $e->getMessage();
                
                // If this is a critical strategy, rollback already created backups
                if ($this->isCriticalStrategy($strategyName, $context)) {
                    $this->rollbackPartialBackups($strategyBackups);
                    throw RollbackException::backupCreationFailed(
                        $operationId, 
                        "Critical strategy '{$strategyName}' failed: " . $e->getMessage()
                    );
                }
            }
        }

        // Store mapping of composite backup to individual strategy backups
        $this->strategyBackups[$compositeBackupId] = [
            'operation_id' => $operationId,
            'created_at' => now()->toISOString(),
            'strategy_backups' => $strategyBackups,
            'errors' => $errors,
        ];

        return $compositeBackupId;
    }

    public function restore(string $backupId): bool
    {
        if (!$this->hasBackup($backupId)) {
            throw RollbackException::backupNotFound($backupId);
        }

        $backupInfo = $this->strategyBackups[$backupId];
        $errors = [];
        $restoredStrategies = [];

        // Restore in reverse order for proper rollback
        $strategyBackups = array_reverse($backupInfo['strategy_backups'], true);

        foreach ($strategyBackups as $strategyName => $strategyBackupId) {
            $strategy = $this->strategies->get($strategyName);
            if (!$strategy) {
                $errors[$strategyName] = "Strategy no longer available";
                continue;
            }

            try {
                if ($strategy->restore($strategyBackupId)) {
                    $restoredStrategies[] = $strategyName;
                } else {
                    $errors[$strategyName] = "Restore returned false";
                }
            } catch (\Exception $e) {
                $errors[$strategyName] = $e->getMessage();
            }
        }

        // If any critical strategies failed, this is a failure
        $criticalStrategies = $this->getCriticalStrategies($backupInfo);
        $failedCritical = array_intersect($criticalStrategies, array_keys($errors));

        if (!empty($failedCritical)) {
            throw RollbackException::restoreFailed(
                $backupId, 
                'Critical strategies failed: ' . implode(', ', $failedCritical)
            );
        }

        return true;
    }

    public function hasBackup(string $backupId): bool
    {
        return isset($this->strategyBackups[$backupId]);
    }

    public function listBackups(): array
    {
        $backups = [];

        foreach ($this->strategyBackups as $backupId => $backupInfo) {
            $strategyBackups = [];
            $totalSize = 0;

            foreach ($backupInfo['strategy_backups'] as $strategyName => $strategyBackupId) {
                $strategy = $this->strategies->get($strategyName);
                if ($strategy && $strategy->hasBackup($strategyBackupId)) {
                    $metadata = $strategy->getBackupMetadata($strategyBackupId);
                    $strategyBackups[$strategyName] = [
                        'backup_id' => $strategyBackupId,
                        'metadata' => $metadata,
                    ];
                    
                    // Calculate size if available
                    if (method_exists($strategy, 'getBackupSize')) {
                        $totalSize += $strategy->getBackupSize($strategyBackupId);
                    }
                }
            }

            $backups[] = [
                'id' => $backupId,
                'operation_id' => $backupInfo['operation_id'],
                'created_at' => $backupInfo['created_at'],
                'strategies_count' => count($strategyBackups),
                'strategy_backups' => $strategyBackups,
                'errors' => $backupInfo['errors'] ?? [],
                'total_size' => $totalSize,
            ];
        }

        return collect($backups)->sortByDesc('created_at')->values()->all();
    }

    public function removeBackup(string $backupId): bool
    {
        if (!$this->hasBackup($backupId)) {
            return false;
        }

        $backupInfo = $this->strategyBackups[$backupId];
        $allRemoved = true;

        foreach ($backupInfo['strategy_backups'] as $strategyName => $strategyBackupId) {
            $strategy = $this->strategies->get($strategyName);
            if ($strategy && !$strategy->removeBackup($strategyBackupId)) {
                $allRemoved = false;
            }
        }

        // Remove from our tracking regardless of individual strategy success
        unset($this->strategyBackups[$backupId]);

        return $allRemoved;
    }

    public function cleanupBackups(int $retentionDays = 30): int
    {
        $cleaned = 0;

        // Cleanup individual strategies
        foreach ($this->strategies as $strategy) {
            $cleaned += $strategy->cleanupBackups($retentionDays);
        }

        // Cleanup our own tracking for orphaned backups
        $cutoffDate = now()->subDays($retentionDays);
        $toRemove = [];

        foreach ($this->strategyBackups as $backupId => $backupInfo) {
            $createdAt = \Carbon\Carbon::parse($backupInfo['created_at']);
            if ($createdAt->lt($cutoffDate)) {
                $toRemove[] = $backupId;
            }
        }

        foreach ($toRemove as $backupId) {
            unset($this->strategyBackups[$backupId]);
            $cleaned++;
        }

        return $cleaned;
    }

    public function getBackupMetadata(string $backupId): ?array
    {
        if (!$this->hasBackup($backupId)) {
            return null;
        }

        $backupInfo = $this->strategyBackups[$backupId];
        $strategyMetadata = [];

        foreach ($backupInfo['strategy_backups'] as $strategyName => $strategyBackupId) {
            $strategy = $this->strategies->get($strategyName);
            if ($strategy) {
                $strategyMetadata[$strategyName] = $strategy->getBackupMetadata($strategyBackupId);
            }
        }

        return [
            'composite_backup_id' => $backupId,
            'operation_id' => $backupInfo['operation_id'],
            'created_at' => $backupInfo['created_at'],
            'strategy_metadata' => $strategyMetadata,
            'errors' => $backupInfo['errors'] ?? [],
            'strategies_count' => count($backupInfo['strategy_backups']),
        ];
    }

    public function canRestore(string $backupId): bool
    {
        if (!$this->hasBackup($backupId)) {
            return false;
        }

        $backupInfo = $this->strategyBackups[$backupId];
        $criticalStrategies = $this->getCriticalStrategies($backupInfo);

        // Check if all critical strategies can restore
        foreach ($criticalStrategies as $strategyName) {
            if (!isset($backupInfo['strategy_backups'][$strategyName])) {
                return false;
            }

            $strategy = $this->strategies->get($strategyName);
            $strategyBackupId = $backupInfo['strategy_backups'][$strategyName];

            if (!$strategy || !$strategy->canRestore($strategyBackupId)) {
                return false;
            }
        }

        return true;
    }

    public function getName(): string
    {
        return 'composite_rollback';
    }

    /**
     * Generate a composite backup ID.
     */
    protected function generateCompositeBackupId(string $operationId): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $hash = substr(md5($operationId . microtime()), 0, 8);
        return "composite_{$timestamp}_{$hash}";
    }

    /**
     * Rollback partially created backups in case of failure.
     */
    protected function rollbackPartialBackups(array $strategyBackups): void
    {
        foreach ($strategyBackups as $strategyName => $backupId) {
            $strategy = $this->strategies->get($strategyName);
            if ($strategy) {
                try {
                    $strategy->removeBackup($backupId);
                } catch (\Exception) {
                    // Ignore cleanup errors
                }
            }
        }
    }

    /**
     * Determine if a strategy is critical for the given context.
     */
    protected function isCriticalStrategy(string $strategyName, array $context): bool
    {
        // File backup is usually critical
        if ($strategyName === 'file_backup') {
            return true;
        }

        // Database backup is critical if we're dealing with database operations
        if ($strategyName === 'database_rollback' && 
            (isset($context['table_name']) || isset($context['migration']))) {
            return true;
        }

        return false;
    }

    /**
     * Get critical strategies from backup info.
     */
    protected function getCriticalStrategies(array $backupInfo): array
    {
        $critical = [];
        
        foreach (array_keys($backupInfo['strategy_backups']) as $strategyName) {
            if (in_array($strategyName, ['file_backup', 'database_rollback'])) {
                $critical[] = $strategyName;
            }
        }

        return $critical;
    }
}