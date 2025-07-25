<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AutoGen DataTable Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the AutoGen DataTable
    | package, including performance settings, caching, and export options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default DataTable Type
    |--------------------------------------------------------------------------
    |
    | This option controls the default type of datatable to generate when
    | no type is specified. Supported types: yajra, livewire, inertia, api
    |
    */
    'default_type' => env('AUTOGEN_DATATABLE_TYPE', 'yajra'),

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | These options control various performance optimizations for datatables.
    |
    */
    'performance' => [
        // Default page size for pagination
        'default_page_size' => 15,
        
        // Maximum page size allowed
        'max_page_size' => 100,
        
        // Enable server-side processing by default
        'server_side' => true,
        
        // Enable query result caching
        'enable_cache' => env('AUTOGEN_DATATABLE_CACHE', false),
        
        // Cache duration in seconds (5 minutes default)
        'cache_duration' => 300,
        
        // Cache tags for easier invalidation
        'cache_tags' => ['datatables'],
        
        // Use cursor pagination for large datasets
        'cursor_pagination' => false,
        
        // Virtual scrolling settings
        'virtual_scroll' => [
            'enabled' => false,
            'row_height' => 50,
            'buffer_size' => 10,
        ],
        
        // Database query optimizations
        'query_optimizations' => [
            'select_specific_columns' => true,
            'use_indexes' => true,
            'chunk_size' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for export functionality including formats and limits.
    |
    */
    'exports' => [
        // Supported export formats
        'formats' => ['excel', 'csv', 'pdf'],
        
        // Maximum number of records for direct export
        'max_direct_export' => 1000,
        
        // Use background jobs for large exports
        'use_background_jobs' => env('AUTOGEN_DATATABLE_BACKGROUND_JOBS', false),
        
        // Export file storage disk
        'storage_disk' => 'public',
        
        // Export file retention period (in hours)
        'file_retention' => 24,
        
        // Export queue name
        'queue_name' => 'exports',
        
        // Chunk size for large exports
        'chunk_size' => 1000,
        
        // Memory limit for exports (in MB)
        'memory_limit' => 512,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search and Filter Settings
    |--------------------------------------------------------------------------
    |
    | Options for search and filtering functionality.
    |
    */
    'search' => [
        // Enable global search by default
        'global_search' => true,
        
        // Search debounce delay (in milliseconds)
        'debounce_delay' => 300,
        
        // Minimum search term length
        'min_search_length' => 2,
        
        // Advanced search filters
        'advanced_filters' => [
            'date_range' => true,
            'status_filter' => true,
            'category_filter' => false,
        ],
        
        // Search operators
        'operators' => ['like', 'equals', 'greater_than', 'less_than', 'between'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operations
    |--------------------------------------------------------------------------
    |
    | Configuration for bulk operations and actions.
    |
    */
    'bulk_operations' => [
        // Enable bulk operations by default
        'enabled' => true,
        
        // Maximum number of items for bulk operations
        'max_items' => 1000,
        
        // Available bulk actions
        'actions' => [
            'delete' => [
                'enabled' => true,
                'requires_confirmation' => true,
                'soft_delete' => true,
            ],
            'activate' => [
                'enabled' => true,
                'requires_confirmation' => false,
            ],
            'deactivate' => [
                'enabled' => true,
                'requires_confirmation' => false,
            ],
            'export' => [
                'enabled' => true,
                'requires_confirmation' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    |
    | User interface configuration options.
    |
    */
    'ui' => [
        // Default CSS framework
        'framework' => 'tailwind', // tailwind, bootstrap, bulma
        
        // Show loading indicators
        'loading_indicators' => true,
        
        // Responsive breakpoints
        'responsive' => [
            'mobile' => 768,
            'tablet' => 1024,
            'desktop' => 1280,
        ],
        
        // Column visibility controls
        'column_visibility' => true,
        
        // Table density options
        'density_options' => ['compact', 'normal', 'comfortable'],
        
        // Theme settings
        'theme' => [
            'dark_mode' => false,
            'primary_color' => '#3B82F6',
            'accent_color' => '#10B981',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration options.
    |
    */
    'security' => [
        // Rate limiting for API endpoints
        'rate_limiting' => [
            'general' => '60,1', // 60 requests per minute
            'export' => '30,1',  // 30 requests per minute
            'bulk' => '20,1',    // 20 requests per minute
        ],
        
        // CSRF protection
        'csrf_protection' => true,
        
        // Input sanitization
        'sanitize_input' => true,
        
        // SQL injection protection
        'sql_injection_protection' => true,
        
        // XSS protection
        'xss_protection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Yajra DataTables Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Yajra DataTables implementation.
    |
    */
    'yajra' => [
        // Default HTML builder options
        'html_builder' => [
            'dom' => 'Bfrtip',
            'buttons' => ['excel', 'csv', 'pdf', 'print', 'reset', 'reload'],
            'responsive' => true,
            'auto_width' => false,
            'state_save' => false,
            'processing' => true,
            'server_side' => true,
            'search_delay' => 350,
        ],
        
        // Column definitions
        'column_defs' => [
            'orderable_columns' => [],
            'searchable_columns' => [],
            'visible_columns' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Livewire datatables.
    |
    */
    'livewire' => [
        // Polling interval for real-time updates (in milliseconds)
        'polling_interval' => null,
        
        // Lazy loading
        'lazy_loading' => false,
        
        // Wire navigation
        'wire_navigate' => true,
        
        // Defer loading
        'defer' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inertia.js Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Inertia.js datatables.
    |
    */
    'inertia' => [
        // Page component name
        'page_component' => 'DataTable/Index',
        
        // Preserve scroll position
        'preserve_scroll' => true,
        
        // Preserve state
        'preserve_state' => true,
        
        // Use history API
        'replace_history' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to API datatables.
    |
    */
    'api' => [
        // Default response format
        'response_format' => 'json',
        
        // Include metadata in responses
        'include_metadata' => true,
        
        // Include links in responses
        'include_links' => true,
        
        // API versioning
        'version' => 'v1',
        
        // Documentation generation
        'generate_docs' => true,
        
        // OpenAPI specification
        'openapi_spec' => [
            'version' => '3.0.0',
            'title' => 'DataTable API',
            'description' => 'High-performance datatable API endpoints',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Stub Paths
    |--------------------------------------------------------------------------
    |
    | Path to custom stub files for code generation.
    |
    */
    'custom_stubs_path' => resource_path('stubs/datatable'),

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for logging datatable operations.
    |
    */
    'logging' => [
        // Enable query logging
        'log_queries' => env('AUTOGEN_DATATABLE_LOG_QUERIES', false),
        
        // Log slow queries (in milliseconds)
        'slow_query_threshold' => 1000,
        
        // Log export operations
        'log_exports' => true,
        
        // Log bulk operations
        'log_bulk_operations' => true,
        
        // Log channel
        'channel' => 'single',
    ],
];