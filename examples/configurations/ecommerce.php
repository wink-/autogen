<?php

/**
 * E-commerce Configuration Example
 * 
 * This configuration demonstrates how to set up AutoGen
 * for a typical e-commerce application with multiple
 * database connections and optimized settings.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | E-commerce Database Connections
    |--------------------------------------------------------------------------
    */
    'database' => [
        'default_connection' => 'ecommerce_main',
        'legacy_connections' => [
            'ecommerce_main' => [
                'driver' => 'mysql',
                'host' => env('ECOMMERCE_DB_HOST', '127.0.0.1'),
                'port' => env('ECOMMERCE_DB_PORT', '3306'),
                'database' => env('ECOMMERCE_DB_DATABASE', 'ecommerce'),
                'username' => env('ECOMMERCE_DB_USERNAME', 'root'),
                'password' => env('ECOMMERCE_DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
            'analytics' => [
                'driver' => 'mysql',
                'host' => env('ANALYTICS_DB_HOST', '127.0.0.1'),
                'database' => env('ANALYTICS_DB_DATABASE', 'ecommerce_analytics'),
                'username' => env('ANALYTICS_DB_USERNAME', 'analytics'),
                'password' => env('ANALYTICS_DB_PASSWORD', ''),
            ],
        ],
        'introspection' => [
            'cache_schema' => true,
            'cache_ttl' => 7200, // 2 hours for stable schemas
            'exclude_columns' => ['password', 'remember_token', 'api_token'],
            'max_table_scan' => 150, // Large product catalogs
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | E-commerce Specific Settings
    |--------------------------------------------------------------------------
    */
    'tables_to_ignore' => [
        'migrations',
        'failed_jobs',
        'password_resets',
        'sessions',
        'cache',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'analytics_events', // Handle separately
        'search_indexes', // Generated tables
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization for E-commerce
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'enable_query_optimization' => true,
        'enable_caching' => true,
        'cache_driver' => 'redis',
        'cache_prefix' => 'ecommerce_autogen',
        'eager_load_relationships' => true,
        'use_database_transactions' => true,
        'chunk_size' => 2000, // Larger chunks for product imports
        'pagination_size' => 25, // Better for product listings
    ],

    /*
    |--------------------------------------------------------------------------
    | E-commerce Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'enable_authorization' => true,
        'generate_policies' => true,
        'mass_assignment_protection' => true,
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 120, // Higher for API usage
            'decay_minutes' => 1,
        ],
        'sensitive_fields' => [
            'password',
            'remember_token',
            'api_token',
            'payment_token',
            'stripe_customer_id',
            'credit_card_last_four',
            'billing_address',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | E-commerce Package Configuration
    |--------------------------------------------------------------------------
    */
    'packages' => [
        'model' => [
            'enabled' => true,
            'generate_traits' => true,
            'generate_scopes' => true, // Useful for product filtering
            'generate_mutators' => true,
            'generate_accessors' => true,
            'soft_deletes_detection' => true,
            'uuid_detection' => true,
        ],
        'controller' => [
            'enabled' => true,
            'default_type' => 'resource',
            'generate_form_requests' => true,
            'generate_policies' => true,
            'middleware' => [
                'web' => ['auth', 'verified'],
                'api' => ['auth:sanctum', 'throttle:api'],
                'admin' => ['auth', 'role:admin'],
            ],
        ],
        'views' => [
            'enabled' => true,
            'default_framework' => 'tailwind',
            'generate_layouts' => false, // Use existing e-commerce layouts
            'include_assets' => true,
            'responsive_design' => true,
            'accessibility_features' => true,
        ],
        'datatable' => [
            'enabled' => true,
            'default_type' => 'yajra',
            'server_side' => true,
            'export_enabled' => true,
            'search_enabled' => true,
            'filter_enabled' => true,
            'performance' => [
                'chunk_size' => 1000,
                'max_export_rows' => 100000, // Large product exports
                'cache_ttl' => 900, // 15 minutes
                'use_cursor_pagination' => true,
            ],
        ],
        'factory' => [
            'enabled' => true,
            'generate_states' => true,
            'realistic_data' => true,
            'relationship_factories' => true,
            'locale' => 'en_US',
            'ecommerce_specific' => [
                'generate_product_variations' => true,
                'realistic_prices' => true,
                'inventory_management' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | E-commerce Validation Rules
    |--------------------------------------------------------------------------
    */
    'validation_rules' => [
        'price' => 'numeric|min:0|max:999999.99',
        'quantity' => 'integer|min:0',
        'sku' => 'string|max:50|unique:products,sku',
        'email' => 'email|max:255',
        'phone' => 'string|max:20',
        'postal_code' => 'string|max:10',
        'status' => 'in:active,inactive,discontinued',
        'weight' => 'numeric|min:0',
        'dimensions' => 'string|max:100',
    ],

    /*
    |--------------------------------------------------------------------------
    | E-commerce Naming Conventions
    |--------------------------------------------------------------------------
    */
    'naming_conventions' => [
        'model_suffix' => '',
        'controller_suffix' => 'Controller',
        'request_suffix' => 'Request',
        'resource_suffix' => 'Resource',
        'policy_suffix' => 'Policy',
        'factory_suffix' => 'Factory',
        'test_suffix' => 'Test',
        'datatable_suffix' => 'DataTable',
    ],

    /*
    |--------------------------------------------------------------------------
    | E-commerce Relationships
    |--------------------------------------------------------------------------
    */
    'relationships' => [
        'detect_belongsto' => true,
        'detect_hasmany' => true,
        'detect_manytomany' => true,
        'foreign_key_suffix' => '_id',
        'pivot_table_pattern' => '{model1}_{model2}',
        'morph_prefix' => 'able',
        'ecommerce_patterns' => [
            'product_categories' => 'many_to_many',
            'product_attributes' => 'polymorphic',
            'order_items' => 'has_many_through',
            'customer_addresses' => 'morph_many',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | E-commerce AI Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'default_provider' => env('AUTOGEN_AI_PROVIDER', 'openai'),
        'ecommerce_prompts' => [
            'product_descriptions' => 'Generate compelling product descriptions that highlight features and benefits',
            'category_names' => 'Suggest appropriate category names for e-commerce products',
            'validation_rules' => 'Create validation rules for e-commerce specific fields',
        ],
    ],
];