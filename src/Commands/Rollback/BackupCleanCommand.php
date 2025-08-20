<?php

declare(strict_types=1);

namespace AutoGen\Commands\Rollback;

use AutoGen\Common\Contracts\BackupManagerInterface;
use Illuminate\Console\Command;

/**
 * Command for cleaning up old backups.
 */
class BackupCleanCommand extends Command
{
    protected $signature = 'autogen:backup:clean
                          {--days=30 : Number of days to retain backups}
                          {--strategy= : Clean only specific strategy}
                          {--dry-run : Show what would be cleaned without actually deleting}
                          {--force : Skip confirmation prompts}';

    protected $description = 'Clean up old AutoGen backups based on retention policy';

    protected BackupManagerInterface $backupManager;

    public function __construct(BackupManagerInterface $backupManager)
    {
        parent::__construct();
        $this->backupManager = $backupManager;
    }

    public function handle(): int
    {
        $retentionDays = (int) $this->option('days');
        $strategyFilter = $this->option('strategy');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($retentionDays < 1) {
            $this->error('Retention days must be at least 1.');
            return self::FAILURE;
        }

        $this->info("Cleaning backups older than {$retentionDays} days...");
        
        if ($strategyFilter) {
            $this->line("Filtering by strategy: {$strategyFilter}");
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be actually deleted');
        }

        $this->newLine();

        try {
            if ($dryRun) {
                return $this->performDryRun($retentionDays, $strategyFilter);
            }

            return $this->performCleanup($retentionDays, $strategyFilter, $force);
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Perform dry run to show what would be cleaned.
     */
    protected function performDryRun(int $retentionDays, ?string $strategyFilter): int
    {
        $backups = $this->getBackupsToClean($retentionDays, $strategyFilter);

        if (empty($backups)) {
            $this->info('No backups found that match the cleanup criteria.');
            return self::SUCCESS;
        }

        $this->info('Backups that would be cleaned:');
        $this->newLine();

        $headers = ['Operation ID', 'Created At', 'Age (days)', 'Strategies', 'Size'];
        $rows = [];
        $totalSize = 0;

        foreach ($backups as $backup) {
            $createdAt = \Carbon\Carbon::parse($backup['created_at']);
            $age = $createdAt->diffInDays(now());
            $strategies = collect($backup['strategies'])->keys()->implode(', ');
            $size = $this->formatBytes($backup['total_size']);
            $totalSize += $backup['total_size'];

            $rows[] = [
                $backup['operation_id'],
                $createdAt->format('Y-m-d H:i:s'),
                $age,
                $strategies,
                $size,
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->info('Total backups to clean: ' . count($backups));
        $this->info('Total space to free: ' . $this->formatBytes($totalSize));

        return self::SUCCESS;
    }

    /**
     * Perform actual cleanup.
     */
    protected function performCleanup(int $retentionDays, ?string $strategyFilter, bool $force): int
    {
        $backups = $this->getBackupsToClean($retentionDays, $strategyFilter);

        if (empty($backups)) {
            $this->info('No backups found that match the cleanup criteria.');
            return self::SUCCESS;
        }

        // Show summary
        $totalSize = array_sum(array_column($backups, 'total_size'));
        $this->info('Found ' . count($backups) . ' backup(s) to clean');
        $this->info('Total space to free: ' . $this->formatBytes($totalSize));
        $this->newLine();

        // Confirm cleanup
        if (!$force && !$this->confirmCleanup(count($backups))) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        // Perform cleanup with progress
        $progressBar = $this->output->createProgressBar(count($backups));
        $progressBar->setFormat('debug');
        $progressBar->start();

        $cleaned = 0;
        $errors = 0;

        foreach ($backups as $backup) {
            $progressBar->setMessage("Cleaning {$backup['operation_id']}...");
            
            try {
                if ($this->backupManager->removeAllBackups($backup['operation_id'])) {
                    $cleaned++;
                } else {
                    $errors++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Failed to clean {$backup['operation_id']}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        $this->info("✓ Cleaned {$cleaned} backup(s)");
        
        if ($errors > 0) {
            $this->warn("⚠ {$errors} error(s) occurred during cleanup");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get backups that should be cleaned.
     */
    protected function getBackupsToClean(int $retentionDays, ?string $strategyFilter): array
    {
        $allBackups = $this->backupManager->listAllBackups();
        $cutoffDate = now()->subDays($retentionDays);
        $toClean = [];

        foreach ($allBackups as $backup) {
            $createdAt = \Carbon\Carbon::parse($backup['created_at']);
            
            // Skip if not old enough
            if (!$createdAt->lt($cutoffDate)) {
                continue;
            }

            // Skip if strategy filter doesn't match
            if ($strategyFilter && !isset($backup['strategies'][$strategyFilter])) {
                continue;
            }

            $toClean[] = $backup;
        }

        return $toClean;
    }

    /**
     * Confirm cleanup operation.
     */
    protected function confirmCleanup(int $count): bool
    {
        $this->warn('WARNING: This operation will permanently delete backup files.');
        $this->warn('Deleted backups cannot be recovered.');
        $this->newLine();

        return $this->confirm("Are you sure you want to delete {$count} backup(s)?");
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