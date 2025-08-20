<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rollback Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for AutoGen's rollback
    | and backup system. You can customize backup strategies, retention
    | policies, and error recovery behavior.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Backup Storage
    |--------------------------------------------------------------------------
    |
    | Configure where backups are stored. The backup_disk should be a
    | filesystem disk configured in your config/filesystems.php file.
    |
    */

    'backup_disk' => env('AUTOGEN_BACKUP_DISK', 'local'),
    'backup_path' => env('AUTOGEN_BACKUP_PATH', 'autogen/backups'),
    'db_backup_path' => env('AUTOGEN_DB_BACKUP_PATH', 'autogen/db_backups'),
    'index_path' => env('AUTOGEN_INDEX_PATH', 'autogen/backup_index.json'),

    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    |
    | Configure how long backups are retained before being eligible for
    | cleanup. The retention_days setting applies to automatic cleanup.
    |
    */

    'retention_days' => env('AUTOGEN_BACKUP_RETENTION_DAYS', 30),
    'auto_cleanup' => env('AUTOGEN_AUTO_CLEANUP', true),
    'cleanup_schedule' => env('AUTOGEN_CLEANUP_SCHEDULE', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Backup Strategies
    |--------------------------------------------------------------------------
    |
    | Configure which backup strategies are enabled and their settings.
    | Available strategies: file_backup, database_rollback, composite
    |
    */

    'default_strategies' => [
        'file_backup',
        'database_rollback',
    ],

    'strategies' => [
        'file_backup' => [
            'enabled' => true,
            'backup_permissions' => true,
            'backup_timestamps' => true,
        ],

        'database_rollback' => [
            'enabled' => true,
            'supported_connections' => ['mysql', 'pgsql', 'sqlite'],
            'backup_data' => true,
            'backup_schema' => true,
            'backup_migrations' => true,
        ],

        'composite' => [
            'enabled' => true,
            'critical_strategies' => ['file_backup'],
            'ignore_strategy_errors' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Recovery
    |--------------------------------------------------------------------------
    |
    | Configure automatic error recovery behavior. When enabled, the system
    | will attempt to automatically rollback operations that fail.
    |
    */

    'auto_recovery' => [
        'enabled' => env('AUTOGEN_AUTO_RECOVERY', true),
        'recovery_strategies' => [
            'filesystem_error' => true,
            'database_error' => true,
            'generation_error' => true,
            'template_error' => false,
        ],
        'max_recovery_attempts' => 3,
        'recovery_timeout' => 300, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Configure validation behavior for backups and recovery operations.
    |
    */

    'validation' => [
        'validate_after_backup' => true,
        'validate_after_restore' => true,
        'strict_validation' => env('AUTOGEN_STRICT_VALIDATION', false),
        'custom_validators' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for rollback and recovery operations.
    |
    */

    'logging' => [
        'enabled' => true,
        'channel' => env('AUTOGEN_LOG_CHANNEL', 'daily'),
        'level' => env('AUTOGEN_LOG_LEVEL', 'info'),
        'log_successful_backups' => false,
        'log_successful_restores' => true,
        'log_cleanup_operations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings for backup operations.
    |
    */

    'performance' => [
        'compression' => [
            'enabled' => env('AUTOGEN_BACKUP_COMPRESSION', true),
            'algorithm' => 'gzip',
            'level' => 6,
        ],
        
        'parallel_operations' => [
            'enabled' => env('AUTOGEN_PARALLEL_BACKUP', false),
            'max_processes' => 4,
        ],
        
        'chunked_processing' => [
            'enabled' => true,
            'chunk_size' => 1000, // records per chunk for database backups
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Configure security settings for backup operations.
    |
    */

    'security' => [
        'encrypt_backups' => env('AUTOGEN_ENCRYPT_BACKUPS', false),
        'encryption_key' => env('AUTOGEN_BACKUP_ENCRYPTION_KEY'),
        'secure_deletion' => env('AUTOGEN_SECURE_DELETION', false),
        'verify_checksums' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notifications for backup and recovery events.
    |
    */

    'notifications' => [
        'enabled' => env('AUTOGEN_NOTIFICATIONS', false),
        'channels' => ['mail', 'slack'],
        'events' => [
            'backup_created' => false,
            'backup_failed' => true,
            'restore_completed' => true,
            'restore_failed' => true,
            'cleanup_completed' => false,
            'recovery_attempted' => true,
        ],
        'recipients' => [
            'mail' => env('AUTOGEN_NOTIFICATION_EMAIL'),
            'slack' => env('AUTOGEN_NOTIFICATION_SLACK_WEBHOOK'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development
    |--------------------------------------------------------------------------
    |
    | Configuration options for development and testing environments.
    |
    */

    'development' => [
        'mock_operations' => env('AUTOGEN_MOCK_OPERATIONS', false),
        'debug_mode' => env('AUTOGEN_DEBUG_MODE', false),
        'preserve_test_backups' => env('AUTOGEN_PRESERVE_TEST_BACKUPS', false),
    ],
];