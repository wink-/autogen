<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

use AutoGen\Common\Exceptions\RollbackException;

/**
 * Interface for rollback strategy implementations.
 */
interface RollbackStrategyInterface
{
    /**
     * Create a backup point for the given operation.
     */
    public function createBackup(string $operationId, array $context): string;

    /**
     * Restore from a backup point.
     * 
     * @throws RollbackException
     */
    public function restore(string $backupId): bool;

    /**
     * Check if a backup exists.
     */
    public function hasBackup(string $backupId): bool;

    /**
     * List all available backups.
     */
    public function listBackups(): array;

    /**
     * Remove a specific backup.
     */
    public function removeBackup(string $backupId): bool;

    /**
     * Clean up old backups based on retention policy.
     */
    public function cleanupBackups(int $retentionDays = 30): int;

    /**
     * Get backup metadata.
     */
    public function getBackupMetadata(string $backupId): ?array;

    /**
     * Validate if restore is possible.
     */
    public function canRestore(string $backupId): bool;

    /**
     * Get the strategy name.
     */
    public function getName(): string;
}