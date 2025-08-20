<?php

declare(strict_types=1);

namespace AutoGen\Commands\Rollback;

use AutoGen\Common\Contracts\BackupManagerInterface;
use Illuminate\Console\Command;

/**
 * Command for listing available backups.
 */
class BackupListCommand extends Command
{
    protected $signature = 'autogen:backup:list
                          {--strategy= : Filter by specific strategy}
                          {--detailed : Show detailed information}
                          {--format=table : Output format (table, json)}';

    protected $description = 'List all available AutoGen backups';

    protected BackupManagerInterface $backupManager;

    public function __construct(BackupManagerInterface $backupManager)
    {
        parent::__construct();
        $this->backupManager = $backupManager;
    }

    public function handle(): int
    {
        $backups = $this->backupManager->listAllBackups();

        if (empty($backups)) {
            $this->info('No backups available.');
            return self::SUCCESS;
        }

        // Filter by strategy if specified
        if ($strategyFilter = $this->option('strategy')) {
            $backups = array_filter($backups, function ($backup) use ($strategyFilter) {
                return isset($backup['strategies'][$strategyFilter]);
            });
        }

        if (empty($backups)) {
            $this->info("No backups found for strategy: {$strategyFilter}");
            return self::SUCCESS;
        }

        $format = $this->option('format');
        
        switch ($format) {
            case 'json':
                return $this->outputJson($backups);
            case 'table':
            default:
                return $this->outputTable($backups);
        }
    }

    /**
     * Output backups as table.
     */
    protected function outputTable(array $backups): int
    {
        $detailed = $this->option('detailed');

        if ($detailed) {
            return $this->outputDetailedTable($backups);
        }

        $headers = ['Operation ID', 'Created At', 'Strategies', 'Size', 'Restorable', 'Context'];
        $rows = [];

        foreach ($backups as $backup) {
            $strategies = collect($backup['strategies'])->keys()->implode(', ');
            $canRestore = $this->backupManager->canRestoreOperation($backup['operation_id']) ? '✓' : '✗';
            $size = $this->formatBytes($backup['total_size']);
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
        $this->info('Total backups: ' . count($backups));
        $this->info('Total size: ' . $this->formatBytes(array_sum(array_column($backups, 'total_size'))));

        return self::SUCCESS;
    }

    /**
     * Output detailed table with strategy information.
     */
    protected function outputDetailedTable(array $backups): int
    {
        foreach ($backups as $backup) {
            $this->info("Operation: {$backup['operation_id']}");
            $this->line("Created: " . \Carbon\Carbon::parse($backup['created_at'])->format('Y-m-d H:i:s'));
            $this->line("Context: " . $this->formatContext($backup['context']));
            $this->line("Restorable: " . ($this->backupManager->canRestoreOperation($backup['operation_id']) ? 'Yes' : 'No'));
            
            if (!empty($backup['errors'])) {
                $this->line("Errors: " . count($backup['errors']) . " occurred during backup");
            }

            $this->newLine();
            $this->line("Strategies:");

            $strategyHeaders = ['Strategy', 'Backup ID', 'Size', 'Restorable'];
            $strategyRows = [];

            foreach ($backup['strategies'] as $strategyName => $strategyInfo) {
                $size = $this->formatBytes($strategyInfo['size']);
                $restorable = $strategyInfo['can_restore'] ? '✓' : '✗';

                $strategyRows[] = [
                    $strategyName,
                    substr($strategyInfo['backup_id'], 0, 20) . '...',
                    $size,
                    $restorable,
                ];
            }

            $this->table($strategyHeaders, $strategyRows);
            $this->newLine();
            $this->line(str_repeat('-', 80));
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Output backups as JSON.
     */
    protected function outputJson(array $backups): int
    {
        // Prepare data for JSON output
        $jsonData = [];

        foreach ($backups as $backup) {
            $jsonBackup = [
                'operation_id' => $backup['operation_id'],
                'created_at' => $backup['created_at'],
                'context' => $backup['context'],
                'total_size' => $backup['total_size'],
                'can_restore' => $this->backupManager->canRestoreOperation($backup['operation_id']),
                'strategies' => [],
                'errors' => $backup['errors'] ?? [],
            ];

            foreach ($backup['strategies'] as $strategyName => $strategyInfo) {
                $jsonBackup['strategies'][$strategyName] = [
                    'backup_id' => $strategyInfo['backup_id'],
                    'size' => $strategyInfo['size'],
                    'can_restore' => $strategyInfo['can_restore'],
                    'metadata' => $strategyInfo['metadata'],
                ];
            }

            $jsonData[] = $jsonBackup;
        }

        $this->line(json_encode([
            'total_backups' => count($jsonData),
            'total_size' => array_sum(array_column($backups, 'total_size')),
            'backups' => $jsonData,
        ], JSON_PRETTY_PRINT));

        return self::SUCCESS;
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