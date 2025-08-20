<?php

declare(strict_types=1);

namespace AutoGen\Common\Traits;

use AutoGen\Common\Contracts\BackupManagerInterface;
use AutoGen\Common\Contracts\RollbackableGeneratorInterface;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Support\Str;

/**
 * Trait for adding rollback support to generators.
 */
trait HasRollbackSupport
{
    protected ?BackupManagerInterface $backupManager = null;
    protected array $rollbackStrategies = ['file_backup'];
    protected ?string $currentOperationId = null;

    /**
     * Set the backup manager.
     */
    public function setBackupManager(BackupManagerInterface $backupManager): void
    {
        $this->backupManager = $backupManager;
    }

    /**
     * Get the backup manager.
     */
    protected function getBackupManager(): BackupManagerInterface
    {
        if (!$this->backupManager) {
            $this->backupManager = app(BackupManagerInterface::class);
        }

        return $this->backupManager;
    }

    /**
     * Generate content with rollback support.
     */
    public function generateWithRollback(array $context, ?string $operationId = null): string
    {
        $operationId = $operationId ?? $this->generateOperationId($context);
        $this->currentOperationId = $operationId;

        try {
            // Create backup before generation
            $backupResult = $this->createOperationBackup($operationId, $context);
            
            // Perform the actual generation
            $result = $this->performGeneration($context);
            
            return $result;
        } catch (\Exception $e) {
            // Attempt automatic rollback on failure
            $this->handleGenerationFailure($operationId, $e);
            throw $e;
        }
    }

    /**
     * Rollback the last generation operation.
     */
    public function rollback(?string $operationId = null): bool
    {
        $operationId = $operationId ?? $this->currentOperationId;
        
        if (!$operationId) {
            throw new RollbackException('No operation ID available for rollback');
        }

        try {
            return $this->getBackupManager()->restore($operationId);
        } catch (RollbackException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw RollbackException::restoreFailed($operationId, $e->getMessage());
        }
    }

    /**
     * Get the backup strategies used by this generator.
     */
    public function getBackupStrategies(): array
    {
        return $this->rollbackStrategies;
    }

    /**
     * Get rollback points for this generator.
     */
    public function getRollbackPoints(): array
    {
        $backups = $this->getBackupManager()->listAllBackups();
        $generatorName = $this->getGeneratorName();

        return array_filter($backups, function ($backup) use ($generatorName) {
            $context = $backup['context'] ?? [];
            return isset($context['generator']) && $context['generator'] === $generatorName;
        });
    }

    /**
     * Create operation backup.
     */
    protected function createOperationBackup(string $operationId, array $context): array
    {
        // Add generator information to context
        $context = array_merge($context, [
            'generator' => $this->getGeneratorName(),
            'operation_id' => $operationId,
            'operation_type' => $this->getOperationType($context),
            'timestamp' => now()->toISOString(),
        ]);

        // Add files that will be affected
        $context['files_to_backup'] = $this->getFilesToBackup($context);

        return $this->getBackupManager()->createBackup(
            $operationId,
            $context,
            $this->getBackupStrategies()
        );
    }

    /**
     * Handle generation failure with automatic rollback.
     */
    protected function handleGenerationFailure(string $operationId, \Exception $exception): void
    {
        if (!$this->shouldAttemptAutoRollback($exception)) {
            return;
        }

        try {
            $this->rollback($operationId);
        } catch (\Exception $rollbackException) {
            // Log rollback failure but don't mask original exception
            \Log::error('Auto-rollback failed for operation ' . $operationId, [
                'original_exception' => $exception->getMessage(),
                'rollback_exception' => $rollbackException->getMessage(),
            ]);
        }
    }

    /**
     * Determine if automatic rollback should be attempted.
     */
    protected function shouldAttemptAutoRollback(\Exception $exception): bool
    {
        // Don't auto-rollback for validation errors or user input errors
        if ($exception instanceof \InvalidArgumentException) {
            return false;
        }

        // Auto-rollback for file system errors, database errors, etc.
        return true;
    }

