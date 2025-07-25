<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Code Generator Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AutoGen Code Generator package.
    |
    */

    'enabled' => env('AUTOGEN_CODE_GENERATOR_ENABLED', true),

    'generators' => [
        'model' => [
            'enabled' => true,
            'template' => 'model',
            'namespace' => 'App\\Models',
            'path' => 'app/Models',
        ],
        'controller' => [
            'enabled' => true,
            'template' => 'controller',
            'namespace' => 'App\\Http\\Controllers',
            'path' => 'app/Http/Controllers',
        ],
        'service' => [
            'enabled' => true,
            'template' => 'service',
            'namespace' => 'App\\Services',
            'path' => 'app/Services',
        ],
        'repository' => [
            'enabled' => true,
            'template' => 'repository',
            'namespace' => 'App\\Repositories',
            'path' => 'app/Repositories',
        ],
    ],

    'ai_prompts' => [
        'model' => 'Generate a Laravel Eloquent model for {name} with the following attributes: {attributes}',
        'controller' => 'Generate a Laravel controller for {name} with CRUD operations',
        'service' => 'Generate a service class for {name} with business logic methods',
        'repository' => 'Generate a repository class for {name} with data access methods',
    ],

    'validation' => [
        'strict_typing' => true,
        'psr_compliance' => true,
        'naming_conventions' => true,
    ],
];