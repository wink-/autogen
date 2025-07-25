<?php

declare(strict_types=1);

namespace AutoGen\Packages\Controller;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ControllerGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autogen:controller 
                            {model : The model name (e.g., User or Admin/User)}
                            {--api : Generate an API controller}
                            {--resource : Generate a resource controller (default)}
                            {--with-validation : Generate form request validation classes}
                            {--with-policy : Generate a policy class}
                            {--paginate= : Number of items per page for index methods}
                            {--force : Overwrite existing controller files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a controller with CRUD operations for a model';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = $this->argument('model');
        $controllerType = $this->getControllerType();
        $withValidation = $this->option('with-validation');
        $withPolicy = $this->option('with-policy');
        $paginate = $this->option('paginate') ?: config('autogen.defaults.pagination', 15);
        $force = $this->option('force');

        $this->info("Generating {$controllerType} controller for model: {$modelName}");

        try {
            $generator = new ControllerGenerator(
                $modelName,
                $controllerType,
                $withValidation,
                $withPolicy,
                (int) $paginate,
                $force
            );

            $files = $generator->generate();

            foreach ($files as $file) {
                $this->info("✓ Created: {$file}");
            }

            $this->newLine();
            $this->info('Controller generation completed successfully!');

            if ($withValidation) {
                $this->info('Form request validation classes have been generated.');
            }

            if ($withPolicy) {
                $this->info('Policy class has been generated.');
                $this->warn('Remember to register the policy in your AuthServiceProvider.');
            }

            $this->displayNextSteps($modelName, $controllerType);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate controller: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Determine the controller type based on options.
     */
    protected function getControllerType(): string
    {
        if ($this->option('api')) {
            return 'api';
        }

        if ($this->option('resource')) {
            return 'resource';
        }

        return config('autogen.defaults.controller_type', 'resource');
    }

    /**
     * Display next steps after generation.
     */
    protected function displayNextSteps(string $modelName, string $controllerType): void
    {
        $this->newLine();
        $this->info('Next steps:');
        
        $controllerName = Str::studly($modelName) . 'Controller';
        $routePrefix = Str::plural(Str::kebab(class_basename($modelName)));
        
        if ($controllerType === 'api') {
            $this->line("1. Add routes to routes/api.php:");
            $this->line("   Route::apiResource('{$routePrefix}', \\App\\Http\\Controllers\\Api\\{$controllerName}::class);");
        } else {
            $this->line("1. Add routes to routes/web.php:");
            $this->line("   Route::resource('{$routePrefix}', \\App\\Http\\Controllers\\{$controllerName}::class);");
        }
        
        if ($this->option('with-policy')) {
            $this->line("2. Register the policy in App\\Providers\\AuthServiceProvider:");
            $this->line("   protected \$policies = [");
            $this->line("       \\App\\Models\\{$modelName}::class => \\App\\Policies\\{$modelName}Policy::class,");
            $this->line("   ];");
        }
        
        $this->line("3. Run 'php artisan autogen:views {$modelName}' to generate views (for web controllers)");
    }
}