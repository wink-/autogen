<?php

declare(strict_types=1);

namespace AutoGen\Commands\Rollback;

use AutoGen\Common\Contracts\BackupManagerInterface;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Command for rolling back AutoGen operations.
 */
class RollbackCommand extends Command
{
    protected $signature = 'autogen:rollback 
                          {operation? : The operation ID to rollback}
                          {--list : List available rollback operations}
                          {--latest : Rollback the latest operation}
                          {--confirm : Skip confirmation prompts}
                          {--strategy= : Specific strategy to use for rollback}';

    protected $description = 'Rollback AutoGen operations to restore previous state';

    protected BackupManagerInterface $backupManager;

    public function __construct(BackupManagerInterface $backupManager)
    {
        parent::__construct();
        $this->backupManager = $backupManager;
    }

    public function handle(): int
    {
        try {
            if ($this->option('list')) {
                return $this->listRollbackOperations();
            }

            $operationId = $this->getOperationId();
            
            if (!$operationId) {
                $this->error('No operation ID specified. Use --list to see available operations or --latest for the most recent.');
                return self::FAILURE;
            }

            return $this->performRollback($operationId);
        } catch (RollbackException $e) {
            $this->error('Rollback failed: ' . $e->getMessage());
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Unexpected error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * List available rollback operations.
     */
    protected function listRollbackOperations(): int
    {
        $backups = $this->backupManager->listAllBackups();

        if (empty($backups)) {
            $this->info('No rollback operations available.');
            return self::SUCCESS;
        }

        $this->info('Available rollback operations:');
        $this->newLine();

        $headers = ['Operation ID', 'Created At', 'Strategies', 'Size', 'Can Restore', 'Context'];
        $rows = [];

        foreach ($backups as $backup) {
            $strategies = collect($backup['strategies'])->keys()->implode(', ');
            $canRestore = $this->backupManager->canRestoreOperation($backup['operation_id']) ? '✓' : '✗';
            $size = $this->formatBytes($backup['total_size']);
            
            // Format context for display
            $context = $this->formatContext($backup['context']);

            $rows[] = [
                $backup['operation_id'],
                \Carbon\Carbon::parse($backup['created_at'])->format('Y-m-d H:i:s'),
                $strategies,
                $size,
                $canRestore,
                $context,
            ];
        }

        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info('Use: php artisan autogen:rollback <operation-id>');
        $this->info('Or:  php artisan autogen:rollback --latest');

        return self::SUCCESS;
    }

    /**
     * Get the operation ID to rollback.
     */
    protected function getOperationId(): ?string
    {
        // Check if operation ID was provided as argument
        if ($this->argument('operation')) {
            return $this->argument('operation');
        }

        // Check if --latest flag was used
        if ($this->option('latest')) {
            return $this->getLatestOperationId();
        }

        // Interactive selection
        return $this->selectOperationInteractively();
    }

    /**
     * Get the latest operation ID.
     */
    protected function getLatestOperationId(): ?string
    {
        $backups = $this->backupManager->listAllBackups();
        
        if (empty($backups)) {
            return null;
        }

        return $backups[0]['operation_id'];
    }

    /**
     * Select operation interactively.
     */
    protected function selectOperationInteractively(): ?string
    {
        $backups = $this->backupManager->listAllBackups();
        
        if (empty($backups)) {
            return null;
        }

        $choices = [];
        foreach ($backups as $backup) {
            $context = $this->formatContext($backup['context']);
            $createdAt = \Carbon\Carbon::parse($backup['created_at'])->format('Y-m-d H:i:s');
            
            $choices[$backup['operation_id']] = "{$backup['operation_id']} ({$createdAt}) - {$context}";
        }

        return $this->choice('Select operation to rollback:', $choices);
    }

    /**
     * Perform the rollback operation.
     */
    protected function performRollback(string $operationId): int
    {
        // Validate operation exists and can be restored
        if (!$this->backupManager->hasOperationBackups($operationId)) {
            $this->error("Operation '{$operationId}' not found.");
            return self::FAILURE;
        }

        if (!$this->backupManager->canRestoreOperation($operationId)) {
            $this->error("Operation '{$operationId}' cannot be restored. Some backup data may be missing or corrupted.");
            return self::FAILURE;
        }

        // Show operation details
        $this->displayOperationDetails($operationId);

        // Confirm rollback
        if (!$this->confirmRollback($operationId)) {
            $this->info('Rollback cancelled.');
            return self::SUCCESS;
        }

        $this->info("Starting rollback for operation: {$operationId}");
        $this->newLine();

        // Perform rollback with progress
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat('debug');
        $progressBar->start();

        try {
            $progressBar->setMessage('Preparing rollback...');
            $progressBar->advance();

            $progressBar->setMessage('Restoring from backups...');
            $result = $this->backupManager->restore($operationId);
            $progressBar->advance();

            $progressBar->setMessage('Rollback completed');
            $progressBar->finish();

            $this->newLine(2);
            
            if ($result) {
                $this->info("✓ Rollback completed successfully for operation: {$operationId}");
                return self::SUCCESS;
            } else {
                $this->error("✗ Rollback failed for operation: {$operationId}");
                return self::FAILURE;
            }
        } catch (RollbackException $e) {
            $progressBar->finish();
            $this->newLine(2);
            $this->error("✗ Rollback failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Display operation details.
     */
    protected function displayOperationDetails(string $operationId): void
    {
        $operationBackup = $this->backupManager->getOperationBackup($operationId);
        
        if (!$operationBackup) {
            return;
        }

        $this->info('Operation Details:');
        $this->line("  ID: {$operationId}");
        $this->line("  Created: " . \Carbon\Carbon::parse($operationBackup['created_at'])->format('Y-m-d H:i:s'));
        $this->line("  Context: " . $this->formatContext($operationBackup['context']));
        
        if (!empty($operationBackup['strategy_backups'])) {
            $this->line("  Strategies: " . implode(', ', array_keys($operationBackup['strategy_backups'])));
        }
        
        if (!empty($operationBackup['errors'])) {
            $this->line("  Errors: " . count($operationBackup['errors']) . " strategy errors occurred during backup");
        }

        $this->newLine();
    }

    /**
     * Confirm rollback operation.
     */
    protected function confirmRollback(string $operationId): bool
    {
        if ($this->option('confirm')) {
            return true;
        }

        $this->warn('WARNING: This operation will restore files and database to their previous state.');
        $this->warn('Any changes made after the backup was created will be lost.');
        $this->newLine();

        return $this->confirm("Are you sure you want to rollback operation '{$operationId}'?");
    }

    /**
     * Format context for display.
     */
    protected function formatContext(array $context): string
    {
        $parts = [];

        if (isset($context['model_name'])) {
            $parts[] = "Model: {$context['model_name']}";
        }

        if (isset($context['table_name'])) {
            $parts[] = "Table: {$context['table_name']}";
        }

        if (isset($context['controller_name'])) {
            $parts[] = "Controller: {$context['controller_name']}";
        }

        if (isset($context['operation_type'])) {
            $parts[] = "Type: {$context['operation_type']}";
        }

        if (empty($parts) && !empty($context)) {
            $parts[] = 'Custom operation';
        }

        return empty($parts) ? 'Unknown operation' : implode(', ', $parts);
    }

    /**
     * Format bytes for human-readable display.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));

        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[$factor] ?? 'TB');
    }
}