<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Rollback;

use AutoGen\Common\Rollback\ErrorRecoveryManager;
use AutoGen\Common\Contracts\BackupManagerInterface;
use AutoGen\Common\Exceptions\RollbackException;
use Orchestra\Testbench\TestCase;
use Mockery;

/**
 * Test suite for ErrorRecoveryManager.
 */
class ErrorRecoveryManagerTest extends TestCase
{
    protected ErrorRecoveryManager $manager;
    protected $backupManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->backupManager = Mockery::mock(BackupManagerInterface::class);
        $this->manager = new ErrorRecoveryManager($this->backupManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_register_recovery_strategies(): void
    {
        $strategy = function ($error, $operationId, $context) {
            return true;
        };

        $this->manager->registerRecoveryStrategy('custom_error', $strategy);

        // Test that the strategy is registered by simulating an error
        $error = new \Exception('Test error');
        $this->backupManager->shouldReceive('canRestoreOperation')->once()->andReturn(true);
        
        $result = $this->manager->handleError($error, 'test_op');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_register_error_handlers(): void
    {
        $handlerCalled = false;
        $handler = function ($error, $operationId, $context) use (&$handlerCalled) {
            $handlerCalled = true;
        };

        $this->manager->registerErrorHandler('generation_error', $handler);

        $error = new \Exception('Generation failed');
        $this->backupManager->shouldReceive('canRestoreOperation')->once()->andReturn(false);

        $this->manager->handleError($error, 'test_op');
        $this->assertTrue($handlerCalled);
    }

    /** @test */
    public function it_classifies_filesystem_errors_correctly(): void
    {
        $permissionError = new \Exception('Permission denied');
        $this->backupManager->shouldReceive('canRestoreOperation')->once()->andReturn(false);

        $result = $this->manager->handleError($permissionError, 'test_op');
        $this->assertFalse($result); // Should not auto-recover permission errors
    }

    /** @test */
    public function it_classifies_database_errors_correctly(): void
    {
        $connectionError = new \Exception('Connection refused');
        $this->backupManager->shouldReceive('canRestoreOperation')->once()->andReturn(false);

        $result = $this->manager->handleError($connectionError, 'test_op');
        $this->assertFalse($result); // Should not auto-recover connection errors
    }

    /** @test */
    public function it_performs_fallback_recovery_when_no_strategy_matches(): void
    {
        $error = new \Exception('Unknown error type');
        
        $this->backupManager->shouldReceive('canRestoreOperation')
            ->once()
            ->with('test_op')
            ->andReturn(true);
        
        $this->backupManager->shouldReceive('restore')
            ->once()
            ->with('test_op')
            ->andReturn(true);

        $result = $this->manager->handleError($error, 'test_op');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_no_backup_available(): void
    {
        $error = new \Exception('Some error');
        
        $this->backupManager->shouldReceive('canRestoreOperation')
            ->once()
            ->with('test_op')
            ->andReturn(false);

        $result = $this->manager->handleError($error, 'test_op');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_rollback_exceptions_gracefully(): void
    {
        $error = new \Exception('Generation error');
        
        $this->backupManager->shouldReceive('canRestoreOperation')
            ->once()
            ->with('test_op')
            ->andReturn(true);
        
        $this->backupManager->shouldReceive('restore')
            ->once()
            ->with('test_op')
            ->andThrow(new RollbackException('Restore failed'));

        $result = $this->manager->handleError($error, 'test_op');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_check_if_recovery_is_possible(): void
    {
        $this->backupManager->shouldReceive('canRestoreOperation')
            ->once()
            ->with('test_op')
            ->andReturn(true);

        $canRecover = $this->manager->canRecover('test_op');
        $this->assertTrue($canRecover);
    }

    /** @test */
    public function it_validates_recovery_results(): void
    {
        $operationId = 'test_op';
        
        $this->backupManager->shouldReceive('getOperationBackup')
            ->once()
            ->with($operationId)
            ->andReturn([
                'context' => [
                    'model_name' => 'TestModel',
                    'table_name' => 'test_models',
                ],
            ]);

        $validation = $this->manager->validateRecovery($operationId);
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('checks', $validation);
        $this->assertArrayHasKey('errors', $validation);
    }

    /** @test */
    public function it_returns_recovery_statistics(): void
    {
        $this->backupManager->shouldReceive('listAllBackups')
            ->once()
            ->andReturn([
                [
                    'operation_id' => 'op1',
                    'context' => ['error_type' => 'filesystem_error'],
                ],
                [
                    'operation_id' => 'op2',
                    'context' => ['error_type' => 'database_error'],
                ],
            ]);

        $this->backupManager->shouldReceive('canRestoreOperation')
            ->twice()
            ->andReturn(true, false);

        $stats = $this->manager->getRecoveryStats();

        $this->assertEquals(2, $stats['total_operations']);
        $this->assertEquals(1, $stats['recoverable_operations']);
        $this->assertEquals(50, $stats['success_rate']);
        $this->assertArrayHasKey('filesystem_error', $stats['error_types']);
        $this->assertArrayHasKey('database_error', $stats['error_types']);
    }

    /** @test */
    public function it_uses_custom_recovery_strategy_over_default(): void
    {
        $customStrategyCalled = false;
        $customStrategy = function ($error, $operationId, $context) use (&$customStrategyCalled) {
            $customStrategyCalled = true;
            return true;
        };

        $this->manager->registerRecoveryStrategy('generation_error', $customStrategy);

        $error = new \Exception('Generation failed');
        $this->backupManager->shouldReceive('canRestoreOperation')->once()->andReturn(true);

        $result = $this->manager->handleError($error, 'test_op');
        
        $this->assertTrue($result);
        $this->assertTrue($customStrategyCalled);
    }

    /** @test */
    public function it_handles_recovery_strategy_exceptions(): void
    {
        $faultyStrategy = function ($error, $operationId, $context) {
            throw new \Exception('Strategy failed');
        };

        $this->manager->registerRecoveryStrategy('generation_error', $faultyStrategy);

        $error = new \Exception('Generation failed');
        $this->backupManager->shouldReceive('canRestoreOperation')
            ->twice() // Once for strategy, once for fallback
            ->andReturn(true);
        
        $this->backupManager->shouldReceive('restore')
            ->once()
            ->with('test_op')
            ->andReturn(true);

        $result = $this->manager->handleError($error, 'test_op');
        $this->assertTrue($result); // Should fallback to rollback
    }

    /** @test */
    public function it_classifies_template_errors_correctly(): void
    {
        $templateError = new \Exception('Template not found');
        
        // Template errors should use the template_error strategy (which returns true by default)
        $result = $this->manager->handleError($templateError, 'test_op');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_file_restoration(): void
    {
        $operationId = 'test_op';
        
        $this->backupManager->shouldReceive('getOperationBackup')
            ->once()
            ->with($operationId)
            ->andReturn([
                'context' => [
                    'model_name' => 'NonExistentModel',
                ],
            ]);

        $validation = $this->manager->validateRecovery($operationId);
        
        // Should detect missing model file
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            \AutoGen\AutoGenServiceProvider::class,
        ];
    }
}