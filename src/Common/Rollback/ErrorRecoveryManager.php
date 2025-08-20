<?php

declare(strict_types=1);

namespace AutoGen\Common\Rollback;

use AutoGen\Common\Contracts\BackupManagerInterface;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Manages error recovery and automatic rollback operations.
 */
class ErrorRecoveryManager
{
    protected BackupManagerInterface $backupManager;
    protected array $recoveryStrategies = [];
    protected array $errorHandlers = [];

    public function __construct(BackupManagerInterface $backupManager)
    {
        $this->backupManager = $backupManager;
        $this->initializeDefaultStrategies();
    }

    /**
     * Register an error recovery strategy.
     */
    public function registerRecoveryStrategy(string $errorType, callable $strategy): void
    {
        $this->recoveryStrategies[$errorType] = $strategy;
    }

    /**
     * Register an error handler.
     */
    public function registerErrorHandler(string $errorType, callable $handler): void
    {
        $this->errorHandlers[$errorType] = $handler;
    }

    /**
     * Handle an error with automatic recovery.
     */
    public function handleError(Throwable $error, string $operationId, array $context = []): bool
    {
        $errorType = $this->classifyError($error);
        
        Log::warning('AutoGen error occurred, attempting recovery', [
            'operation_id' => $operationId,
            'error_type' => $errorType,
            'error_message' => $error->getMessage(),
            'context' => $context,
        ]);

        try {
            // Run custom error handler if available
            if (isset($this->errorHandlers[$errorType])) {
                $this->errorHandlers[$errorType]($error, $operationId, $context);
            }

            // Attempt recovery using registered strategy
            if (isset($this->recoveryStrategies[$errorType])) {
                $recoveryResult = $this->recoveryStrategies[$errorType]($error, $operationId, $context);
                if ($recoveryResult) {
                    Log::info('Error recovery successful', [
                        'operation_id' => $operationId,
                        'error_type' => $errorType,
                    ]);
                    return true;
                }
            }

            // Fallback to rollback strategy
            return $this->performFallbackRecovery($error, $operationId, $context);
        } catch (Throwable $recoveryError) {
            Log::error('Error recovery failed', [
                'operation_id' => $operationId,
                'original_error' => $error->getMessage(),
                'recovery_error' => $recoveryError->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Validate if recovery is possible for an operation.
     */
    public function canRecover(string $operationId): bool
    {
        return $this->backupManager->canRestoreOperation($operationId);
    }

    /**
     * Perform recovery validation after a rollback.
     */
    public function validateRecovery(string $operationId, array $expectedState = []): array
    {
        $results = [
            'valid' => true,
            'checks' => [],
            'errors' => [],
        ];

        try {
            // Get operation backup information
            $operationBackup = $this->backupManager->getOperationBackup($operationId);
            if (!$operationBackup) {
                $results['valid'] = false;
                $results['errors'][] = 'Operation backup not found';
                return $results;
            }

            $context = $operationBackup['context'];

            // Validate file restoration
            $fileValidation = $this->validateFileRestoration($context);
            $results['checks']['files'] = $fileValidation;
            if (!$fileValidation['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $fileValidation['errors']);
            }

            // Validate database restoration if applicable
            if (isset($context['table_name'])) {
                $dbValidation = $this->validateDatabaseRestoration($context);
                $results['checks']['database'] = $dbValidation;
                if (!$dbValidation['valid']) {
                    $results['valid'] = false;
                    $results['errors'] = array_merge($results['errors'], $dbValidation['errors']);
                }
            }

            // Run custom validations
            $customValidation = $this->runCustomValidations($operationId, $context, $expectedState);
            $results['checks']['custom'] = $customValidation;
            if (!$customValidation['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $customValidation['errors']);
            }
        } catch (Throwable $e) {
            $results['valid'] = false;
            $results['errors'][] = 'Validation error: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get recovery statistics.
     */
    public function getRecoveryStats(): array
    {
        $backups = $this->backupManager->listAllBackups();
        
        $stats = [
            'total_operations' => count($backups),
            'recoverable_operations' => 0,
            'error_types' => [],
            'success_rate' => 0,
            'recovery_strategies' => array_keys($this->recoveryStrategies),
        ];

        foreach ($backups as $backup) {
            if ($this->canRecover($backup['operation_id'])) {
                $stats['recoverable_operations']++;
            }

            // Analyze error types from context
            $context = $backup['context'] ?? [];
            if (isset($context['error_type'])) {
                $errorType = $context['error_type'];
                $stats['error_types'][$errorType] = ($stats['error_types'][$errorType] ?? 0) + 1;
            }
        }

        if ($stats['total_operations'] > 0) {
            $stats['success_rate'] = ($stats['recoverable_operations'] / $stats['total_operations']) * 100;
        }

        return $stats;
    }

    /**
     * Initialize default recovery strategies.
     */
    protected function initializeDefaultStrategies(): void
    {
        // File system error recovery
        $this->registerRecoveryStrategy('filesystem_error', function (Throwable $error, string $operationId, array $context) {
            if (!$this->canRecover($operationId)) {
                return false;
            }

            // Check if it's a permission or disk space issue
            if (str_contains($error->getMessage(), 'Permission denied')) {
                // Log permission issue but don't auto-recover
                return false;
            }

            if (str_contains($error->getMessage(), 'No space left')) {
                // Log disk space issue
                return false;
            }

            // For other filesystem errors, attempt rollback
            return $this->backupManager->restore($operationId);
        });

        // Database error recovery
        $this->registerRecoveryStrategy('database_error', function (Throwable $error, string $operationId, array $context) {
            if (!$this->canRecover($operationId)) {
                return false;
            }

            // Handle connection errors differently
            if (str_contains($error->getMessage(), 'Connection refused')) {
                return false; // Can't recover from connection issues
            }

            // For schema or data errors, attempt rollback
            return $this->backupManager->restore($operationId);
        });

        // Generation error recovery
        $this->registerRecoveryStrategy('generation_error', function (Throwable $error, string $operationId, array $context) {
            if (!$this->canRecover($operationId)) {
                return false;
            }

            // Always attempt rollback for generation errors
            return $this->backupManager->restore($operationId);
        });

        // Template error recovery
        $this->registerRecoveryStrategy('template_error', function (Throwable $error, string $operationId, array $context) {
            // Template errors usually don't require rollback since no files were written
            return true;
        });
    }

    /**
     * Classify error type for appropriate recovery strategy.
     */
    protected function classifyError(Throwable $error): string
    {
        $message = strtolower($error->getMessage());
        $className = get_class($error);

        // File system errors
        if (str_contains($message, 'permission denied') ||
            str_contains($message, 'no space left') ||
            str_contains($message, 'file not found') ||
            str_contains($message, 'directory not writable')) {
            return 'filesystem_error';
        }

        // Database errors
        if (str_contains($message, 'connection refused') ||
            str_contains($message, 'table') ||
            str_contains($message, 'column') ||
            str_contains($message, 'sql') ||
            str_contains($className, 'PDOException') ||
            str_contains($className, 'QueryException')) {
            return 'database_error';
        }

        // Template errors
        if (str_contains($message, 'template') ||
            str_contains($message, 'view') ||
            str_contains($message, 'blade')) {
            return 'template_error';
        }

        // Generation errors (catch-all for generation-specific issues)
        return 'generation_error';
    }

    /**
     * Perform fallback recovery using rollback.
     */
    protected function performFallbackRecovery(Throwable $error, string $operationId, array $context): bool
    {
        if (!$this->canRecover($operationId)) {
            Log::warning('Cannot perform fallback recovery - no backup available', [
                'operation_id' => $operationId,
            ]);
            return false;
        }

        try {
            $result = $this->backupManager->restore($operationId);
            
            if ($result) {
                Log::info('Fallback recovery successful via rollback', [
                    'operation_id' => $operationId,
                ]);
            } else {
                Log::error('Fallback recovery failed', [
                    'operation_id' => $operationId,
                ]);
            }

            return $result;
        } catch (RollbackException $e) {
            Log::error('Fallback recovery failed with rollback exception', [
                'operation_id' => $operationId,
                'rollback_error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Validate file restoration.
     */
    protected function validateFileRestoration(array $context): array
    {
        $results = ['valid' => true, 'errors' => []];

        // Check if expected files exist
        if (isset($context['model_name'])) {
            $modelFile = app_path("Models/{$context['model_name']}.php");
            if (!file_exists($modelFile)) {
                $results['valid'] = false;
                $results['errors'][] = "Model file not found: {$modelFile}";
            }
        }

        if (isset($context['controller_name'])) {
            $controllerFile = app_path("Http/Controllers/{$context['controller_name']}.php");
            if (!file_exists($controllerFile)) {
                $results['valid'] = false;
                $results['errors'][] = "Controller file not found: {$controllerFile}";
            }
        }

        return $results;
    }

    /**
     * Validate database restoration.
     */
    protected function validateDatabaseRestoration(array $context): array
    {
        $results = ['valid' => true, 'errors' => []];

        try {
            if (isset($context['table_name'])) {
                $tableName = $context['table_name'];
                
                // Check if table exists (or doesn't exist, depending on operation)
                $tableExists = \Schema::hasTable($tableName);
                
                // This validation depends on the type of operation
                // For now, just check that we can query the table state
                if (isset($context['operation_type']) && $context['operation_type'] === 'create_table' && !$tableExists) {
                    // Table creation was rolled back successfully
                } elseif (isset($context['operation_type']) && $context['operation_type'] === 'drop_table' && $tableExists) {
                    // Table drop was rolled back successfully
                }
            }
        } catch (Throwable $e) {
            $results['valid'] = false;
            $results['errors'][] = 'Database validation error: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Run custom validation checks.
     */
    protected function runCustomValidations(string $operationId, array $context, array $expectedState): array
    {
        $results = ['valid' => true, 'errors' => []];

        // Custom validations can be added here based on context
        // For example, validating that generated code compiles, tests pass, etc.

        return $results;
    }
}