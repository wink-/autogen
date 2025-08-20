# AutoGen Rollback System Documentation

## Overview

The AutoGen Rollback System provides comprehensive backup and restore capabilities for all code generation operations. It ensures that any generation process can be safely rolled back to its previous state, protecting your codebase from unwanted changes or generation errors.

## Key Features

- **Multi-Strategy Backups**: File system and database backup strategies
- **Automatic Error Recovery**: Intelligent error detection and automatic rollback
- **Granular Control**: Point-in-time rollback capabilities
- **Command-Line Interface**: Easy-to-use Artisan commands
- **Validation System**: Comprehensive backup and restore validation
- **Configurable Retention**: Automated cleanup with configurable retention policies

## Core Components

### 1. Backup Strategies

#### File Backup Strategy
Handles backup and restoration of file system changes:
- Model files
- Controller files
- Migration files
- Factory files
- Configuration files

#### Database Rollback Strategy
Manages database-related rollbacks:
- Table schema backups
- Data snapshots
- Migration state preservation
- Index and constraint backups

#### Composite Rollback Strategy
Coordinates multiple strategies for complex operations:
- Ensures atomicity across multiple backup types
- Handles partial failure scenarios
- Maintains operation consistency

### 2. Backup Manager

The central coordinator for all backup operations:

```php
use AutoGen\Common\Contracts\BackupManagerInterface;

$backupManager = app(BackupManagerInterface::class);

// Create backup
$result = $backupManager->createBackup('operation_id', $context, ['file_backup', 'database_rollback']);

// Restore operation
$success = $backupManager->restore('operation_id');

// List all backups
$backups = $backupManager->listAllBackups();
```

### 3. Error Recovery Manager

Provides intelligent error recovery with automatic rollback:

```php
use AutoGen\Common\Rollback\ErrorRecoveryManager;

$errorManager = app(ErrorRecoveryManager::class);

try {
    // Your generation code here
} catch (\Exception $e) {
    $recovered = $errorManager->handleError($e, $operationId, $context);
    if (!$recovered) {
        // Handle unrecoverable error
    }
}
```

## Usage Examples

### Basic Rollback-Enabled Generation

```php
use AutoGen\Common\Rollback\RollbackableModelGenerator;

$generator = new RollbackableModelGenerator();

$context = [
    'model_name' => 'User',
    'table_name' => 'users',
    'namespace' => 'App\\Models',
];

try {
    $result = $generator->generateWithRollback($context);
    echo "Generation successful: " . $result;
} catch (\Exception $e) {
    // Automatic rollback will be attempted
    echo "Generation failed: " . $e->getMessage();
}
```

### Manual Backup and Restore

```php
use AutoGen\Common\Contracts\BackupManagerInterface;

$backupManager = app(BackupManagerInterface::class);

// Create manual backup point
$operationId = 'manual_backup_' . time();
$context = [
    'description' => 'Before major refactoring',
    'files_to_backup' => [
        app_path('Models/User.php'),
        app_path('Http/Controllers/UserController.php'),
    ],
];

$backupResult = $backupManager->createBackup($operationId, $context);

// ... perform your operations ...

// Restore if needed
if ($needsRollback) {
    $success = $backupManager->restore($operationId);
    if ($success) {
        echo "Successfully rolled back changes";
    }
}
```

### Custom Error Recovery Strategy

```php
use AutoGen\Common\Rollback\ErrorRecoveryManager;

$errorManager = app(ErrorRecoveryManager::class);

// Register custom recovery strategy
$errorManager->registerRecoveryStrategy('my_custom_error', function ($error, $operationId, $context) {
    // Custom recovery logic
    if (str_contains($error->getMessage(), 'specific error pattern')) {
        // Attempt custom recovery
        return $this->performCustomRecovery($operationId, $context);
    }
    
    return false; // Fall back to default strategy
});
```

### Generator with Rollback Points

```php
use AutoGen\Common\Traits\HasRollbackSupport;

class MyCustomGenerator
{
    use HasRollbackSupport;
    
    protected function performGeneration(array $context): string
    {
        // Create rollback point before critical operation
        $rollbackPoint1 = $this->createRollbackPoint('before_file_generation');
        
        // Generate files
        $this->executeWithRollbackProtection(function () use ($context) {
            $this->generateFiles($context);
        }, $context);
        
        // Another rollback point
        $rollbackPoint2 = $this->createRollbackPoint('before_database_changes');
        
        // Make database changes
        $this->executeWithRollbackProtection(function () use ($context) {
            $this->createDatabaseTables($context);
        }, $context);
        
        return "Generation completed successfully";
    }
}
```

## Command Line Interface

### Rollback Operations

```bash
# List available rollback operations
php artisan autogen:backup:list

# Rollback latest operation
php artisan autogen:rollback --latest

# Rollback specific operation
php artisan autogen:rollback operation_id_123

# Interactive rollback selection
php artisan autogen:rollback

# Force rollback without confirmation
php artisan autogen:rollback operation_id_123 --confirm
```

### Backup Management

```bash
# List all backups with details
php artisan autogen:backup:list --detailed

# List backups for specific strategy
php artisan autogen:backup:list --strategy=file_backup

# Export backup list as JSON
php artisan autogen:backup:list --format=json

# Clean old backups (30 days retention)
php artisan autogen:backup:clean --days=30

# Dry run cleanup to see what would be deleted
php artisan autogen:backup:clean --days=7 --dry-run

# Force cleanup without confirmation
php artisan autogen:backup:clean --days=30 --force
```

## Configuration

### Publishing Configuration

```bash
php artisan vendor:publish --tag=autogen-rollback-config
```

