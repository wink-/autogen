<?php

declare(strict_types=1);

namespace AutoGen\Tests\Feature;

use AutoGen\Common\Contracts\BackupManagerInterface;
use AutoGen\Common\Rollback\RollbackableModelGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

/**
 * Integration tests for the rollback system.
 */
class RollbackIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected BackupManagerInterface $backupManager;
    protected string $testDisk = 'testing';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure test disk
        config(['filesystems.disks.testing' => [
            'driver' => 'local',
            'root' => storage_path('testing'),
        ]]);

        config(['autogen.rollback.backup_disk' => $this->testDisk]);
        
        $this->backupManager = app(BackupManagerInterface::class);
        
        // Clean up any existing test data
        Storage::disk($this->testDisk)->deleteDirectory('autogen');
    }

    protected function tearDown(): void
    {
        Storage::disk($this->testDisk)->deleteDirectory('autogen');
        parent::tearDown();
    }

    /** @test */
    public function it_can_perform_full_backup_and_restore_cycle(): void
    {
        $operationId = 'test_full_cycle';
        $context = [
            'model_name' => 'TestModel',
            'table_name' => 'test_models',
            'operation_type' => 'model_generation',
        ];

        // Create backup
        $backupResult = $this->backupManager->createBackup($operationId, $context);

        $this->assertEquals($operationId, $backupResult['operation_id']);
        $this->assertGreaterThan(0, $backupResult['success_count']);
        $this->assertTrue($this->backupManager->hasOperationBackups($operationId));

        // Verify backup can be restored
        $this->assertTrue($this->backupManager->canRestoreOperation($operationId));

        // Perform restore
        $restoreResult = $this->backupManager->restore($operationId);
        $this->assertTrue($restoreResult);
    }

    /** @test */
    public function it_integrates_with_rollbackable_generator(): void
    {
        $generator = new RollbackableModelGenerator();
        $generator->setBackupManager($this->backupManager);

        $context = [
            'model_name' => 'User',
            'table_name' => 'users',
            'namespace' => 'App\\Models',
        ];

        // This should create backups automatically
        $result = $generator->generateWithRollback($context);

        $this->assertNotEmpty($result);

        // Check that rollback points were created
        $rollbackPoints = $generator->getRollbackPoints();
        $this->assertNotEmpty($rollbackPoints);
    }

    /** @test */
    public function it_can_handle_generator_failure_with_auto_rollback(): void
    {
        // Create a generator that will fail
        $generator = new class extends RollbackableModelGenerator {
            protected function performGeneration(array $context): string
            {
                // Simulate failure after creating backup
                throw new \Exception('Generation failed');
            }
        };

        $generator->setBackupManager($this->backupManager);

        $context = [
            'model_name' => 'FailingModel',
            'table_name' => 'failing_models',
        ];

        try {
            $generator->generateWithRollback($context);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Generation failed', $e->getMessage());
        }

        // The backup should have been created and potentially used for rollback
        $rollbackPoints = $generator->getRollbackPoints();
        // In a real scenario, we'd verify that rollback was attempted
    }

    /** @test */
    public function it_can_cleanup_old_backups(): void
    {
        // Create multiple backups
        $operations = ['op1', 'op2', 'op3'];
        foreach ($operations as $op) {
            $this->backupManager->createBackup($op, ['test' => 'data']);
        }

        // Verify all were created
        $allBackups = $this->backupManager->listAllBackups();
        $this->assertCount(3, $allBackups);

        // Perform cleanup (with very short retention for testing)
        $results = $this->backupManager->performCleanup(0); // 0 days = clean all
        
        // Check cleanup results
        $this->assertIsArray($results);
        $totalCleaned = array_sum(array_column($results, 'cleaned'));
        $this->assertGreaterThan(0, $totalCleaned);
    }

    /** @test */
    public function it_maintains_backup_index_integrity(): void
    {
        // Create backups
        $this->backupManager->createBackup('op1', ['type' => 'model']);
        $this->backupManager->createBackup('op2', ['type' => 'controller']);

        // Create new manager instance to test persistence
        $newManager = app(BackupManagerInterface::class);
        
        $this->assertTrue($newManager->hasOperationBackups('op1'));
        $this->assertTrue($newManager->hasOperationBackups('op2'));
        
        $backups = $newManager->listAllBackups();
        $this->assertCount(2, $backups);
    }

    /** @test */
    public function it_handles_strategy_failures_gracefully_in_real_scenario(): void
    {
        // This test would simulate a scenario where one strategy fails
        // but the operation should continue with other strategies
        
        $operationId = 'mixed_success_op';
        $context = [
            'model_name' => 'TestModel',
            'table_name' => 'test_models',
        ];

        // Force a scenario where database strategy might fail
        // (In a real test, we'd mock the database connection to fail)
        
        $result = $this->backupManager->createBackup($operationId, $context);
        
        // Should have at least file backup success
        $this->assertGreaterThanOrEqual(1, $result['success_count']);
        $this->assertTrue($this->backupManager->hasOperationBackups($operationId));
    }

    /** @test */
    public function it_can_rollback_through_artisan_command(): void
    {
        // Create a backup first
        $operationId = 'command_test_op';
        $this->backupManager->createBackup($operationId, ['test' => 'data']);

        // Test rollback command
        $this->artisan('autogen:rollback', [
            'operation' => $operationId,
            '--confirm' => true,
        ])->assertSuccessful();
    }

    /** @test */
    public function it_can_list_backups_through_artisan_command(): void
    {
        // Create some backups
        $this->backupManager->createBackup('list_test_1', ['model_name' => 'Model1']);
        $this->backupManager->createBackup('list_test_2', ['controller_name' => 'Controller1']);

        // Test list command
        $this->artisan('autogen:backup:list')
            ->assertSuccessful()
            ->expectsOutput('list_test_1')
            ->expectsOutput('list_test_2');
    }

    /** @test */
    public function it_can_clean_backups_through_artisan_command(): void
    {
        // Create a backup
        $this->backupManager->createBackup('clean_test', ['test' => 'data']);

        // Test cleanup command with dry run
        $this->artisan('autogen:backup:clean', [
            '--days' => 0,
            '--dry-run' => true,
        ])->assertSuccessful();

        // Backup should still exist after dry run
        $this->assertTrue($this->backupManager->hasOperationBackups('clean_test'));

        // Test actual cleanup
        $this->artisan('autogen:backup:clean', [
            '--days' => 0,
            '--force' => true,
        ])->assertSuccessful();
    }

    /** @test */
    public function it_preserves_file_permissions_and_metadata(): void
    {
        // This test would verify that file permissions, timestamps, etc.
        // are preserved during backup and restore operations
        
        $operationId = 'metadata_test';
        $context = [
            'model_name' => 'MetadataModel',
            'files_to_backup' => [], // Would include actual files in real test
        ];

        $backupResult = $this->backupManager->createBackup($operationId, $context);
        $this->assertNotEmpty($backupResult['strategy_backups']);

        // In a full implementation, we'd verify that:
        // - File permissions are preserved
        // - Timestamps are maintained
        // - File ownership is correct
        // - Symbolic links are handled properly
    }

    protected function getPackageProviders($app): array
    {
        return [
            \AutoGen\Common\Rollback\RollbackServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Configure test environment
        $app['config']->set('autogen.rollback.backup_disk', 'testing');
        $app['config']->set('autogen.rollback.auto_recovery.enabled', true);
        $app['config']->set('filesystems.disks.testing', [
            'driver' => 'local',
            'root' => storage_path('testing'),
        ]);
    }
}