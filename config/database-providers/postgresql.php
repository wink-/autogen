<?php

declare(strict_types=1);

/**
 * PostgreSQL Database Provider Configuration for AutoGen
 * 
 * This configuration file contains PostgreSQL-specific settings
 * for the AutoGen package suite.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Connection Templates
    |--------------------------------------------------------------------------
    |
    | Pre-configured connection templates for common PostgreSQL setups
    |
    */
    'connection_templates' => [
        'local' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
        
        'legacy' => [
            'driver' => 'pgsql',
            'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
            'port' => env('LEGACY_DB_PORT', '5432'),
            'database' => env('LEGACY_DB_DATABASE'),
            'username' => env('LEGACY_DB_USERNAME'),
            'password' => env('LEGACY_DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => env('LEGACY_DB_PREFIX', ''),
            'search_path' => env('LEGACY_DB_SCHEMA', 'public'),
            'sslmode' => 'disable',
        ],
        
        'production' => [
            'driver' => 'pgsql',
            'read' => [
                'host' => [
                    env('DB_READ_HOST_1', '127.0.0.1'),
                    env('DB_READ_HOST_2', '127.0.0.1'),
                ],
            ],
            'write' => [
                'host' => [
                    env('DB_WRITE_HOST', '127.0.0.1'),
                ],
            ],
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'search_path' => 'public',
            'sslmode' => 'require',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Type Mappings
    |--------------------------------------------------------------------------
    |
    | Maps PostgreSQL column types to Laravel cast types and validation rules
    |
    */
    'type_mappings' => [
        'cast_mappings' => [
            'boolean' => 'boolean',
            'bool' => 'boolean',
            'smallint' => 'integer',
            'int2' => 'integer',
            'integer' => 'integer',
            'int' => 'integer',
            'int4' => 'integer',
            'bigint' => 'integer',
            'int8' => 'integer',
            'decimal' => 'decimal:2',
            'numeric' => 'decimal:2',
            'real' => 'float',
            'float4' => 'float',
            'double precision' => 'double',
            'float8' => 'double',
            'char' => 'string',
            'character' => 'string',
            'varchar' => 'string',
            'character varying' => 'string',
            'text' => 'string',
            'uuid' => 'string',
            'json' => 'array',
            'jsonb' => 'array',
            'xml' => 'string',
            'date' => 'date',
            'timestamp' => 'datetime',
            'timestamptz' => 'datetime',
            'timestamp with time zone' => 'datetime',
            'timestamp without time zone' => 'datetime',
            'time' => 'string',
            'timetz' => 'string',
            'time with time zone' => 'string',
            'time without time zone' => 'string',
            'interval' => 'string',
            'bytea' => 'string',
            'inet' => 'string',
            'cidr' => 'string',
            'macaddr' => 'string',
            'point' => 'string',
            'line' => 'string',
            'lseg' => 'string',
            'box' => 'string',
            'path' => 'string',
            'polygon' => 'string',
            'circle' => 'string',
            'money' => 'decimal:2',
            'bit' => 'string',
            'bit varying' => 'string',
            'varbit' => 'string',
            'tsvector' => 'string',
            'tsquery' => 'string',
        ],
        
        'validation_mappings' => [
            'boolean' => 'boolean',
            'smallint' => 'integer|min:-32768|max:32767',
            'integer' => 'integer|min:-2147483648|max:2147483647',
            'bigint' => 'integer',
            'decimal' => 'numeric',
            'numeric' => 'numeric',
            'real' => 'numeric',
            'double precision' => 'numeric',
            'char' => 'string|size:{length}',
            'varchar' => 'string|max:{length}',
            'text' => 'string',
            'uuid' => 'uuid',
            'json' => 'json',
            'jsonb' => 'json',
            'date' => 'date',
            'timestamp' => 'date',
            'timestamptz' => 'date',
            'time' => 'date_format:H:i:s',
            'inet' => 'ip',
            'email' => 'email',
        ],

        'faker_mappings' => [
            'boolean' => 'boolean',
            'smallint' => 'numberBetween(-32768, 32767)',
            'integer' => 'randomNumber()',
            'bigint' => 'randomNumber()',
            'decimal' => 'randomFloat(2, 0, 999999)',
            'numeric' => 'randomFloat(2, 0, 999999)',
            'real' => 'randomFloat()',
            'double precision' => 'randomFloat()',
            'varchar' => 'text({length})',
            'text' => 'paragraph',
            'uuid' => 'uuid',
            'date' => 'date',
            'timestamp' => 'dateTime',
            'timestamptz' => 'dateTime',
            'time' => 'time',
            'inet' => 'ipv4',
            'email' => 'email',
            'json' => 'json',
            'jsonb' => 'json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Introspection Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to PostgreSQL database introspection
    |
    */
    'introspection' => [
        'information_schema' => [
            'enabled' => true,
            'schema' => 'information_schema',
            'tables' => [
                'columns' => 'columns',
                'tables' => 'tables',
                'key_column_usage' => 'key_column_usage',
                'referential_constraints' => 'referential_constraints',
                'table_constraints' => 'table_constraints',
                'constraint_column_usage' => 'constraint_column_usage',
            ],
        ],
        
        'system_catalogs' => [
            'enabled' => true,
            'pg_class' => 'pg_class',
            'pg_attribute' => 'pg_attribute',
            'pg_constraint' => 'pg_constraint',
            'pg_index' => 'pg_index',
            'pg_type' => 'pg_type',
            'pg_namespace' => 'pg_namespace',
        ],
        
        'relationship_detection' => [
            'foreign_key_query' => "
                SELECT
                    tc.constraint_name,
                    tc.table_name,
                    kcu.column_name,
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_schema = ?
            ",
            'index_query' => "
                SELECT
                    i.relname AS index_name,
                    t.relname AS table_name,
                    a.attname AS column_name,
                    ix.indisunique AS is_unique,
                    ix.indisprimary AS is_primary
                FROM pg_class t
                JOIN pg_index ix ON t.oid = ix.indrelid
                JOIN pg_class i ON i.oid = ix.indexrelid
                JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
                JOIN pg_namespace n ON n.oid = t.relnamespace
                WHERE n.nspname = ?
            ",
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | Performance-specific settings for PostgreSQL
    |
    */
    'performance' => [
        'query_optimizations' => [
            'use_indexes' => true,
            'avoid_select_star' => true,
            'use_limits' => true,
            'use_exists_over_in' => true,
            'use_lateral_joins' => true,
            'use_window_functions' => true,
        ],
        
        'index_recommendations' => [
            'auto_suggest' => true,
            'composite_threshold' => 3,
            'covering_indexes' => false, // PostgreSQL doesn't have covering indexes like SQL Server
            'partial_indexes' => true,
            'expression_indexes' => true,
            'gin_gist_indexes' => true,
        ],
        
        'connection_optimization' => [
            'persistent_connections' => false,
            'connection_pooling' => true,
            'prepared_statements' => true,
            'vacuum_analyze' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Version Compatibility
    |--------------------------------------------------------------------------
    |
    | Version-specific feature detection and compatibility
    |
    */
    'version_compatibility' => [
        '12' => [
            'json_support' => true,
            'jsonb_support' => true,
            'generated_columns' => true,
            'common_table_expressions' => true,
            'window_functions' => true,
            'check_constraints' => true,
            'partial_indexes' => true,
            'expression_indexes' => true,
        ],
        
        '13' => [
            'json_support' => true,
            'jsonb_support' => true,
            'generated_columns' => true,
            'common_table_expressions' => true,
            'window_functions' => true,
            'check_constraints' => true,
            'partial_indexes' => true,
            'expression_indexes' => true,
            'extended_statistics' => true,
        ],
        
        '14' => [
            'json_support' => true,
            'jsonb_support' => true,
            'generated_columns' => true,
            'common_table_expressions' => true,
            'window_functions' => true,
            'check_constraints' => true,
            'partial_indexes' => true,
            'expression_indexes' => true,
            'extended_statistics' => true,
            'multirange_types' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Specific Features
    |--------------------------------------------------------------------------
    |
    | PostgreSQL-specific features and their handling
    |
    */
    'features' => [
        'sequences' => [
            'auto_detection' => true,
            'naming_pattern' => '{table}_{column}_seq',
        ],
        
        'timestamps' => [
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
            'auto_detection' => true,
            'timezone_aware' => true,
        ],
        
        'soft_deletes' => [
            'column_name' => 'deleted_at',
            'auto_detection' => true,
        ],
        
        'json_columns' => [
            'json_support' => true,
            'jsonb_support' => true,
            'auto_detection' => true,
            'cast_to_array' => true,
            'json_operators' => true,
        ],
        
        'uuid_columns' => [
            'auto_detection' => true,
            'extension_required' => 'uuid-ossp',
            'cast_to_string' => true,
        ],
        
        'array_columns' => [
            'auto_detection' => true,
            'cast_to_array' => true,
        ],
        
        'full_text_search' => [
            'tsvector_support' => true,
            'tsquery_support' => true,
            'auto_detection' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Migration Generation
    |--------------------------------------------------------------------------
    |
    | Settings for generating migrations from PostgreSQL schemas
    |
    */
    'migration_generation' => [
        'preserve_schemas' => true,
        'include_indexes' => true,
        'include_foreign_keys' => true,
        'include_check_constraints' => true,
        'include_comments' => true,
        'include_sequences' => true,
        
        'extensions' => [
            'auto_detect' => true,
            'common_extensions' => [
                'uuid-ossp',
                'pgcrypto',
                'hstore',
                'postgis',
                'pg_trgm',
                'btree_gin',
                'btree_gist',
            ],
        ],
        
        'unsupported_features' => [
            'rules',
            'triggers', // Will be supported in future versions
            'stored_procedures', // Will be supported in future versions
            'views', // Will be supported in future versions
            'materialized_views',
            'partitions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL Security Features
    |--------------------------------------------------------------------------
    |
    | Security-related settings and features
    |
    */
    'security' => [
        'row_level_security' => [
            'auto_detection' => true,
            'policy_generation' => false, // Future feature
        ],
        
        'column_encryption' => [
            'pgcrypto_support' => true,
            'auto_detection_patterns' => [
                'encrypted_',
                '_encrypted',
                '_cipher',
            ],
        ],
    ],
];