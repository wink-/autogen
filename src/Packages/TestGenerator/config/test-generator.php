<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Test Generator Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AutoGen Test Generator package.
    |
    */

    'enabled' => env('AUTOGEN_TEST_GENERATOR_ENABLED', true),

    'test_framework' => env('TEST_FRAMEWORK', 'pest'), // pest, phpunit

    'generators' => [
        'unit' => [
            'enabled' => true,
            'path' => 'tests/Unit',
            'namespace' => 'Tests\\Unit',
            'template' => 'unit-test',
        ],
        'feature' => [
            'enabled' => true,
            'path' => 'tests/Feature',
            'namespace' => 'Tests\\Feature',
            'template' => 'feature-test',
        ],
        'integration' => [
            'enabled' => true,
            'path' => 'tests/Integration',
            'namespace' => 'Tests\\Integration',
            'template' => 'integration-test',
        ],
    ],

    'coverage' => [
        'target_percentage' => 80,
        'enforce_coverage' => false,
        'exclude_paths' => [
            'vendor',
            'bootstrap',
            'config',
            'database/migrations',
        ],
    ],

    'ai_prompts' => [
        'unit' => 'Generate comprehensive unit tests for the following class: {class}',
        'feature' => 'Generate feature tests for the following Laravel feature: {feature}',
        'integration' => 'Generate integration tests for the following system: {system}',
    ],
];