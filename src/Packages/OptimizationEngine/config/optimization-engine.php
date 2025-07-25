<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Optimization Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AutoGen Optimization Engine package.
    |
    */

    'enabled' => env('AUTOGEN_OPTIMIZATION_ENGINE_ENABLED', true),

    'optimizers' => [
        'code' => [
            'enabled' => true,
            'auto_apply' => false,
            'techniques' => [
                'dead_code_removal',
                'unused_imports',
                'variable_optimization',
                'method_inlining',
            ],
        ],
        'database' => [
            'enabled' => true,
            'auto_apply' => false,
            'techniques' => [
                'query_optimization',
                'index_suggestions',
                'n_plus_one_detection',
                'eager_loading',
            ],
        ],
        'performance' => [
            'enabled' => true,
            'auto_apply' => false,
            'techniques' => [
                'caching_suggestions',
                'memory_optimization',
                'algorithm_improvements',
                'batch_processing',
            ],
        ],
    ],

    'ai_optimization' => [
        'enabled' => true,
        'suggest_patterns' => true,
        'refactor_suggestions' => true,
        'performance_analysis' => true,
    ],

    'safety' => [
        'backup_original' => true,
        'test_before_apply' => true,
        'rollback_on_failure' => true,
        'confirmation_required' => true,
    ],

    'metrics' => [
        'track_improvements' => true,
        'measure_performance' => true,
        'generate_reports' => true,
    ],
];