<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Orchestrator Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AutoGen Workflow Orchestrator package.
    |
    */

    'enabled' => env('AUTOGEN_WORKFLOW_ORCHESTRATOR_ENABLED', true),

    'workflows' => [
        'full_stack_generation' => [
            'enabled' => true,
            'steps' => [
                'generate_model',
                'generate_migration',
                'generate_controller',
                'generate_service',
                'generate_repository',
                'generate_tests',
                'generate_documentation',
            ],
            'parallel_execution' => false,
            'rollback_on_failure' => true,
        ],
        'testing_pipeline' => [
            'enabled' => true,
            'steps' => [
                'analyze_code',
                'generate_unit_tests',
                'generate_feature_tests',
                'run_tests',
                'generate_coverage_report',
            ],
            'parallel_execution' => true,
            'rollback_on_failure' => false,
        ],
        'optimization_pipeline' => [
            'enabled' => true,
            'steps' => [
                'analyze_performance',
                'optimize_code',
                'optimize_database',
                'test_optimizations',
                'generate_report',
            ],
            'parallel_execution' => false,
            'rollback_on_failure' => true,
        ],
    ],

    'scheduling' => [
        'enabled' => true,
        'default_queue' => 'autogen',
        'max_parallel_jobs' => 5,
        'timeout' => 300, // seconds
    ],

    'notifications' => [
        'enabled' => true,
        'channels' => ['slack', 'email'],
        'on_success' => true,
        'on_failure' => true,
        'on_completion' => false,
    ],

    'logging' => [
        'enabled' => true,
        'detailed_logs' => true,
        'store_artifacts' => true,
        'retention_days' => 30,
    ],
];