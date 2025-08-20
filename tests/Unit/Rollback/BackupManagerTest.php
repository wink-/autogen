<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Rollback;

use AutoGen\Common\Rollback\BackupManager;
use AutoGen\Common\Rollback\BackupStrategy;
use AutoGen\Common\Rollback\DatabaseRollbackStrategy;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use Mockery;

/**
 * Test suite for BackupManager.
 */
class BackupManagerTest extends TestCase
{
    protected BackupManager $manager;
    protected string $testDisk = 'testing';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure test disk
        config(['filesystems.disks.testing' => [
            'driver' => 'local',
            'root' => storage_path('testing'),
        ]]);

        $this->manager = new BackupManager($this->testDisk, 'test_backup_index.json');
        
        // Clean up any existing test data
        Storage::disk($this->testDisk)->deleteDirectory('test_backups');
        Storage::disk($this->testDisk)->delete('test_backup_index.json');
    }

    protected function tearDown(): void
    {
        Storage::disk($this->testDisk)->deleteDirectory('test_backups');
        Storage::disk($this->testDisk)->delete('test_backup_index.json');
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_register_and_retrieve_strategies(): void
    {
        $strategy = Mockery::mock(BackupStrategy::class);
        $strategy->shouldReceive('getName')->andReturn('test_strategy');

        $this->manager->registerStrategy('test', $strategy);

        $retrievedStrategy = $this->manager->getStrategy('test');
        $this->assertSame($strategy, $retrievedStrategy);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_strategy(): void
    {
        $strategy = $this->manager->getStrategy('nonexistent');
        $this->assertNull($strategy);
    }

    /** @test */
    public function it_can_create_backup_with_multiple_strategies(): void
    {
        // Mock strategies
        $fileStrategy = Mockery::mock(BackupStrategy::class);
        $fileStrategy->shouldReceive('getName')->andReturn('file_backup');
        $fileStrategy->shouldReceive('createBackup')->once()->andReturn('file_backup_123');

        $dbStrategy = Mockery::mock(DatabaseRollbackStrategy::class);
        $dbStrategy->shouldReceive('getName')->andReturn('database_rollback');
        $dbStrategy->shouldReceive('createBackup')->once()->andReturn('db_backup_456');

        $this->manager->registerStrategy('file', $fileStrategy);
        $this->manager->registerStrategy('database', $dbStrategy);

        $result = $this->manager->createBackup('test_operation', ['test' => 'context'], ['file', 'database']);

        $this->assertEquals('test_operation', $result['operation_id']);
        $this->assertEquals(2, $result['success_count']);
        $this->assertEquals(0, $result['error_count']);
        $this->assertArrayHasKey('file', $result['strategy_backups']);
        $this->assertArrayHasKey('database', $result['strategy_backups']);
    }

    /** @test */
    public function it_handles_strategy_failures_gracefully(): void
    {
        // Mock strategies - one succeeds, one fails
        $goodStrategy = Mockery::mock(BackupStrategy::class);
        $goodStrategy->shouldReceive('getName')->andReturn('good_strategy');
        $goodStrategy->shouldReceive('createBackup')->once()->andReturn('backup_123');

        $badStrategy = Mockery::mock(BackupStrategy::class);
        $badStrategy->shouldReceive('getName')->andReturn('bad_strategy');
        $badStrategy->shouldReceive('createBackup')->once()->andThrow(new \Exception('Backup failed'));

        $this->manager->registerStrategy('good', $goodStrategy);
        $this->manager->registerStrategy('bad', $badStrategy);

        $result = $this->manager->createBackup('test_operation', ['test' => 'context'], ['good', 'bad']);

        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(1, $result['error_count']);
        $this->assertArrayHasKey('good', $result['strategy_backups']);
        $this->assertArrayHasKey('bad', $result['errors']);
    }

    /** @test */
    public function it_throws_exception_for_critical_strategy_failure(): void
    {
        // Mock critical strategy that fails
        $criticalStrategy = Mockery::mock(BackupStrategy::class);
        $criticalStrategy->shouldReceive('getName')->andReturn('file_backup');
        $criticalStrategy->shouldReceive('createBackup')->once()->andThrow(new \Exception('Critical failure'));

        $this->manager->registerStrategy('file_backup', $criticalStrategy);

        $this->expectException(RollbackException::class);
        $this->expectExceptionMessage('Critical strategy');

        $this->manager->createBackup('test_operation', ['test' => 'context'], ['file_backup']);
    }

    /** @test */
    public function it_can_restore_operation(): void
    {
        // First create a backup
        $strategy = Mockery::mock(BackupStrategy::class);
        $strategy->shouldReceive('getName')->andReturn('test_strategy');
        $strategy->shouldReceive('createBackup')->once()->andReturn('backup_123');
        $strategy->shouldReceive('restore')->once()->with('backup_123')->andReturn(true);

        $this->manager->registerStrategy('test', $strategy);
        $this->manager->createBackup('test_operation', ['test' => 'context'], ['test']);

        $result = $this->manager->restore('test_operation');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_operation_restore(): void
    {
        $this->expectException(RollbackException::class);
        $this->expectExceptionMessage("Backup 'nonexistent' not found");

        $this->manager->restore('nonexistent');
    }

    /** @test */
    public function it_can_list_all_backups(): void
    {
        // Create multiple operations
        $strategy = Mockery::mock(BackupStrategy::class);
        $strategy->shouldReceive('getName')->andReturn('test_strategy');
        $strategy->shouldReceive('createBackup')->times(2)->andReturn('backup_1', 'backup_2');
        $strategy->shouldReceive('hasBackup')->times(2)->andReturn(true, true);
        $strategy->shouldReceive('getBackupMetadata')->times(2)->andReturn(
            ['created_at' => now()->toISOString()],
            ['created_at' => now()->subHour()->toISOString()]
        );

        $this->manager->registerStrategy('test', $strategy);
        $this->manager->createBackup('operation_1', ['test' => 'context1'], ['test']);
        $this->manager->createBackup('operation_2', ['test' => 'context2'], ['test']);

        $backups = $this->manager->listAllBackups();

        $this->assertCount(2, $backups);
        $this->assertEquals('operation_1', $backups[0]['operation_id']); // Newest first
        $this->assertEquals('operation_2', $backups[1]['operation_id']);
    }

    /** @test */
    public function it_can_remove_all_backups_for_operation(): void
    {
        $strategy = Mockery::mock(BackupStrategy::class);
        $strategy->shouldReceive('getName')->andReturn('test_strategy');
        $strategy->shouldReceive('createBackup')->once()->andReturn('backup_123');
        $strategy->shouldReceive('removeBackup')->once()->with('backup_123')->andReturn(true);

        $this->manager->registerStrategy('test', $strategy);
        $this->manager->createBackup('test_operation', ['test' => 'context'], ['test']);

        $result = $this->manager->removeAllBackups('test_operation');
        $this->assertTrue($result);
        $this->assertFalse($this->manager->hasOperationBackups('test_operation'));
    }

    /** @test */
    public function it_can_perform_cleanup(): void
    {
        $strategy = Mockery::mock(BackupStrategy::class);
        $strategy->shouldReceive('getName')->andReturn('test_strategy');
        $strategy->shouldReceive('cleanupBackups')->once()->with(30)->andReturn(2);

        $this->manager->registerStrategy('test', $strategy);

        $results = $this->manager->performCleanup(30);

        $this->assertArrayHasKey('test', $results);
        $this->assertEquals(2, $results['test']['cleaned']);
        $this->assertNull($results['test']['error']);
    }

    /** @test */
    public function it_handles_cleanup_errors(): void
    {
        $strategy = Mockery::mock(BackupStrategy::class);
        $strategy->shouldReceive('getName')->andReturn('test_strategy');
        $strategy->shouldReceive('cleanupBackups')->once()->andThrow(new \Exception('Cleanup failed'));

        $this->manager->registerStrategy('test', $strategy);

        $results = $this->manager->performCleanup(30);

        $this->assertArrayHasKey('test', $results);
        $this->assertEquals(0, $results['test']['cleaned']);
        $this->assertEquals('Cleanup failed', $results['test']['error']);
    }

    /** @test */
    public function it_can_check_if_operation_can_be_restored(): void
    {
        $strategy = Mockery::mock(BackupStrategy::class);
        $strategy->shouldReceive('getName')->andReturn('test_strategy');
        $strategy->shouldReceive('createBackup')->once()->andReturn('backup_123');
        $strategy->shouldReceive('canRestore')->once()->with('backup_123')->andReturn(true);

        $this->manager->registerStrategy('test', $strategy);
        $this->manager->createBackup('test_operation', ['test' => 'context'], ['test']);

        $canRestore = $this->manager->canRestoreOperation('test_operation');
        $this->assertTrue($canRestore);
    }

    /** @test */
    public function it_returns_false_for_nonexistent_operation_restore_check(): void
    {
        $canRestore = $this->manager->canRestoreOperation('nonexistent');
        $this->assertFalse($canRestore);
    }

    /** @test */
    public function it_persists_and_loads_backup_index(): void
    {
        $strategy = Mockery::mock(BackupStrategy::class);
        $strategy->shouldReceive('getName')->andReturn('test_strategy');
        $strategy->shouldReceive('createBackup')->once()->andReturn('backup_123');

        $this->manager->registerStrategy('test', $strategy);
        $this->manager->createBackup('test_operation', ['test' => 'context'], ['test']);

        // Create new manager instance to test persistence
        $newManager = new BackupManager($this->testDisk, 'test_backup_index.json');
        
        $this->assertTrue($newManager->hasOperationBackups('test_operation'));
    }

    protected function getPackageProviders($app): array
    {
        return [
            \AutoGen\AutoGenServiceProvider::class,
        ];
    }
}