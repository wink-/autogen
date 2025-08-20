<?php

declare(strict_types=1);

/**
 * AutoGen Rollback System Examples
 * 
 * This file contains practical examples of using the AutoGen rollback system
 * in various scenarios. These examples demonstrate best practices and common
 * usage patterns.
 */

use AutoGen\Common\Contracts\BackupManagerInterface;
use AutoGen\Common\Rollback\ErrorRecoveryManager;
use AutoGen\Common\Rollback\RollbackableModelGenerator;
use AutoGen\Common\Traits\HasRollbackSupport;

// Example 1: Basic Model Generation with Automatic Rollback
function example1_basic_rollback_generation(): void
{
    echo "=== Example 1: Basic Model Generation with Rollback ===\n";
    
    $generator = new RollbackableModelGenerator();
    
    $context = [
        'model_name' => 'Product',
        'table_name' => 'products',
        'namespace' => 'App\\Models',
        'generate_factory' => true,
        'enhanced_model' => true,
    ];

    try {
        echo "Starting model generation for Product...\n";
        $result = $generator->generateWithRollback($context);
        echo "✓ Generation successful!\n";
        echo "Generated content preview: " . substr($result, 0, 100) . "...\n";
    } catch (\Exception $e) {
        echo "✗ Generation failed: " . $e->getMessage() . "\n";
        echo "Automatic rollback was attempted.\n";
    }
}

// Example 2: Manual Backup Management
function example2_manual_backup_management(): void
{
    echo "\n=== Example 2: Manual Backup Management ===\n";
    
    $backupManager = app(BackupManagerInterface::class);
    
    // Create a manual backup before making changes
    $operationId = 'manual_refactor_' . time();
    $context = [
        'description' => 'Before refactoring User model and controller',
        'files_to_backup' => [
            app_path('Models/User.php'),
            app_path('Http/Controllers/UserController.php'),
            database_path('migrations/2014_10_12_000000_create_users_table.php'),
        ],
        'operation_type' => 'manual_backup',
    ];

    echo "Creating manual backup...\n";
    $backupResult = $backupManager->createBackup($operationId, $context);
    
    echo "Backup created successfully!\n";
    echo "- Operation ID: {$backupResult['operation_id']}\n";
    echo "- Strategies used: " . implode(', ', array_keys($backupResult['strategy_backups'])) . "\n";
    echo "- Success count: {$backupResult['success_count']}\n";
    
    if ($backupResult['error_count'] > 0) {
        echo "- Errors encountered: {$backupResult['error_count']}\n";
        foreach ($backupResult['errors'] as $strategy => $error) {
            echo "  - {$strategy}: {$error}\n";
        }
    }
    
    // Simulate some changes here...
    echo "Performing refactoring operations...\n";
    
    // Check if we can restore
    $canRestore = $backupManager->canRestoreOperation($operationId);
    echo "Can restore: " . ($canRestore ? 'Yes' : 'No') . "\n";
    
    // Restore if needed (commented out for example)
    // $restoreResult = $backupManager->restore($operationId);
    // echo "Restore result: " . ($restoreResult ? 'Success' : 'Failed') . "\n";
}

// Example 3: Custom Generator with Rollback Points
class CustomCrudGenerator
{
    use HasRollbackSupport;
    
    protected array $rollbackStrategies = ['file_backup', 'database_rollback'];
    
    public function generateCompleteCrud(array $context): string
    {
        return $this->generateWithRollback($context);
    }
    
