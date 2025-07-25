<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tables to Ignore
    |--------------------------------------------------------------------------
    |
    | Tables that should be ignored across all generators
    |
    */
    'tables_to_ignore' => [
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
        'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Model Class
    |--------------------------------------------------------------------------
    |
    | The base model class that generated models should extend
    |
    */
    'base_model' => \Illuminate\Database\Eloquent\Model::class,

    /*
    |--------------------------------------------------------------------------
    | Naming Conventions
    |--------------------------------------------------------------------------
    |
    | Configure naming conventions for generated files
    |
    */
    'naming_conventions' => [
        'model_suffix' => '',
        'controller_suffix' => 'Controller',
        'request_suffix' => 'Request',
        'resource_suffix' => 'Resource',
        'policy_suffix' => 'Policy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    |
    | Default options for various generators
    |
    */
    'defaults' => [
        'views_framework' => 'tailwind',
        'controller_type' => 'resource',
        'pagination' => 15,
        'api_version' => 'v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Column Type Mappings
    |--------------------------------------------------------------------------
    |
    | Mappings for database column types to Laravel cast types
    |
    */
    'column_type_mappings' => [
        'tinyint(1)' => 'boolean',
        'json' => 'array',
        'jsonb' => 'array',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
        'smallint' => 'integer',
        'bigint' => 'integer',
        'decimal' => 'decimal:2',
        'float' => 'float',
        'double' => 'double',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'time' => 'string',
        'year' => 'integer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rule Mappings
    |--------------------------------------------------------------------------
    |
    | Default validation rules for different column types
    |
    */
    'validation_rules' => [
        'varchar' => 'string|max:{length}',
        'char' => 'string|size:{length}',
        'text' => 'string',
        'integer' => 'integer',
        'tinyint' => 'integer|min:0|max:255',
        'smallint' => 'integer',
        'bigint' => 'integer',
        'decimal' => 'numeric',
        'float' => 'numeric',
        'boolean' => 'boolean',
        'date' => 'date',
        'datetime' => 'date',
        'email' => 'email|max:255',
        'json' => 'json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Stubs Path
    |--------------------------------------------------------------------------
    |
    | Path to custom stub files. Set to null to use built-in stubs.
    |
    */
    'custom_stubs_path' => null, // e.g., resource_path('stubs/autogen')

    /*
    |--------------------------------------------------------------------------
    | Relationship Detection
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic relationship detection
    |
    */
    'relationships' => [
        'detect_belongsto' => true,
        'detect_hasmany' => true,
        'detect_manytomany' => true,
        'foreign_key_suffix' => '_id',
        'pivot_table_pattern' => '{model1}_{model2}',
        'morph_prefix' => 'able',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI providers used in code generation
    |
    */
    'ai' => [
        'default_provider' => env('AUTOGEN_AI_PROVIDER', 'openai'),
        'providers' => [
            'openai' => [
                'api_key' => env('OPENAI_API_KEY'),
                'model' => env('OPENAI_MODEL', 'gpt-4'),
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ],
            'anthropic' => [
                'api_key' => env('ANTHROPIC_API_KEY'),
                'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ],
            'gemini' => [
                'api_key' => env('GEMINI_API_KEY'),
                'model' => env('GEMINI_MODEL', 'gemini-pro'),
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ],
            'local' => [
                'endpoint' => env('LOCAL_LLM_ENDPOINT', 'http://localhost:11434'),
                'model' => env('LOCAL_LLM_MODEL', 'llama2'),
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling multiple database connections
    |
    */
    'database' => [
        'default_connection' => env('DB_CONNECTION', 'mysql'),
        'legacy_connections' => [
            // Example legacy connections
            // 'legacy_mysql' => [
            //     'driver' => 'mysql',
            //     'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
            //     'port' => env('LEGACY_DB_PORT', '3306'),
            //     'database' => env('LEGACY_DB_DATABASE'),
            //     'username' => env('LEGACY_DB_USERNAME'),
            //     'password' => env('LEGACY_DB_PASSWORD'),
            // ],
        ],
        'introspection' => [
            'cache_schema' => true,
            'cache_ttl' => 3600, // 1 hour
            'exclude_columns' => ['password', 'remember_token'],
            'max_table_scan' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Generation Settings
    |--------------------------------------------------------------------------
    |
    | Global settings for code generation across all packages
    |
    */
    'generation' => [
        'output_paths' => [
            'models' => 'app/Models',
            'controllers' => 'app/Http/Controllers',
            'requests' => 'app/Http/Requests',
            'resources' => 'app/Http/Resources',
            'policies' => 'app/Policies',
            'views' => 'resources/views',
            'factories' => 'database/factories',
            'migrations' => 'database/migrations',
            'seeders' => 'database/seeders',
            'tests' => 'tests',
        ],
        'namespaces' => [
            'models' => 'App\\Models',
            'controllers' => 'App\\Http\\Controllers',
            'requests' => 'App\\Http\\Requests',
            'resources' => 'App\\Http\\Resources',
            'policies' => 'App\\Policies',
            'factories' => 'Database\\Factories',
            'seeders' => 'Database\\Seeders',
            'tests' => 'Tests',
        ],
        'file_permissions' => 0644,
        'use_strict_types' => true,
        'add_docblocks' => true,
        'php_version' => '8.3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for optimizing generated code performance
    |
    */
    'performance' => [
        'enable_query_optimization' => true,
        'enable_caching' => true,
        'cache_driver' => env('CACHE_DRIVER', 'redis'),
        'cache_prefix' => 'autogen',
        'eager_load_relationships' => true,
        'use_database_transactions' => true,
        'chunk_size' => 1000,
        'pagination_size' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security configuration for generated code
    |
    */
    'security' => [
        'enable_authorization' => true,
        'generate_policies' => true,
        'mass_assignment_protection' => true,
        'sql_injection_protection' => true,
        'xss_protection' => true,
        'csrf_protection' => true,
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
        'sensitive_fields' => [
            'password',
            'remember_token',
            'api_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for code templates and stubs
    |
    */
    'templates' => [
        'use_custom_stubs' => false,
        'custom_stubs_path' => resource_path('stubs/autogen'),
        'template_engine' => 'blade', // blade, twig, mustache
        'variable_delimiters' => ['{{', '}}'],
        'preserve_formatting' => true,
        'minify_output' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Package-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Individual package configurations
    |
    */
    'packages' => [
        'model' => [
            'enabled' => true,
            'generate_traits' => true,
            'generate_scopes' => true,
            'generate_mutators' => true,
            'generate_accessors' => true,
            'soft_deletes_detection' => true,
            'uuid_detection' => true,
        ],
        'controller' => [
            'enabled' => true,
            'default_type' => 'resource', // resource, api, web
            'generate_form_requests' => true,
            'generate_policies' => true,
            'middleware' => ['auth', 'throttle:60,1'],
            'return_types' => true,
        ],
        'views' => [
            'enabled' => true,
            'default_framework' => 'tailwind', // tailwind, bootstrap, css
            'generate_layouts' => false,
            'include_assets' => true,
            'responsive_design' => true,
            'accessibility_features' => true,
        ],
        'migration' => [
            'enabled' => true,
            'include_foreign_keys' => true,
            'include_indexes' => true,
            'include_constraints' => true,
            'backup_existing' => true,
        ],
        'factory' => [
            'enabled' => true,
            'generate_states' => true,
            'realistic_data' => true,
            'relationship_factories' => true,
            'locale' => 'en_US',
        ],
        'datatable' => [
            'enabled' => true,
            'default_type' => 'yajra', // yajra, livewire, inertia
            'server_side' => true,
            'export_enabled' => true,
            'search_enabled' => true,
            'filter_enabled' => true,
        ],
        'test' => [
            'enabled' => true,
            'test_framework' => 'phpunit', // phpunit, pest
            'generate_feature_tests' => true,
            'generate_unit_tests' => true,
            'use_factories' => true,
            'use_database_transactions' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for logging generation activities
    |
    */
    'logging' => [
        'enabled' => true,
        'level' => env('AUTOGEN_LOG_LEVEL', 'info'),
        'channel' => env('AUTOGEN_LOG_CHANNEL', 'single'),
        'log_generation_time' => true,
        'log_file_changes' => true,
        'log_errors' => true,
    ],
];