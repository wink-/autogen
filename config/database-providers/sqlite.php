<?php

declare(strict_types=1);

/**
 * SQLite Database Provider Configuration for AutoGen
 * 
 * This configuration file contains SQLite-specific settings
 * for the AutoGen package suite.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | SQLite Connection Templates
    |--------------------------------------------------------------------------
    |
    | Pre-configured connection templates for common SQLite setups
    |
    */
    'connection_templates' => [
        'local' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        
        'testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        
        'legacy' => [
            'driver' => 'sqlite',
            'database' => env('LEGACY_DB_PATH', database_path('legacy.sqlite')),
            'prefix' => env('LEGACY_DB_PREFIX', ''),
            'foreign_key_constraints' => false, // Often disabled in legacy SQLite
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQLite Type Mappings
    |--------------------------------------------------------------------------
    |
    | Maps SQLite column types to Laravel cast types and validation rules
    | Note: SQLite has dynamic typing, so these are based on declared types
    |
    */
    'type_mappings' => [
        'cast_mappings' => [
            'integer' => 'integer',
            'int' => 'integer',
            'tinyint' => 'integer',
            'smallint' => 'integer',
            'mediumint' => 'integer',
            'bigint' => 'integer',
            'unsigned big int' => 'integer',
            'int2' => 'integer',
            'int8' => 'integer',
            'boolean' => 'boolean',
            'bool' => 'boolean',
            'real' => 'float',
            'double' => 'double',
            'double precision' => 'double',
            'float' => 'float',
            'numeric' => 'decimal:2',
            'decimal' => 'decimal:2',
            'text' => 'string',
            'character' => 'string',
            'varchar' => 'string',
            'varying character' => 'string',
            'nchar' => 'string',
            'native character' => 'string',
            'nvarchar' => 'string',
            'clob' => 'string',
            'blob' => 'string',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'datetime',
            'time' => 'string',
            'json' => 'array', // SQLite 3.38+ has JSON support
        ],
        
        'validation_mappings' => [
            'integer' => 'integer',
            'tinyint' => 'integer|min:0|max:255',
            'smallint' => 'integer',
            'bigint' => 'integer',
            'boolean' => 'boolean',
            'real' => 'numeric',
            'float' => 'numeric',
            'double' => 'numeric',
            'numeric' => 'numeric',
            'decimal' => 'numeric',
            'text' => 'string',
            'varchar' => 'string|max:{length}',
            'character' => 'string|size:{length}',
            'date' => 'date',
            'datetime' => 'date',
            'timestamp' => 'date',
            'time' => 'date_format:H:i:s',
            'json' => 'json',
        ],

        'faker_mappings' => [
            'integer' => 'randomNumber()',
            'tinyint' => 'numberBetween(0, 255)',
            'smallint' => 'numberBetween(-32768, 32767)',
            'bigint' => 'randomNumber()',
            'boolean' => 'boolean',
            'real' => 'randomFloat()',
            'float' => 'randomFloat()',
            'double' => 'randomFloat()',
            'numeric' => 'randomFloat(2, 0, 999999)',
            'decimal' => 'randomFloat(2, 0, 999999)',
            'text' => 'paragraph',
            'varchar' => 'text({length})',
            'character' => 'lexify(str_repeat("?", {length}))',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'dateTime',
            'time' => 'time',
            'json' => 'json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQLite Introspection Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to SQLite database introspection
    |
    */
    'introspection' => [
        'pragma_commands' => [
            'table_info' => 'PRAGMA table_info({table})',
            'table_list' => 'PRAGMA table_list',
            'foreign_key_list' => 'PRAGMA foreign_key_list({table})',
            'index_list' => 'PRAGMA index_list({table})',
            'index_info' => 'PRAGMA index_info({index})',
            'database_list' => 'PRAGMA database_list',
        ],
        
        'system_tables' => [
            'sqlite_master',
            'sqlite_sequence',
            'sqlite_stat1',
            'sqlite_stat2',
            'sqlite_stat3',
            'sqlite_stat4',
        ],
        
        'relationship_detection' => [
            'foreign_key_enabled' => 'PRAGMA foreign_keys',
            'foreign_key_query' => 'PRAGMA foreign_key_list({table})',
        ],
        
        'schema_queries' => [
            'create_table' => "SELECT sql FROM sqlite_master WHERE type='table' AND name=?",
            'indexes' => "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name=?",
            'triggers' => "SELECT sql FROM sqlite_master WHERE type='trigger' AND tbl_name=?",
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQLite Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | Performance-specific settings for SQLite
    |
    */
    'performance' => [
        'pragma_optimizations' => [
            'journal_mode' => 'WAL', // Write-Ahead Logging
            'synchronous' => 'NORMAL',
            'cache_size' => -64000, // 64MB cache
            'temp_store' => 'MEMORY',
            'mmap_size' => 268435456, // 256MB memory-mapped I/O
        ],
        
        'query_optimizations' => [
            'use_indexes' => true,
            'avoid_select_star' => true,
            'use_limits' => true,
            'use_exists_over_in' => true,
            'analyze_tables' => true,
        ],
        
        'index_recommendations' => [
            'auto_suggest' => true,
            'composite_threshold' => 3,
            'covering_indexes' => false, // SQLite doesn't have covering indexes
            'partial_indexes' => true, // SQLite 3.8.0+
            'expression_indexes' => true,
        ],
        
        'connection_optimization' => [
            'persistent_connections' => false,
            'busy_timeout' => 30000, // 30 seconds
            'auto_vacuum' => 'INCREMENTAL',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQLite Version Compatibility
    |--------------------------------------------------------------------------
    |
    | Version-specific feature detection and compatibility
    |
    */
    'version_compatibility' => [
        '3.35' => [
            'drop_column_support' => true,
            'returning_clause' => true,
            'materialized_views' => false,
            'common_table_expressions' => true,
            'window_functions' => true,
            'json_support' => false,
        ],
        
        '3.38' => [
            'drop_column_support' => true,
            'returning_clause' => true,
            'materialized_views' => false,
            'common_table_expressions' => true,
            'window_functions' => true,
            'json_support' => true, // JSON1 extension built-in
            'json_operators' => true,
        ],
        
        '3.39' => [
            'drop_column_support' => true,
            'returning_clause' => true,
            'materialized_views' => false,
            'common_table_expressions' => true,
            'window_functions' => true,
            'json_support' => true,
            'json_operators' => true,
            'right_outer_joins' => true,
            'full_outer_joins' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQLite Specific Features
    |--------------------------------------------------------------------------
    |
    | SQLite-specific features and their handling
    |
    */
    'features' => [
        'autoincrement' => [
            'detection' => 'AUTOINCREMENT',
            'handling' => 'exclude_from_fillable',
            'rowid_alias' => true,
        ],
        
        'timestamps' => [
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
            'auto_detection' => true,
            'format' => 'Y-m-d H:i:s', // SQLite stores as TEXT
        ],
        
        'soft_deletes' => [
            'column_name' => 'deleted_at',
            'auto_detection' => true,
        ],
        
        'json_columns' => [
            'auto_detection' => true,
            'cast_to_array' => true,
            'json1_extension' => true,
        ],
        
        'check_constraints' => [
            'auto_detection' => true,
            'extract_from_sql' => true,
        ],
        
        'unique_constraints' => [
            'auto_detection' => true,
            'composite_unique' => true,
        ],
        
        'default_values' => [
            'auto_detection' => true,
            'current_timestamp' => 'CURRENT_TIMESTAMP',
            'current_date' => 'CURRENT_DATE',
            'current_time' => 'CURRENT_TIME',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQLite Migration Generation
    |--------------------------------------------------------------------------
    |
    | Settings for generating migrations from SQLite schemas
    |
    */
    'migration_generation' => [
        'preserve_constraints' => true,
        'include_indexes' => true,
        'include_foreign_keys' => true,
        'include_check_constraints' => true,
        'include_default_values' => true,
        'preserve_collation' => true,
        
        'schema_reconstruction' => [
            'parse_create_table' => true,
            'extract_constraints' => true,
            'rebuild_from_pragma' => true,
        ],
        
        'limitations' => [
            'no_alter_column' => true, // SQLite has limited ALTER TABLE support
            'no_drop_column' => false, // Supported in 3.35+
            'recreate_table_strategy' => true,
        ],
        
        'unsupported_features' => [
            'stored_procedures',
            'user_defined_functions',
            'triggers', // Will be supported in future versions
            'views', // Will be supported in future versions
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQLite Extensions
    |--------------------------------------------------------------------------
    |
    | Common SQLite extensions and their handling
    |
    */
    'extensions' => [
        'json1' => [
            'auto_detect' => true,
            'required_version' => '3.38.0',
            'functions' => [
                'json',
                'json_array',
                'json_object',
                'json_extract',
                'json_insert',
                'json_replace',
                'json_set',
                'json_remove',
                'json_type',
                'json_valid',
            ],
        ],
        
        'fts5' => [
            'auto_detect' => true,
            'full_text_search' => true,
            'table_pattern' => '_fts',
        ],
        
        'rtree' => [
            'auto_detect' => true,
            'spatial_indexing' => true,
        ],
        
        'uuid' => [
            'auto_detect' => false,
            'extension_required' => false, // Can use built-in functions
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQLite Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing
    |
    */
    'development' => [
        'explain_query_plan' => true,
        'enable_foreign_keys' => true,
        'enable_triggers' => true,
        'recursive_triggers' => false,
        
        'testing' => [
            'in_memory_database' => true,
            'fast_migrations' => true,
            'skip_foreign_keys' => false,
        ],
    ],
];