    protected function performGeneration(array $context): string
    {
        $modelName = $context['model_name'];
        $results = [];
        
        try {
            // Step 1: Generate Model
            echo "Step 1: Generating model {$modelName}...\n";
            $rollbackPoint1 = $this->createRollbackPoint('before_model_generation', $context);
            
            $modelResult = $this->executeWithRollbackProtection(function () use ($context) {
                return $this->generateModel($context);
            }, $context);
            $results[] = "Model: {$modelResult}";
            
            // Step 2: Generate Migration
            echo "Step 2: Generating migration...\n";
            $rollbackPoint2 = $this->createRollbackPoint('before_migration_generation', $context);
            
            $migrationResult = $this->executeWithRollbackProtection(function () use ($context) {
                return $this->generateMigration($context);
            }, $context);
            $results[] = "Migration: {$migrationResult}";
            
            // Step 3: Generate Controller
            echo "Step 3: Generating controller...\n";
            $rollbackPoint3 = $this->createRollbackPoint('before_controller_generation', $context);
            
            $controllerResult = $this->executeWithRollbackProtection(function () use ($context) {
                return $this->generateController($context);
            }, $context);
            $results[] = "Controller: {$controllerResult}";
            
            // Step 4: Generate Views
            echo "Step 4: Generating views...\n";
            $rollbackPoint4 = $this->createRollbackPoint('before_views_generation', $context);
            
            $viewsResult = $this->executeWithRollbackProtection(function () use ($context) {
                return $this->generateViews($context);
            }, $context);
            $results[] = "Views: {$viewsResult}";
            
            return "CRUD generation completed:\n" . implode("\n", $results);
            
        } catch (\Exception $e) {
            echo "✗ Generation failed at step: " . $e->getMessage() . "\n";
            echo "Rollback protection activated.\n";
            throw $e;
        }
    }
    
    private function generateModel(array $context): string
    {
        // Simulate model generation
        return "Generated {$context['model_name']} model";
    }
    
    private function generateMigration(array $context): string
    {
        // Simulate migration generation
        return "Generated migration for {$context['table_name']}";
    }
    
    private function generateController(array $context): string
    {
        // Simulate controller generation
        return "Generated {$context['model_name']}Controller";
    }
    
    private function generateViews(array $context): string
    {
        // Simulate views generation
        return "Generated views for {$context['model_name']}";
    }
}

function example3_custom_generator_with_rollback_points(): void
{
    echo "\n=== Example 3: Custom Generator with Rollback Points ===\n";
    
    $generator = new CustomCrudGenerator();
    
    $context = [
        'model_name' => 'Order',
        'table_name' => 'orders',
        'controller_name' => 'OrderController',
        'with_views' => true,
        'with_api' => true,
    ];
    
    try {
        $result = $generator->generateCompleteCrud($context);
        echo "✓ Complete CRUD generation successful!\n";
        echo $result . "\n";
        
        // Show rollback points created
        $rollbackPoints = $generator->getRollbackPoints();
        echo "\nRollback points created: " . count($rollbackPoints) . "\n";
        
    } catch (\Exception $e) {
        echo "✗ CRUD generation failed: " . $e->getMessage() . "\n";
    }
}

// Example 4: Custom Error Recovery Strategy
function example4_custom_error_recovery(): void
{
    echo "\n=== Example 4: Custom Error Recovery Strategy ===\n";
    
    $errorManager = app(ErrorRecoveryManager::class);
    
    // Register custom recovery strategy for API-related errors
    $errorManager->registerRecoveryStrategy('api_error', function ($error, $operationId, $context) {
        echo "Custom API error recovery triggered\n";
        
        if (str_contains($error->getMessage(), 'rate limit exceeded')) {
            echo "Rate limit detected, waiting 60 seconds...\n";
            sleep(1); // Shortened for example
            echo "Retrying operation...\n";
            return true; // Indicate successful recovery
        }
        
        if (str_contains($error->getMessage(), 'connection timeout')) {
            echo "Connection timeout detected, switching to backup API...\n";
            // Logic to switch to backup API endpoint
            return true;
        }
        
        return false; // Fall back to default recovery
    });
    
    // Register error handler for logging
    $errorManager->registerErrorHandler('api_error', function ($error, $operationId, $context) {
        echo "Logging API error for operation {$operationId}\n";
        // Custom logging logic here
    });
    
    // Simulate an API error
    $apiError = new \Exception('API rate limit exceeded');
    $operationId = 'api_operation_' . time();
    
    $recovered = $errorManager->handleError($apiError, $operationId, ['api_endpoint' => 'users']);
    
    echo "Recovery result: " . ($recovered ? 'Success' : 'Failed') . "\n";
}

