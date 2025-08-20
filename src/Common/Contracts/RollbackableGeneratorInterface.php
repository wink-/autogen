<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

/**
 * Interface for generators that support rollback operations.
 */
interface RollbackableGeneratorInterface extends GeneratorInterface
{
    /**
     * Generate content with rollback support.
     */
    public function generateWithRollback(array $context, ?string $operationId = null): string;

    /**
     * Rollback the last generation operation.
     */
    public function rollback(?string $operationId = null): bool;

    /**
     * Get the backup strategies used by this generator.
     */
    public function getBackupStrategies(): array;

    /**
     * Set the backup manager.
     */
    public function setBackupManager(BackupManagerInterface $backupManager): void;

    /**
     * Get rollback points for this generator.
     */
    public function getRollbackPoints(): array;
}