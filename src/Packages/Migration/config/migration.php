<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Migration Generator Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the AutoGen Migration
    | package. These settings control how database schemas are reverse
    | engineered into Laravel migration files.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Output Path
    |--------------------------------------------------------------------------
    |
    | The default directory where migration files will be generated.
    | This can be overridden using the --output-path command option.
    |
    */
    'output_path' => database_path('migrations'),

    /*
    |--------------------------------------------------------------------------
    | Migration Naming Convention
    |--------------------------------------------------------------------------
    |
    | Configure how migration files and classes are named.
    |
    */
    'naming' => [
        'timestamp_prefix' => true,
        'class_prefix' => 'Create',
        'class_suffix' => 'Table',
        'file_suffix' => '.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Processing Options
    |--------------------------------------------------------------------------
    |
    | Configure which tables and table features to include in migrations.
    |
    */
    'tables' => [
        // Tables to always ignore
        'ignore' => [
            'migrations',
            'failed_jobs',
            'password_resets',
            'password_reset_tokens',
            'personal_access_tokens',
            'cache',
            'cache_locks',
            'sessions',
            'jobs',
            'job_batches',
            'telescope_entries',
            'telescope_entries_tags',
            'telescope_monitoring',
        ],

        // Include database views
        'include_views' => false,

        // Preserve table creation order based on foreign key dependencies
        'preserve_order' => true,

        // Maximum tables to process in a single run (0 = no limit)
        'max_tables' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Processing Options
    |--------------------------------------------------------------------------
    |
    | Configure how database columns are mapped to Laravel migration methods.
    |
    */
    'columns' => [
        // Include column comments in migrations
        'include_comments' => true,

        // Include default values
        'include_defaults' => true,

        // Include unsigned modifiers for integer columns
        'include_unsigned' => true,

        // Custom column type mappings
        'type_mappings' => [
            // Add custom mappings here
            // 'custom_type' => 'laravel_method',
        ],

        // Column names that should be treated as special types
        'special_columns' => [
            'email' => ['type' => 'string', 'length' => 255],
            'password' => ['type' => 'string', 'length' => 255],
            'remember_token' => ['type' => 'rememberToken'],
            'email_verified_at' => ['type' => 'timestamp', 'nullable' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Processing Options
    |--------------------------------------------------------------------------
    |
    | Configure how database indexes are handled in migrations.
    |
    */
    'indexes' => [
        // Include all indexes in migrations
        'include_indexes' => true,

        // Include primary key indexes (usually automatic)
        'include_primary' => false,

        // Include unique indexes
        'include_unique' => true,

        // Include composite indexes
        'include_composite' => true,

        // Include full-text indexes (MySQL)
        'include_fulltext' => true,

        // Maximum index name length (database specific)
        'max_name_length' => 64,
    ],

    /*
    |--------------------------------------------------------------------------
    | Foreign Key Processing Options
    |--------------------------------------------------------------------------
    |
    | Configure how foreign key constraints are handled.
    |
    */
    'foreign_keys' => [
        // Include foreign key constraints
        'include_foreign_keys' => true,

        // Generate separate migration files for foreign keys
        'separate_migrations' => false,

        // Include ON UPDATE actions
        'include_on_update' => true,

        // Include ON DELETE actions
        'include_on_delete' => true,

        // Handle circular dependencies by separating foreign keys
        'handle_circular_dependencies' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database-Specific Options
    |--------------------------------------------------------------------------
    |
    | Options specific to different database drivers.
    |
    */
    'database_specific' => [
        'mysql' => [
            'include_engine' => true,
            'include_charset' => true,
            'include_collation' => true,
            'include_row_format' => false,
            'include_partitions' => false,
            'include_triggers' => false,
        ],

        'postgresql' => [
            'include_sequences' => true,
            'include_triggers' => false,
            'include_extensions' => false,
            'schema_name' => 'public',
        ],

        'sqlite' => [
            'include_pragma' => false,
            'include_triggers' => false,
        ],

        'sqlserver' => [
            'include_schema' => false,
            'include_triggers' => false,
            'schema_name' => 'dbo',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Content Options
    |--------------------------------------------------------------------------
    |
    | Configure the content and structure of generated migrations.
    |
    */
    'content' => [
        // Include detailed comments in migration files
        'include_comments' => true,

        // Include original database information in comments
        'include_metadata' => true,

        // Include rollback support in down() methods
        'rollback_support' => true,

        // Format code with proper indentation
        'format_code' => true,

        // Include use statements for DB facade when needed
        'include_db_facade' => true,

        // Add strict types declaration
        'strict_types' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation and Safety Options
    |--------------------------------------------------------------------------
    |
    | Options to ensure migration safety and validation.
    |
    */
    'validation' => [
        // Validate migration syntax before writing
        'validate_syntax' => true,

        // Check for existing migrations before generating
        'check_existing' => true,

        // Create backup of existing migrations
        'backup_existing' => false,

        // Maximum file size for migrations (in KB)
        'max_file_size' => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Options
    |--------------------------------------------------------------------------
    |
    | Options to optimize performance for large databases.
    |
    */
    'performance' => [
        // Process tables in chunks for large databases
        'chunk_size' => 50,

        // Cache database schema analysis
        'cache_schema' => true,

        // Cache duration in minutes
        'cache_duration' => 60,

        // Maximum memory limit for processing
        'memory_limit' => '512M',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Stub Paths
    |--------------------------------------------------------------------------
    |
    | Paths to custom migration stub files. Leave null to use defaults.
    |
    */
    'stubs' => [
        'migration' => null,
        'foreign_key_migration' => null,
        'rollback_migration' => null,
        'table_migration' => null,
        'index_migration' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Output and Logging Options
    |--------------------------------------------------------------------------
    |
    | Configure output verbosity and logging.
    |
    */
    'output' => [
        // Show progress during migration generation
        'show_progress' => true,

        // Show detailed table analysis
        'show_analysis' => false,

        // Show generated migration content
        'show_content' => false,

        // Log migration generation activity
        'log_activity' => true,

        // Log level (debug, info, warning, error)
        'log_level' => 'info',
    ],
];