// Example 5: Batch Operations with Rollback
function example5_batch_operations_with_rollback(): void
{
    echo "\n=== Example 5: Batch Operations with Rollback ===\n";
    
    $backupManager = app(BackupManagerInterface::class);
    
    // Models to generate
    $models = [
        ['name' => 'Category', 'table' => 'categories'],
        ['name' => 'Product', 'table' => 'products'],
        ['name' => 'Review', 'table' => 'reviews'],
    ];
    
    // Create overall backup for batch operation
    $batchOperationId = 'batch_models_' . time();
    echo "Creating batch backup for operation: {$batchOperationId}\n";
    
    $batchBackup = $backupManager->createBackup($batchOperationId, [
        'operation_type' => 'batch_model_generation',
        'models' => $models,
        'batch_size' => count($models),
    ]);
    
    $successCount = 0;
    $individualOperations = [];
    
    foreach ($models as $index => $model) {
        echo "\nGenerating model {$model['name']} ({$index + 1}/" . count($models) . ")...\n";
        
        $generator = new RollbackableModelGenerator();
        
        try {
            $context = [
                'model_name' => $model['name'],
                'table_name' => $model['table'],
                'batch_operation' => $batchOperationId,
                'batch_index' => $index,
            ];
            
            $result = $generator->generateWithRollback($context);
            echo "✓ {$model['name']} generated successfully\n";
            $successCount++;
            
        } catch (\Exception $e) {
            echo "✗ {$model['name']} generation failed: " . $e->getMessage() . "\n";
            
            // Decide whether to continue or rollback entire batch
            if ($index === 0) {
                // If first model fails, rollback everything
                echo "First model failed, rolling back entire batch...\n";
                $backupManager->restore($batchOperationId);
                return;
            } else {
                // Continue with other models but log the failure
                echo "Continuing with remaining models...\n";
            }
        }
    }
    
    echo "\nBatch operation completed:\n";
    echo "- Total models: " . count($models) . "\n";
    echo "- Successful: {$successCount}\n";
    echo "- Failed: " . (count($models) - $successCount) . "\n";
    
    if ($successCount < count($models)) {
        echo "\nSome operations failed. You can:\n";
        echo "1. Keep partial results\n";
        echo "2. Rollback entire batch: php artisan autogen:rollback {$batchOperationId}\n";
    }
}

