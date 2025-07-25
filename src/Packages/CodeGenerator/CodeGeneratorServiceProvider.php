<?php

declare(strict_types=1);

namespace AutoGen\Packages\CodeGenerator;

use Illuminate\Support\ServiceProvider;

class CodeGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/code-generator.php',
            'autogen.code-generator'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/code-generator.php' => config_path('autogen/code-generator.php'),
            ], 'autogen-code-generator-config');
        }
    }
}