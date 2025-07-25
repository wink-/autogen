<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scaffold Default Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the default configuration for the AutoGen Scaffold
    | package. These settings can be overridden in your application's
    | config/autogen/scaffold.php file.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    |
    | Default values for scaffold command options. These will be used when
    | options are not explicitly provided.
    |
    */
    'defaults' => [
        'controller_type' => 'resource',
        'view_framework' => 'tailwind',
        'view_layout' => 'layouts.app',
        'pagination' => 15,
        'force' => false,
        'with_relationships' => false,
        'with_validation' => true,
        'with_policy' => false,
        'with_scopes' => false,
        'with_traits' => true,
        'with_datatable' => false,
        'with_search' => false,
        'with_modals' => false,
        'with_migration' => false,
        'with_factory' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Dependencies
    |--------------------------------------------------------------------------
    |
    | Define the dependency relationships between different packages.
    | This is used by the DependencyResolver to determine execution order.
    |
    */
    'dependencies' => [
        'model' => [],
        'controller' => ['model'],
        'views' => ['model', 'controller'],
        'factory' => ['model'],
        'datatable' => ['model', 'views'],
        'migration' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Priority
    |--------------------------------------------------------------------------
    |
    | Define the priority of packages when resolving dependencies.
    | Lower numbers mean higher priority.
    |
    */
    'priority' => [
        'model' => 1,
        'migration' => 2,
        'controller' => 3,
        'views' => 4,
        'factory' => 5,
        'datatable' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Packages
    |--------------------------------------------------------------------------
    |
    | Packages marked as critical will stop the entire scaffold process
    | if they fail. Non-critical packages will log warnings but allow
    | the process to continue.
    |
    */
    'critical_packages' => [
        'model',
        'controller',
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Timeouts
    |--------------------------------------------------------------------------
    |
    | Maximum execution time (in seconds) for each package type.
    | Set to 0 for no timeout.
    |
    */
    'timeouts' => [
        'model' => 30,
        'controller' => 45,
        'views' => 60,
        'factory' => 20,
        'datatable' => 40,
        'migration' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Estimation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for estimating execution time and progress.
    |
    */
    'estimation' => [
        // Base time estimates (in seconds) per package
        'base_times' => [
            'model' => 5,
            'controller' => 8,
            'views' => 12,
            'factory' => 3,
            'datatable' => 6,
            'migration' => 4,
        ],

        // Multipliers based on options
        'multipliers' => [
            'with_relationships' => 1.5,
            'with_validation' => 1.3,
            'with_policy' => 1.2,
            'with_scopes' => 1.1,
            'with_datatable' => 1.4,
            'with_search' => 1.2,
            'multiple_views' => 1.3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation settings for the configuration validator.
    |
    */
    'validation' => [
        // Enable strict validation
        'strict' => true,

        // Require certain conditions
        'require_database_connection' => true,
        'require_writable_directories' => true,
        'check_existing_files' => true,

        // Validation thresholds
        'max_pagination' => 1000,
        'min_pagination' => 1,

        // File system checks
        'required_directories' => [
            'app',
            'app/Http/Controllers',
            'app/Models',
            'resources/views',
            'database/migrations',
            'database/factories',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress Tracking
    |--------------------------------------------------------------------------
    |
    | Settings for progress tracking and reporting.
    |
    */
    'progress' => [
        // Enable progress tracking
        'enabled' => true,

        // Log progress to Laravel log
        'log_progress' => true,

        // Progress reporting interval (in seconds)
        'reporting_interval' => 1.0,

        // Store progress history
        'store_history' => true,

        // History file location (relative to storage path)
        'history_file' => 'autogen/scaffold_history.json',

        // Maximum history entries to keep
        'max_history_entries' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rollback Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for rollback functionality.
    |
    */
    'rollback' => [
        // Enable rollback functionality
        'enabled' => true,

        // Create backup of existing files before overwriting
        'backup_existing' => true,

        // Backup directory (relative to storage path)
        'backup_directory' => 'autogen/backups',

        // Maximum backup age (in days)
        'max_backup_age' => 30,

        // Files to exclude from rollback
        'exclude_patterns' => [
            '*.log',
            '*.cache',
            'node_modules/*',
            'vendor/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Interactive Mode Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for interactive mode prompts.
    |
    */
    'interactive' => [
        // Default answers for common prompts
        'defaults' => [
            'overwrite_existing' => false,
            'include_relationships' => true,
            'generate_validation' => true,
            'generate_policy' => false,
            'css_framework' => 'tailwind',
            'use_datatables' => false,
        ],

        // Skip certain prompts based on context
        'skip_prompts' => [
            'views_for_api' => true, // Don't ask about views for API controllers
            'datatable_without_views' => true, // Don't ask about datatables without views
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dry Run Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for dry run mode.
    |
    */
    'dry_run' => [
        // Show detailed analysis in dry run
        'show_detailed_analysis' => true,

        // Validate file contents in dry run
        'validate_file_contents' => false,

        // Check for potential conflicts
        'check_conflicts' => true,

        // Estimate disk space usage
        'estimate_disk_usage' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Generation Settings
    |--------------------------------------------------------------------------
    |
    | Settings that affect how files are generated.
    |
    */
    'file_generation' => [
        // File permissions for generated files
        'file_permissions' => 0644,

        // Directory permissions for created directories
        'directory_permissions' => 0755,

        // Add generation timestamp to files
        'add_timestamp' => true,

        // Add generator signature
        'add_signature' => true,

        // Generator signature template
        'signature_template' => '// Generated by AutoGen Scaffold v{version} on {date}',

        // Preserve existing file comments
        'preserve_comments' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for error handling and recovery.
    |
    */
    'error_handling' => [
        // Continue on non-critical errors
        'continue_on_error' => true,

        // Maximum retries for failed operations
        'max_retries' => 3,

        // Retry delay (in seconds)
        'retry_delay' => 1,

        // Log all errors to Laravel log
        'log_errors' => true,

        // Send error notifications (requires notification configuration)
        'notify_errors' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize scaffold performance.
    |
    */
    'performance' => [
        // Enable parallel execution where possible
        'enable_parallel' => false,

        // Maximum parallel processes
        'max_parallel_processes' => 3,

        // Use caching for repeated operations
        'enable_caching' => true,

        // Cache duration (in minutes)
        'cache_duration' => 60,

        // Memory limit for scaffold operations
        'memory_limit' => '256M',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for integration with other tools and services.
    |
    */
    'integrations' => [
        // IDE integration
        'ide' => [
            'generate_ide_files' => false,
            'ide_type' => 'phpstorm', // phpstorm, vscode, sublime
        ],

        // Version control integration
        'vcs' => [
            'auto_commit' => false,
            'commit_message_template' => 'AutoGen: Scaffold {table} ({packages})',
            'create_branch' => false,
            'branch_name_template' => 'autogen/scaffold-{table}',
        ],

        // Testing integration
        'testing' => [
            'run_tests_after_generation' => false,
            'test_command' => 'php artisan test',
            'test_timeout' => 300,
        ],
    ],
];