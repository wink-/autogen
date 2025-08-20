<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Rollback;

use AutoGen\Common\Rollback\BackupStrategy;
use AutoGen\Common\Exceptions\RollbackException;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Test suite for BackupStrategy.
 */
class BackupStrategyTest extends OrchestraTestCase
{
    use RefreshDatabase;

    protected BackupStrategy $strategy;
    protected string $testDisk = 'testing';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure test disk
        config(['filesystems.disks.testing' => [
            'driver' => 'local',
            'root' => storage_path('testing'),
        ]]);

        $this->strategy = new BackupStrategy($this->testDisk, 'test_backups');
        
        // Clean up any existing test data
        Storage::disk($this->testDisk)->deleteDirectory('test_backups');
    }

    protected function tearDown(): void
    {
        // Clean up test data
        Storage::disk($this->testDisk)->deleteDirectory('test_backups');
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_backup(): void
    {
        $operationId = 'test_operation_001';
        $context = [
            'model_name' => 'TestModel',
            'table_name' => 'test_models',
            'files_to_backup' => [],
        ];

        $backupId = $this->strategy->createBackup($operationId, $context);

        $this->assertNotEmpty($backupId);
        $this->assertTrue($this->strategy->hasBackup($backupId));
    }

    /** @test */
    public function it_stores_metadata_correctly(): void
    {
        $operationId = 'test_operation_002';
        $context = [
            'model_name' => 'TestModel',
            'table_name' => 'test_models',
        ];

        $backupId = $this->strategy->createBackup($operationId, $context);
        $metadata = $this->strategy->getBackupMetadata($backupId);

        $this->assertIsArray($metadata);
        $this->assertEquals($operationId, $metadata['operation_id']);
        $this->assertEquals($context, $metadata['context']);
        $this->assertEquals('file_backup', $metadata['strategy']);
        $this->assertArrayHasKey('created_at', $metadata);
    }

    /** @test */
    public function it_can_list_backups(): void
    {
        // Create multiple backups
        $backupId1 = $this->strategy->createBackup('op1', ['test' => 'data1']);
        $backupId2 = $this->strategy->createBackup('op2', ['test' => 'data2']);

        $backups = $this->strategy->listBackups();

        $this->assertCount(2, $backups);
        $this->assertContains($backupId1, array_column($backups, 'id'));
        $this->assertContains($backupId2, array_column($backups, 'id'));
    }

    /** @test */
    public function it_can_remove_backup(): void
    {
        $backupId = $this->strategy->createBackup('test_op', ['test' => 'data']);
        
        $this->assertTrue($this->strategy->hasBackup($backupId));
        
        $removed = $this->strategy->removeBackup($backupId);
        
        $this->assertTrue($removed);
        $this->assertFalse($this->strategy->hasBackup($backupId));
    }

    /** @test */
    public function it_can_cleanup_old_backups(): void
    {
        // Create backup with old timestamp
        $backupId = $this->strategy->createBackup('old_op', ['test' => 'old_data']);
        
        // Manually modify metadata to make it appear old
        $metadataPath = "test_backups/{$backupId}/metadata.json";
        $metadata = json_decode(Storage::disk($this->testDisk)->get($metadataPath), true);
        $metadata['created_at'] = now()->subDays(40)->toISOString();
        Storage::disk($this->testDisk)->put($metadataPath, json_encode($metadata));

        $cleaned = $this->strategy->cleanupBackups(30);
        
        $this->assertEquals(1, $cleaned);
        $this->assertFalse($this->strategy->hasBackup($backupId));
    }

    /** @test */
    public function it_validates_backup_restoration_possibility(): void
    {
        $backupId = $this->strategy->createBackup('valid_op', ['test' => 'data']);
        
        $this->assertTrue($this->strategy->canRestore($backupId));
        $this->assertFalse($this->strategy->canRestore('nonexistent_backup'));
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_backup_restore(): void
    {
        $this->expectException(RollbackException::class);
        $this->expectExceptionMessage("Backup 'nonexistent' not found");
        
        $this->strategy->restore('nonexistent');
    }

    /** @test */
    public function it_returns_correct_strategy_name(): void
    {
        $this->assertEquals('file_backup', $this->strategy->getName());
    }

    /** @test */
    public function it_handles_empty_file_list_gracefully(): void
    {
        $context = [
            'model_name' => 'TestModel',
            'files_to_backup' => [],
        ];

        $backupId = $this->strategy->createBackup('empty_files', $context);
        $metadata = $this->strategy->getBackupMetadata($backupId);

        $this->assertIsArray($metadata['files']);
        $this->assertEmpty($metadata['files']);
    }

    /** @test */
    public function it_generates_unique_backup_ids(): void
    {
        $backupId1 = $this->strategy->createBackup('op1', ['test' => 'data']);
        $backupId2 = $this->strategy->createBackup('op1', ['test' => 'data']); // Same operation

        $this->assertNotEquals($backupId1, $backupId2);
    }

    /** @test */
    public function it_sorts_backups_by_creation_time(): void
    {
        // Create backups with slight delay to ensure different timestamps
        $backupId1 = $this->strategy->createBackup('first', ['test' => 'data1']);
        usleep(1000); // 1ms delay
        $backupId2 = $this->strategy->createBackup('second', ['test' => 'data2']);

        $backups = $this->strategy->listBackups();

        // Should be sorted by created_at DESC (newest first)
        $this->assertEquals($backupId2, $backups[0]['id']);
        $this->assertEquals($backupId1, $backups[1]['id']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \AutoGen\AutoGenServiceProvider::class,
        ];
    }
}