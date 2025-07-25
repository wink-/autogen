<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tables to Ignore
    |--------------------------------------------------------------------------
    |
    | These tables will be ignored across all generators. Add any system
    | tables or tables you don't want to generate models for.
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
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Model Class
    |--------------------------------------------------------------------------
    |
    | The base model class that generated models will extend.
    |
    */
    'base_model' => \Illuminate\Database\Eloquent\Model::class,

    /*
    |--------------------------------------------------------------------------
    | Naming Conventions
    |--------------------------------------------------------------------------
    |
    | Configure how generated class names are formatted.
    |
    */
    'naming_conventions' => [
        'model_suffix' => '',
        'controller_suffix' => 'Controller',
        'request_suffix' => 'Request',
        'resource_suffix' => 'Resource',
        'policy_suffix' => 'Policy',
        'factory_suffix' => 'Factory',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    |
    | Default configuration options for generators.
    |
    */
    'defaults' => [
        'views_framework' => 'tailwindcss',
        'controller_type' => 'resource',
        'pagination' => 15,
        'api_version' => 'v1',
        'generate_relationships' => true,
        'generate_validation' => true,
        'generate_scopes' => true,
        'generate_traits' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Column Type Mappings
    |--------------------------------------------------------------------------
    |
    | Map database column types to Laravel cast types.
    |
    */
    'column_type_mappings' => [
        'tinyint(1)' => 'boolean',
        'json' => 'array',
        'jsonb' => 'array',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
        'tinytext' => 'string',
        'smallint' => 'integer',
        'mediumint' => 'integer',
        'bigint' => 'integer',
        'decimal' => 'decimal:2',
        'numeric' => 'decimal:2',
        'float' => 'float',
        'double' => 'double',
        'real' => 'float',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'time' => 'string',
        'year' => 'integer',
        'enum' => 'string',
        'set' => 'array',
        'uuid' => 'string',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rule Mappings
    |--------------------------------------------------------------------------
    |
    | Map database column types and names to Laravel validation rules.
    |
    */
    'validation_rules' => [
        // Type-based rules
        'varchar' => 'string|max:{length}',
        'char' => 'string|size:{length}',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
        'tinytext' => 'string',
        'integer' => 'integer',
        'int' => 'integer',
        'tinyint' => 'integer|min:0|max:255',
        'smallint' => 'integer',
        'mediumint' => 'integer',
        'bigint' => 'integer',
        'decimal' => 'numeric',
        'numeric' => 'numeric',
        'float' => 'numeric',
        'double' => 'numeric',
        'real' => 'numeric',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'date' => 'date',
        'datetime' => 'date',
        'timestamp' => 'date',
        'time' => 'date_format:H:i:s',
        'year' => 'integer|min:1901|max:2155',
        'json' => 'json',
        'jsonb' => 'json',
        'enum' => 'in:{values}',
        'set' => 'array',
        'uuid' => 'uuid',
        
        // Name-based rules (will override type-based if column name matches)
        'email' => 'email|max:255',
        'password' => 'string|min:8',
        'phone' => 'string|max:20',
        'url' => 'url|max:255',
        'slug' => 'alpha_dash|max:255',
        'ip' => 'ip',
        'mac_address' => 'mac_address',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should be hidden by default in model $hidden property.
    |
    */
    'hidden_fields' => [
        'password',
        'remember_token',
        'api_token',
        'token',
        'secret',
        'private_key',
        'access_token',
        'refresh_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Stubs Path
    |--------------------------------------------------------------------------
    |
    | Path to custom stub templates. If null, default stubs will be used.
    |
    */
    'custom_stubs_path' => null, // e.g., resource_path('stubs/autogen')

    /*
    |--------------------------------------------------------------------------
    | Relationship Detection
    |--------------------------------------------------------------------------
    |
    | Configure how relationships are detected and generated.
    |
    */
    'relationships' => [
        'detect_belongs_to' => true,
        'detect_has_many' => true,
        'detect_many_to_many' => true,
        'detect_polymorphic' => true,
        'foreign_key_suffix' => '_id',
        'morph_suffix' => '_type',
        
        // Patterns for detecting polymorphic relationships
        'morph_patterns' => [
            '*able_type' => '*able_id',
            '*_type' => '*_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Traits Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which traits to automatically add to models.
    |
    */
    'traits' => [
        'has_factory' => true,
        'soft_deletes' => 'auto', // true, false, or 'auto' to detect
        'timestamps' => 'auto', // true, false, or 'auto' to detect
        
        // Custom traits based on column detection
        'custom_traits' => [
            // If 'uuid' column exists, add Uuids trait
            'uuid' => '\\Illuminate\\Database\\Eloquent\\Concerns\\HasUuids',
            // If 'slug' column exists, add Sluggable trait (if package installed)
            'slug' => '\\Spatie\\Sluggable\\HasSlug',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope Generation
    |--------------------------------------------------------------------------
    |
    | Configure which scopes to automatically generate based on column names.
    |
    */
    'scopes' => [
        'generate_common_scopes' => true,
        
        // Column name patterns and their corresponding scopes
        'scope_patterns' => [
            'status' => 'status',
            'is_active' => 'active',
            'is_published' => 'published',
            'published_at' => 'published',
            'is_featured' => 'featured',
            'is_verified' => 'verified',
            'type' => 'ofType',
            'category' => 'category',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Accessor/Mutator Generation
    |--------------------------------------------------------------------------
    |
    | Configure which accessors and mutators to automatically generate.
    |
    */
    'accessors_mutators' => [
        'generate_common_accessors' => true,
        'generate_common_mutators' => true,
        
        // Common accessor patterns
        'accessor_patterns' => [
            ['first_name', 'last_name'] => 'full_name',
            ['street', 'city', 'state', 'zip'] => 'full_address',
        ],
        
        // Common mutator patterns
        'mutator_patterns' => [
            'password' => 'hash',
            'email' => 'lowercase',
            'slug' => 'slugify',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PHP 8.3+ Features
    |--------------------------------------------------------------------------
    |
    | Configure usage of modern PHP features in generated models.
    |
    */
    'php_features' => [
        'use_typed_properties' => true,
        'use_readonly_properties' => true,
        'use_typed_constants' => true,
        'use_property_hooks' => false, // Experimental
        'use_strict_types' => true,
        'use_match_expressions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Generation
    |--------------------------------------------------------------------------
    |
    | Configure PHPDoc generation for models.
    |
    */
    'documentation' => [
        'generate_property_docs' => true,
        'generate_relationship_docs' => true,
        'generate_scope_docs' => true,
        'include_index_documentation' => true,
        'include_foreign_key_docs' => true,
        'generate_ide_helper_docs' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | Configure performance-related features.
    |
    */
    'performance' => [
        'eager_load_relationships' => true,
        'generate_query_optimizations' => true,
        'suggest_indexes' => true,
        'cache_model_metadata' => true,
    ],
];