// Example 6: Backup Validation and Recovery Testing
function example6_backup_validation(): void
{
    echo "\n=== Example 6: Backup Validation and Recovery Testing ===\n";
    
    $backupManager = app(BackupManagerInterface::class);
    $errorManager = app(ErrorRecoveryManager::class);
    
    // Create test backup
    $operationId = 'validation_test_' . time();
    $context = [
        'model_name' => 'TestValidation',
        'table_name' => 'test_validations',
        'test_operation' => true,
    ];
    
    echo "Creating test backup...\n";
    $backupResult = $backupManager->createBackup($operationId, $context);
    
    // Validate the backup
    echo "Validating backup...\n";
    $validation = $errorManager->validateRecovery($operationId);
    
    echo "Validation results:\n";
    echo "- Valid: " . ($validation['valid'] ? 'Yes' : 'No') . "\n";
    
    if (!empty($validation['checks'])) {
        echo "- Checks performed:\n";
        foreach ($validation['checks'] as $checkType => $checkResult) {
            echo "  - {$checkType}: " . ($checkResult['valid'] ? 'Pass' : 'Fail') . "\n";
            if (!$checkResult['valid'] && !empty($checkResult['errors'])) {
                foreach ($checkResult['errors'] as $error) {
                    echo "    Error: {$error}\n";
                }
            }
        }
    }
    
    if (!empty($validation['errors'])) {
        echo "- Validation errors:\n";
        foreach ($validation['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
    
    // Test recovery capability
    $canRecover = $errorManager->canRecover($operationId);
    echo "- Can recover: " . ($canRecover ? 'Yes' : 'No') . "\n";
    
    // Get recovery statistics
    echo "\nRecovery statistics:\n";
    $stats = $errorManager->getRecoveryStats();
    echo "- Total operations: {$stats['total_operations']}\n";
    echo "- Recoverable operations: {$stats['recoverable_operations']}\n";
    echo "- Success rate: " . number_format($stats['success_rate'], 1) . "%\n";
    
    if (!empty($stats['error_types'])) {
        echo "- Error types encountered:\n";
        foreach ($stats['error_types'] as $errorType => $count) {
            echo "  - {$errorType}: {$count}\n";
        }
    }
}

// Example 7: Production-Safe Generation with Comprehensive Rollback
function example7_production_safe_generation(): void
{
    echo "\n=== Example 7: Production-Safe Generation ===\n";
    
    $backupManager = app(BackupManagerInterface::class);
    $errorManager = app(ErrorRecoveryManager::class);
    
    // Production context with comprehensive backup requirements
    $context = [
        'model_name' => 'CriticalData',
        'table_name' => 'critical_data',
        'environment' => 'production',
        'requires_validation' => true,
        'critical_operation' => true,
        'backup_verification' => true,
    ];
    
    echo "Initiating production-safe generation...\n";
    
    // Step 1: Pre-generation validation
    echo "Step 1: Pre-generation validation...\n";
    if (!$this->validateProductionEnvironment($context)) {
        echo "✗ Production environment validation failed\n";
        return;
    }
    
    // Step 2: Create comprehensive backup
    echo "Step 2: Creating comprehensive backup...\n";
    $operationId = 'production_' . time();
    
    // Use all available strategies for production
    $strategies = ['file_backup', 'database_rollback', 'composite'];
    $backupResult = $backupManager->createBackup($operationId, $context, $strategies);
    
    if ($backupResult['error_count'] > 0) {
        echo "✗ Backup creation had errors - aborting operation\n";
        foreach ($backupResult['errors'] as $strategy => $error) {
            echo "  - {$strategy}: {$error}\n";
        }
        return;
    }
    
    echo "✓ Backup created successfully\n";
    
    // Step 3: Verify backup integrity
    echo "Step 3: Verifying backup integrity...\n";
    $validation = $errorManager->validateRecovery($operationId);
    
    if (!$validation['valid']) {
        echo "✗ Backup validation failed - aborting operation\n";
        foreach ($validation['errors'] as $error) {
            echo "  - {$error}\n";
        }
        $backupManager->removeAllBackups($operationId);
        return;
    }
    
    echo "✓ Backup validation passed\n";
    
    // Step 4: Perform generation with enhanced error handling
    echo "Step 4: Performing generation...\n";
    
    try {
        $generator = new RollbackableModelGenerator();
        $generator->setRollbackStrategies($strategies);
        
        $result = $generator->generateWithRollback($context, $operationId);
        
        echo "✓ Generation completed successfully\n";
        echo "Result: " . substr($result, 0, 100) . "...\n";
        
        // Step 5: Post-generation validation
        echo "Step 5: Post-generation validation...\n";
        if ($this->validateGeneratedCode($context)) {
            echo "✓ Generated code validation passed\n";
            echo "✓ Production operation completed successfully\n";
        } else {
            echo "✗ Generated code validation failed - initiating rollback\n";
            $backupManager->restore($operationId);
            echo "✓ Rollback completed\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ Generation failed: " . $e->getMessage() . "\n";
        echo "Initiating automatic recovery...\n";
        
        $recovered = $errorManager->handleError($e, $operationId, $context);
        
        if ($recovered) {
            echo "✓ Automatic recovery successful\n";
        } else {
            echo "✗ Automatic recovery failed - manual intervention required\n";
            echo "Backup available for manual restore: {$operationId}\n";
        }
    }
}

// Helper functions for production example
function validateProductionEnvironment(array $context): bool
{
    // Simulate production environment validation
    echo "- Checking environment: " . ($context['environment'] ?? 'unknown') . "\n";
    echo "- Checking database connectivity: OK\n";
    echo "- Checking file permissions: OK\n";
    echo "- Checking disk space: OK\n";
    return true;
}

function validateGeneratedCode(array $context): bool
{
    // Simulate generated code validation
    echo "- Syntax checking: OK\n";
    echo "- Code style validation: OK\n";
    echo "- Unit tests: OK\n";
    return true;
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "AutoGen Rollback System Examples\n";
    echo "================================\n";
    
    // Run all examples
    example1_basic_rollback_generation();
    example2_manual_backup_management();
    example3_custom_generator_with_rollback_points();
    example4_custom_error_recovery();
    example5_batch_operations_with_rollback();
    example6_backup_validation();
    example7_production_safe_generation();
    
    echo "\n=== Examples completed ===\n";
    echo "For more information, see the documentation at docs/rollback-system.md\n";
}