    /**
     * Generate a unique operation ID.
     */
    protected function generateOperationId(array $context): string
    {
        $generator = $this->getGeneratorName();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $hash = substr(md5(serialize($context) . microtime()), 0, 8);
        
        return "{$generator}_{$timestamp}_{$hash}";
    }

    /**
     * Get the generator name for rollback tracking.
     */
    protected function getGeneratorName(): string
    {
        $className = class_basename(static::class);
        return Str::snake(str_replace('Generator', '', $className));
    }

    /**
     * Get the operation type based on context.
     */
    protected function getOperationType(array $context): string
    {
        if (isset($context['model_name'])) {
            return 'model_generation';
        }

        if (isset($context['controller_name'])) {
            return 'controller_generation';
        }

        if (isset($context['table_name'])) {
            return 'migration_generation';
        }

        return 'generic_generation';
    }

    /**
     * Get files that will be affected by this generation.
     */
    protected function getFilesToBackup(array $context): array
    {
        $files = [];

        // Model files
        if (isset($context['model_name'])) {
            $files[] = app_path("Models/{$context['model_name']}.php");
        }

        // Controller files
        if (isset($context['controller_name'])) {
            $files[] = app_path("Http/Controllers/{$context['controller_name']}.php");
        }

        // Factory files
        if (isset($context['factory_name'])) {
            $files[] = database_path("factories/{$context['factory_name']}.php");
        }

        // Migration files
        if (isset($context['table_name'])) {
            $migrationPattern = database_path("migrations/*_{$context['table_name']}_*.php");
            $files = array_merge($files, glob($migrationPattern) ?: []);
        }

        // Add any explicitly specified files
        if (isset($context['additional_files'])) {
            $files = array_merge($files, (array) $context['additional_files']);
        }

        return array_filter($files);
    }

    /**
     * Perform the actual generation (must be implemented by using class).
     */
    abstract protected function performGeneration(array $context): string;

    /**
     * Set rollback strategies for this generator.
     */
    protected function setRollbackStrategies(array $strategies): void
    {
        $this->rollbackStrategies = $strategies;
    }

    /**
     * Add a rollback strategy.
     */
    protected function addRollbackStrategy(string $strategy): void
    {
        if (!in_array($strategy, $this->rollbackStrategies)) {
            $this->rollbackStrategies[] = $strategy;
        }
    }

    /**
     * Remove a rollback strategy.
     */
    protected function removeRollbackStrategy(string $strategy): void
    {
        $this->rollbackStrategies = array_filter($this->rollbackStrategies, function ($s) use ($strategy) {
            return $s !== $strategy;
        });
    }

    /**
     * Create a rollback point at any time during generation.
     */
    protected function createRollbackPoint(string $name, array $additionalContext = []): string
    {
        $operationId = $this->generateOperationId(array_merge($additionalContext, ['rollback_point' => $name]));
        
        $context = array_merge($additionalContext, [
            'generator' => $this->getGeneratorName(),
            'rollback_point' => $name,
            'parent_operation' => $this->currentOperationId,
        ]);

        $this->getBackupManager()->createBackup($operationId, $context, ['file_backup']);
        
        return $operationId;
    }

    /**
     * Get the current operation ID.
     */
    protected function getCurrentOperationId(): ?string
    {
        return $this->currentOperationId;
    }

    /**
     * Execute a generation step with rollback protection.
     */
    protected function executeWithRollbackProtection(callable $operation, array $context = []): mixed
    {
        $rollbackPointId = $this->createRollbackPoint('before_step', $context);

        try {
            return $operation();
        } catch (\Exception $e) {
            // Rollback to the point before this step
            try {
                $this->getBackupManager()->restore($rollbackPointId);
            } catch (\Exception $rollbackException) {
                \Log::error('Step rollback failed', [
                    'rollback_point' => $rollbackPointId,
                    'original_error' => $e->getMessage(),
                    'rollback_error' => $rollbackException->getMessage(),
                ]);
            }

            throw $e;
        }
    }
}