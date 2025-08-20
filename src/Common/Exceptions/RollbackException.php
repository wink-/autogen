<?php

declare(strict_types=1);

namespace AutoGen\Common\Exceptions;

use Exception;

/**
 * Exception thrown when rollback operations fail.
 */
class RollbackException extends AutoGenException
{
    /**
     * Create exception for backup creation failure.
     */
    public static function backupCreationFailed(string $operationId, string $reason = ''): static
    {
        $message = "Failed to create backup for operation '{$operationId}'";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new static($message);
    }

    /**
     * Create exception for restore failure.
     */
    public static function restoreFailed(string $backupId, string $reason = ''): static
    {
        $message = "Failed to restore from backup '{$backupId}'";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new static($message);
    }

    /**
     * Create exception for backup not found.
     */
    public static function backupNotFound(string $backupId): static
    {
        return new static("Backup '{$backupId}' not found");
    }

    /**
     * Create exception for invalid backup state.
     */
    public static function invalidBackupState(string $backupId, string $reason = ''): static
    {
        $message = "Backup '{$backupId}' is in an invalid state";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new static($message);
    }

    /**
     * Create exception for strategy not found.
     */
    public static function strategyNotFound(string $strategyName): static
    {
        return new static("Rollback strategy '{$strategyName}' not found");
    }
}