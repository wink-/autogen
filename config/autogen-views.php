<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Framework
    |--------------------------------------------------------------------------
    |
    | This option defines the default CSS framework to use when generating
    | views. Supported frameworks: "tailwind", "bootstrap", "css"
    |
    */
    'default_framework' => env('AUTOGEN_VIEWS_FRAMEWORK', 'tailwind'),

    /*
    |--------------------------------------------------------------------------
    | Default Layout
    |--------------------------------------------------------------------------
    |
    | The default layout template that views will extend.
    |
    */
    'default_layout' => env('AUTOGEN_VIEWS_LAYOUT', 'layouts.app'),

    /*
    |--------------------------------------------------------------------------
    | View Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace prefix for generated views. This helps organize views
    | for different model directories.
    |
    */
    'view_namespace' => '',

    /*
    |--------------------------------------------------------------------------
    | Custom Stubs Path
    |--------------------------------------------------------------------------
    |
    | Path to custom stub files. If specified, these will be used instead
    | of the default stubs. Set to null to use built-in stubs.
    |
    */
    'custom_stubs_path' => resource_path('stubs/autogen/views'),

    /*
    |--------------------------------------------------------------------------
    | Framework Configurations
    |--------------------------------------------------------------------------
    |
    | Framework-specific configuration options.
    |
    */
    'frameworks' => [
        'tailwind' => [
            'cdn_url' => 'https://cdn.tailwindcss.com',
            'include_cdn' => false, // Set to true to include CDN link in views
        ],
        
        'bootstrap' => [
            'version' => '5.3.0',
            'cdn_css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            'cdn_js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            'include_cdn' => false, // Set to true to include CDN links in views
        ],
        
        'css' => [
            'css_file' => '/css/autogen/crud.css',
            'generate_css' => true, // Generate CSS file
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | View Options
    |--------------------------------------------------------------------------
    |
    | Default options for view generation.
    |
    */
    'options' => [
        'with_datatable' => false,
        'with_search' => true,
        'with_modals' => true,
        'responsive' => true,
        'accessibility' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for table generation.
    |
    */
    'table' => [
        'max_columns' => 6, // Maximum columns to show in table view
        'pagination' => 15, // Default pagination size
        'show_id' => true, // Show ID column
        'show_timestamps' => true, // Show created_at/updated_at
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for form generation.
    |
    */
    'form' => [
        'required_fields' => [
            // Fields that should be marked as required by default
            'name', 'title', 'email'
        ],
        'hidden_fields' => [
            // Fields to hide from forms
            'password', 'remember_token', 'email_verified_at'
        ],
        'textarea_fields' => [
            // Fields that should use textarea instead of input
            'description', 'content', 'body', 'notes', 'bio'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Type Mappings
    |--------------------------------------------------------------------------
    |
    | Mapping of database column types to HTML input types.
    |
    */
    'field_type_mappings' => [
        'string' => 'text',
        'text' => 'textarea',
        'integer' => 'number',
        'bigint' => 'number',
        'decimal' => 'number',
        'float' => 'number',
        'double' => 'number',
        'boolean' => 'checkbox',
        'date' => 'date',
        'datetime' => 'datetime-local',
        'timestamp' => 'datetime-local',
        'time' => 'time',
        'json' => 'textarea',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for different field types.
    |
    */
    'validation_rules' => [
        'email' => 'required|email|max:255',
        'password' => 'required|min:8',
        'url' => 'nullable|url|max:255',
        'phone' => 'nullable|string|max:20',
        'text' => 'required|string|max:255',
        'textarea' => 'nullable|string',
        'number' => 'required|numeric',
        'boolean' => 'boolean',
        'date' => 'nullable|date',
        'datetime' => 'nullable|date',
    ],

    /*
    |--------------------------------------------------------------------------
    | Icons
    |--------------------------------------------------------------------------
    |
    | Icon mappings for different actions and states.
    |
    */
    'icons' => [
        'add' => 'plus',
        'edit' => 'pencil',
        'delete' => 'trash',
        'view' => 'eye',
        'search' => 'search',
        'filter' => 'filter',
        'save' => 'check',
        'cancel' => 'x',
    ],

    /*
    |--------------------------------------------------------------------------
    | Accessibility
    |--------------------------------------------------------------------------
    |
    | Accessibility configuration options.
    |
    */
    'accessibility' => [
        'include_labels' => true,
        'include_descriptions' => true,
        'include_aria_attributes' => true,
        'keyboard_navigation' => true,
        'screen_reader_support' => true,
    ],
];