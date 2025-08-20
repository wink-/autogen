<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

/**
 * Interface for managing backup operations across different strategies.
 */
interface BackupManagerInterface
{
    /**
     * Register a rollback strategy.
     */
    public function registerStrategy(string $name, RollbackStrategyInterface $strategy): void;

    /**
     * Get a registered strategy.
     */
    public function getStrategy(string $name): ?RollbackStrategyInterface;

    /**
     * Create a backup using specified strategies.
     */
    public function createBackup(string $operationId, array $context, array $strategies = []): array;

    /**
     * Restore from backup using all associated strategies.
     */
    public function restore(string $operationId): bool;

    /**
     * List all backups across strategies.
     */
    public function listAllBackups(): array;

    /**
     * Remove all backups for an operation.
     */
    public function removeAllBackups(string $operationId): bool;

    /**
     * Perform cleanup across all strategies.
     */
    public function performCleanup(int $retentionDays = 30): array;
}