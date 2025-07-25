<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Documentation Generator Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AutoGen Documentation Generator package.
    |
    */

    'enabled' => env('AUTOGEN_DOCUMENTATION_GENERATOR_ENABLED', true),

    'formats' => [
        'markdown' => [
            'enabled' => true,
            'output_path' => 'docs',
            'template' => 'markdown-docs',
        ],
        'html' => [
            'enabled' => false,
            'output_path' => 'docs/html',
            'template' => 'html-docs',
        ],
        'pdf' => [
            'enabled' => false,
            'output_path' => 'docs/pdf',
            'template' => 'pdf-docs',
        ],
    ],

    'generators' => [
        'api' => [
            'enabled' => true,
            'include_examples' => true,
            'include_schemas' => true,
        ],
        'readme' => [
            'enabled' => true,
            'sections' => [
                'installation',
                'usage',
                'configuration',
                'examples',
                'contributing',
            ],
        ],
        'changelog' => [
            'enabled' => true,
            'auto_generate' => false,
        ],
    ],

    'ai_prompts' => [
        'api' => 'Generate API documentation for the following endpoints: {endpoints}',
        'readme' => 'Generate a comprehensive README for the project: {project}',
        'code_comments' => 'Generate detailed code comments for the following class: {class}',
    ],
];