### Key Configuration Options

```php
// config/autogen/rollback.php

return [
    // Storage configuration
    'backup_disk' => env('AUTOGEN_BACKUP_DISK', 'local'),
    'retention_days' => env('AUTOGEN_BACKUP_RETENTION_DAYS', 30),
    
    // Backup strategies
    'default_strategies' => ['file_backup', 'database_rollback'],
    
    // Auto recovery
    'auto_recovery' => [
        'enabled' => true,
        'recovery_strategies' => [
            'filesystem_error' => true,
            'database_error' => true,
            'generation_error' => true,
        ],
    ],
    
    // Security
    'security' => [
        'encrypt_backups' => false,
        'verify_checksums' => true,
    ],
];
```

## Error Recovery Strategies

### Built-in Recovery Types

1. **Filesystem Error Recovery**
   - Permission denied errors (manual intervention required)
   - Disk space issues (manual intervention required)
   - File corruption (automatic rollback)

2. **Database Error Recovery**
   - Connection failures (manual intervention required)
   - Schema conflicts (automatic rollback)
   - Data integrity issues (automatic rollback)

3. **Generation Error Recovery**
   - Template errors (no rollback needed)
   - Validation failures (automatic rollback)
   - Code generation failures (automatic rollback)

### Custom Recovery Strategies

```php
// Register in a service provider
$errorManager = app(ErrorRecoveryManager::class);

$errorManager->registerRecoveryStrategy('api_error', function ($error, $operationId, $context) {
    if (str_contains($error->getMessage(), 'API rate limit')) {
        // Wait and retry logic
        sleep(60);
        return $this->retryOperation($operationId, $context);
    }
    
    return false; // Use default recovery
});
```

## Validation System

### Post-Backup Validation

The system automatically validates backups after creation:

```php
$validation = $errorManager->validateRecovery($operationId);

if (!$validation['valid']) {
    echo "Backup validation failed:\n";
    foreach ($validation['errors'] as $error) {
        echo "- {$error}\n";
    }
}
```

### Custom Validators

```php
// config/autogen/rollback.php
'validation' => [
    'custom_validators' => [
        \App\Validators\CodeSyntaxValidator::class,
        \App\Validators\TestIntegrityValidator::class,
    ],
],
```

## Best Practices

### 1. Strategy Selection

- Use `file_backup` for file-only operations
- Use `database_rollback` for schema changes
- Use both for full CRUD generation operations
- Consider `composite` for complex multi-step operations

### 2. Retention Management

```php
// Set appropriate retention based on your needs
'retention_days' => 30, // Production: 30+ days
'retention_days' => 7,  // Development: 7 days
'retention_days' => 1,  // Testing: 1 day
```

### 3. Error Handling

```php
try {
    $result = $generator->generateWithRollback($context);
} catch (RollbackException $e) {
    // Rollback-specific error
    Log::error('Rollback failed: ' . $e->getMessage());
} catch (\Exception $e) {
    // General error (rollback may have been attempted)
    Log::error('Generation failed: ' . $e->getMessage());
}
```

### 4. Backup Verification

Always verify critical backups:

```php
$backupResult = $backupManager->createBackup($operationId, $context);

if ($backupResult['error_count'] > 0) {
    // Handle backup errors before proceeding
    foreach ($backupResult['errors'] as $strategy => $error) {
        Log::warning("Backup strategy {$strategy} failed: {$error}");
    }
}
```

## Troubleshooting

### Common Issues

1. **Backup Creation Fails**
   - Check disk space and permissions
   - Verify backup disk configuration
   - Review storage filesystem settings

2. **Restore Operations Fail**
   - Ensure backup files haven't been manually deleted
   - Check file permissions on restore locations
   - Verify database connection for database restores

3. **Auto Recovery Not Working**
   - Check `auto_recovery.enabled` configuration
   - Verify error classification is correct
   - Review logs for recovery attempt details

### Debug Mode

Enable debug mode for detailed logging:

```php
// config/autogen/rollback.php
'development' => [
    'debug_mode' => true,
],
```

## Security Considerations

### Backup Encryption

```php
'security' => [
    'encrypt_backups' => true,
    'encryption_key' => env('AUTOGEN_BACKUP_ENCRYPTION_KEY'),
],
```

### Secure Deletion

```php
'security' => [
    'secure_deletion' => true, // Overwrite files before deletion
],
```

### Access Control

Ensure backup directories are not web-accessible and have appropriate file system permissions.

## Performance Optimization

### Compression

```php
'performance' => [
    'compression' => [
        'enabled' => true,
        'algorithm' => 'gzip',
        'level' => 6,
    ],
],
```

### Parallel Processing

```php
'performance' => [
    'parallel_operations' => [
        'enabled' => true,
        'max_processes' => 4,
    ],
],
```

## Integration with CI/CD

### Pre-deployment Backups

```bash
# Create backup before deployment
php artisan autogen:backup:create --operation="pre_deploy_$(date +%s)"

# Deploy your changes...

# Rollback if needed
php artisan autogen:rollback --latest --confirm
```

### Automated Testing

```php
// In your test setup
protected function setUp(): void
{
    parent::setUp();
    
    // Create backup before each test
    $this->backupId = app(BackupManagerInterface::class)
        ->createBackup('test_' . time(), ['test' => true]);
}

protected function tearDown(): void
{
    // Restore state after each test
    app(BackupManagerInterface::class)->restore($this->backupId);
    
    parent::tearDown();
}
```

This comprehensive rollback system ensures that your AutoGen operations are safe, reliable, and easily recoverable, giving you confidence to generate and modify code at scale.