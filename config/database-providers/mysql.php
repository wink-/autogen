<?php

declare(strict_types=1);

/**
 * MySQL Database Provider Configuration for AutoGen
 * 
 * This configuration file contains MySQL-specific settings
 * for the AutoGen package suite.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | MySQL Connection Templates
    |--------------------------------------------------------------------------
    |
    | Pre-configured connection templates for common MySQL setups
    |
    */
    'connection_templates' => [
        'local' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
        
        'legacy' => [
            'driver' => 'mysql',
            'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
            'port' => env('LEGACY_DB_PORT', '3306'),
            'database' => env('LEGACY_DB_DATABASE'),
            'username' => env('LEGACY_DB_USERNAME'),
            'password' => env('LEGACY_DB_PASSWORD'),
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix' => env('LEGACY_DB_PREFIX', ''),
            'strict' => false, // Often needed for legacy systems
            'engine' => 'MyISAM', // Legacy systems might use MyISAM
        ],
        
        'production' => [
            'driver' => 'mysql',
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
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Type Mappings
    |--------------------------------------------------------------------------
    |
    | Maps MySQL column types to Laravel cast types and validation rules
    |
    */
    'type_mappings' => [
        'cast_mappings' => [
            'tinyint(1)' => 'boolean',
            'tinyint' => 'integer',
            'smallint' => 'integer',
            'mediumint' => 'integer',
            'int' => 'integer',
            'integer' => 'integer',
            'bigint' => 'integer',
            'decimal' => 'decimal:2',
            'numeric' => 'decimal:2',
            'float' => 'float',
            'double' => 'double',
            'real' => 'double',
            'bit' => 'boolean',
            'char' => 'string',
            'varchar' => 'string',
            'tinytext' => 'string',
            'text' => 'string',
            'mediumtext' => 'string',
            'longtext' => 'string',
            'binary' => 'string',
            'varbinary' => 'string',
            'tinyblob' => 'string',
            'blob' => 'string',
            'mediumblob' => 'string',
            'longblob' => 'string',
            'enum' => 'string',
            'set' => 'array',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'datetime',
            'time' => 'string',
            'year' => 'integer',
            'json' => 'array',
            'geometry' => 'string',
            'point' => 'string',
            'linestring' => 'string',
            'polygon' => 'string',
            'multipoint' => 'string',
            'multilinestring' => 'string',
            'multipolygon' => 'string',
            'geometrycollection' => 'string',
        ],
        
        'validation_mappings' => [
            'tinyint(1)' => 'boolean',
            'tinyint' => 'integer|min:-128|max:127',
            'smallint' => 'integer|min:-32768|max:32767',
            'mediumint' => 'integer|min:-8388608|max:8388607',
            'int' => 'integer',
            'bigint' => 'integer',
            'decimal' => 'numeric',
            'float' => 'numeric',
            'double' => 'numeric',
            'char' => 'string|size:{length}',
            'varchar' => 'string|max:{length}',
            'text' => 'string|max:65535',
            'mediumtext' => 'string|max:16777215',
            'longtext' => 'string',
            'enum' => 'in:{values}',
            'date' => 'date',
            'datetime' => 'date',
            'timestamp' => 'date',
            'time' => 'date_format:H:i:s',
            'year' => 'integer|min:1901|max:2155',
            'json' => 'json',
        ],

        'faker_mappings' => [
            'tinyint(1)' => 'boolean',
            'tinyint' => 'numberBetween(-128, 127)',
            'smallint' => 'numberBetween(-32768, 32767)',
            'int' => 'randomNumber()',
            'bigint' => 'randomNumber()',
            'decimal' => 'randomFloat(2, 0, 999999)',
            'float' => 'randomFloat()',
            'varchar' => 'text({length})',
            'text' => 'paragraph',
            'enum' => 'randomElement({values})',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'dateTime',
            'time' => 'time',
            'year' => 'year',
            'json' => 'json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Introspection Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to MySQL database introspection
    |
    */
    'introspection' => [
        'information_schema' => [
            'enabled' => true,
            'database' => 'INFORMATION_SCHEMA',
            'tables' => [
                'columns' => 'COLUMNS',
                'tables' => 'TABLES',
                'key_column_usage' => 'KEY_COLUMN_USAGE',
                'referential_constraints' => 'REFERENTIAL_CONSTRAINTS',
                'statistics' => 'STATISTICS',
            ],
        ],
        
        'show_commands' => [
            'tables' => 'SHOW TABLES',
            'columns' => 'SHOW COLUMNS FROM {table}',
            'indexes' => 'SHOW INDEXES FROM {table}',
            'create_table' => 'SHOW CREATE TABLE {table}',
        ],
        
        'relationship_detection' => [
            'foreign_key_pattern' => '/CONSTRAINT `(.+)` FOREIGN KEY \(`(.+)`\) REFERENCES `(.+)` \(`(.+)`\)/',
            'index_pattern' => '/KEY `(.+)` \(`(.+)`\)/',
            'unique_pattern' => '/UNIQUE KEY `(.+)` \(`(.+)`\)/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | Performance-specific settings for MySQL
    |
    */
    'performance' => [
        'query_optimizations' => [
            'use_indexes' => true,
            'avoid_select_star' => true,
            'use_limits' => true,
            'use_exists_over_in' => true,
        ],
        
        'index_recommendations' => [
            'auto_suggest' => true,
            'composite_threshold' => 3,
            'covering_indexes' => true,
            'partial_indexes' => false, // MySQL doesn't support partial indexes
        ],
        
        'connection_optimization' => [
            'persistent_connections' => false,
            'connection_pooling' => true,
            'query_cache' => true,
            'prepared_statements' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Version Compatibility
    |--------------------------------------------------------------------------
    |
    | Version-specific feature detection and compatibility
    |
    */
    'version_compatibility' => [
        '5.7' => [
            'json_support' => true,
            'generated_columns' => true,
            'common_table_expressions' => false,
            'window_functions' => false,
            'check_constraints' => false,
        ],
        
        '8.0' => [
            'json_support' => true,
            'generated_columns' => true,
            'common_table_expressions' => true,
            'window_functions' => true,
            'check_constraints' => true,
            'invisible_indexes' => true,
            'descending_indexes' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Specific Features
    |--------------------------------------------------------------------------
    |
    | MySQL-specific features and their handling
    |
    */
    'features' => [
        'auto_increment' => [
            'detection' => 'auto_increment',
            'handling' => 'exclude_from_fillable',
        ],
        
        'timestamps' => [
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
            'auto_detection' => true,
        ],
        
        'soft_deletes' => [
            'column_name' => 'deleted_at',
            'auto_detection' => true,
        ],
        
        'json_columns' => [
            'auto_detection' => true,
            'cast_to_array' => true,
        ],
        
        'enum_columns' => [
            'auto_detection' => true,
            'validation_rules' => true,
            'cast_to_string' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MySQL Migration Generation
    |--------------------------------------------------------------------------
    |
    | Settings for generating migrations from MySQL schemas
    |
    */
    'migration_generation' => [
        'preserve_engine' => true,
        'preserve_charset' => true,
        'preserve_collation' => true,
        'include_indexes' => true,
        'include_foreign_keys' => true,
        'include_comments' => true,
        
        'unsupported_features' => [
            'partitioning',
            'triggers',
            'stored_procedures',
            'views',
            'events',
        ],
    ],
];