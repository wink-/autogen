<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Analysis Tools Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AutoGen Analysis Tools package.
    |
    */

    'enabled' => env('AUTOGEN_ANALYSIS_TOOLS_ENABLED', true),

    'analyzers' => [
        'code_quality' => [
            'enabled' => true,
            'tools' => ['phpstan', 'psalm', 'php-cs-fixer'],
            'severity_threshold' => 'warning',
        ],
        'security' => [
            'enabled' => true,
            'tools' => ['enlightn', 'security-checker'],
            'auto_fix' => false,
        ],
        'performance' => [
            'enabled' => true,
            'tools' => ['blackfire', 'xdebug'],
            'profile_endpoints' => true,
        ],
        'dependencies' => [
            'enabled' => true,
            'check_outdated' => true,
            'check_vulnerabilities' => true,
            'suggest_updates' => true,
        ],
    ],

    'reports' => [
        'format' => 'json', // json, xml, html
        'output_path' => 'storage/autogen/analysis',
        'include_suggestions' => true,
        'include_metrics' => true,
    ],

    'ai_analysis' => [
        'enabled' => true,
        'provide_suggestions' => true,
        'suggest_refactoring' => true,
        'detect_patterns' => true,
    ],

    'thresholds' => [
        'complexity' => 10,
        'duplication' => 5,
        'maintainability' => 70,
        'test_coverage' => 80,
    ],
];