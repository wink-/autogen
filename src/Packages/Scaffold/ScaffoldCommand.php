<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ScaffoldCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autogen:scaffold 
                            {table : The database table name to scaffold}
                            {--connection= : The database connection to use}
                            {--interactive : Interactive mode for user choices}
                            {--dry-run : Show what would be generated without creating files}
                            {--rollback : Rollback the last scaffold operation}
                            {--model-dir= : The subdirectory within app/Models/}
                            {--model-namespace= : Custom namespace for the models}
                            {--controller-api : Generate an API controller}
                            {--controller-resource : Generate a resource controller (default)}
                            {--with-validation : Generate form request validation classes}
                            {--with-policy : Generate a policy class}
                            {--with-relationships : Analyze and include relationships}
                            {--with-scopes : Generate common query scopes}
                            {--with-traits : Auto-detect and include traits}
                            {--view-framework=tailwind : CSS framework to use (tailwind, bootstrap, css)}
                            {--view-layout=layouts.app : Master layout file to extend}
                            {--view-only= : Comma-separated list of views to generate}
                            {--with-datatable : Include DataTable integration}
                            {--with-search : Include search/filter functionality}
                            {--with-modals : Use modal dialogs for confirmations}
                            {--with-migration : Generate migration file}
                            {--with-factory : Generate factory for testing}
                            {--skip-model : Skip model generation}
                            {--skip-controller : Skip controller generation}
                            {--skip-views : Skip view generation}
                            {--skip-migration : Skip migration generation}
                            {--skip-factory : Skip factory generation}
                            {--skip-datatable : Skip datatable generation}
                            {--paginate= : Number of items per page for index methods}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Master scaffold command that orchestrates all AutoGen packages to generate complete CRUD functionality';

    /**
     * The workflow orchestrator instance.
     *
     * @var WorkflowOrchestrator
     */
    protected WorkflowOrchestrator $orchestrator;

    /**
     * The configuration validator instance.
     *
     * @var ConfigurationValidator
     */
    protected ConfigurationValidator $validator;

    /**
     * The progress tracker instance.
     *
     * @var ProgressTracker
     */
    protected ProgressTracker $tracker;

    /**
     * Create a new command instance.
     */
    public function __construct(
        WorkflowOrchestrator $orchestrator,
        ConfigurationValidator $validator,
        ProgressTracker $tracker
    ) {
        parent::__construct();
        
        $this->orchestrator = $orchestrator;
        $this->validator = $validator;
        $this->tracker = $tracker;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Handle rollback first
        if ($this->option('rollback')) {
            return $this->handleRollback();
        }

        $table = $this->argument('table');
        $connection = $this->option('connection');
        
        if (!$connection) {
            $this->error('The --connection option is required.');
            return self::FAILURE;
        }

        $this->info("Starting scaffold generation for table: {$table}");
        $this->info("Database connection: {$connection}");
        $this->newLine();

        try {
            // Validate configuration
            $config = $this->buildConfiguration();
            
            if (!$this->validator->validate($config)) {
                $this->error('Configuration validation failed:');
                foreach ($this->validator->getErrors() as $error) {
                    $this->error("  - {$error}");
                }
                return self::FAILURE;
            }

            // Interactive mode handling
            if ($this->option('interactive')) {
                $config = $this->runInteractiveMode($config);
            }

            // Dry run mode
            if ($this->option('dry-run')) {
                return $this->runDryRun($config);
            }

            // Initialize progress tracking
            $this->tracker->initialize($config);

            // Execute workflow orchestration
            $result = $this->orchestrator->execute($config, $this);

            if ($result->isSuccess()) {
                $this->displaySuccessMessage($result);
                return self::SUCCESS;
            } else {
                $this->displayErrorMessage($result);
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('Scaffold generation failed: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Build configuration array from command options.
     */
    protected function buildConfiguration(): array
    {
        return [
            'table' => $this->argument('table'),
            'connection' => $this->option('connection'),
            'interactive' => $this->option('interactive') ?? false,
            'dry_run' => $this->option('dry-run') ?? false,
            'force' => $this->option('force') ?? false,
            
            // Model configuration
            'model' => [
                'skip' => $this->option('skip-model') ?? false,
                'dir' => $this->option('model-dir') ?? '',
                'namespace' => $this->option('model-namespace'),
                'with_relationships' => $this->option('with-relationships') ?? false,
                'with_scopes' => $this->option('with-scopes') ?? false,
                'with_traits' => $this->option('with-traits') ?? true,
            ],
            
            // Controller configuration
            'controller' => [
                'skip' => $this->option('skip-controller') ?? false,
                'type' => $this->getControllerType(),
                'with_validation' => $this->option('with-validation') ?? false,
                'with_policy' => $this->option('with-policy') ?? false,
                'paginate' => $this->option('paginate') ?: 15,
            ],
            
            // Views configuration
            'views' => [
                'skip' => $this->option('skip-views') ?? false,
                'framework' => $this->option('view-framework') ?? 'tailwind',
                'layout' => $this->option('view-layout') ?? 'layouts.app',
                'only' => $this->option('view-only') ? 
                    array_map('trim', explode(',', $this->option('view-only'))) : [],
                'with_datatable' => $this->option('with-datatable') ?? false,
                'with_search' => $this->option('with-search') ?? false,
                'with_modals' => $this->option('with-modals') ?? false,
            ],
            
            // Migration configuration
            'migration' => [
                'skip' => $this->option('skip-migration') ?? true,
                'generate' => $this->option('with-migration') ?? false,
            ],
            
            // Factory configuration
            'factory' => [
                'skip' => $this->option('skip-factory') ?? true,
                'generate' => $this->option('with-factory') ?? false,
            ],
            
            // DataTable configuration
            'datatable' => [
                'skip' => $this->option('skip-datatable') ?? true,
                'generate' => $this->option('with-datatable') ?? false,
            ],
        ];
    }

    /**
     * Determine the controller type based on options.
     */
    protected function getControllerType(): string
    {
        if ($this->option('controller-api')) {
            return 'api';
        }

        if ($this->option('controller-resource')) {
            return 'resource';
        }

        return 'resource'; // Default
    }

    /**
     * Run interactive mode to gather user preferences.
     */
    protected function runInteractiveMode(array $config): array
    {
        $this->info('=== Interactive Scaffold Configuration ===');
        $this->newLine();

        // Model options
        if (!$config['model']['skip']) {
            $config['model']['with_relationships'] = $this->confirm(
                'Include relationship analysis?', 
                $config['model']['with_relationships']
            );
            
            $config['model']['with_scopes'] = $this->confirm(
                'Generate common query scopes?', 
                $config['model']['with_scopes']
            );
        }

        // Controller options
        if (!$config['controller']['skip']) {
            $controllerType = $this->choice(
                'Controller type',
                ['resource', 'api'],
                $config['controller']['type'] === 'api' ? 1 : 0
            );
            $config['controller']['type'] = $controllerType;
            
            $config['controller']['with_validation'] = $this->confirm(
                'Generate form request validation classes?', 
                $config['controller']['with_validation']
            );
            
            $config['controller']['with_policy'] = $this->confirm(
                'Generate policy class?', 
                $config['controller']['with_policy']
            );
        }

        // Views options (only for resource controllers)
        if (!$config['views']['skip'] && $config['controller']['type'] === 'resource') {
            $framework = $this->choice(
                'CSS framework',
                ['tailwind', 'bootstrap', 'css'],
                array_search($config['views']['framework'], ['tailwind', 'bootstrap', 'css'])
            );
            $config['views']['framework'] = $framework;
            
            $config['views']['with_datatable'] = $this->confirm(
                'Include DataTable integration?', 
                $config['views']['with_datatable']
            );
            
            $config['views']['with_search'] = $this->confirm(
                'Include search/filter functionality?', 
                $config['views']['with_search']
            );
            
            $config['views']['with_modals'] = $this->confirm(
                'Use modal dialogs for confirmations?', 
                $config['views']['with_modals']
            );
        }

        // Additional components
        $config['factory']['generate'] = $this->confirm(
            'Generate factory for testing?', 
            $config['factory']['generate']
        );
        
        $config['migration']['generate'] = $this->confirm(
            'Generate migration file?', 
            $config['migration']['generate']
        );

        $this->newLine();
        return $config;
    }

    /**
     * Run dry run mode to show what would be generated.
     */
    protected function runDryRun(array $config): int
    {
        $this->info('=== DRY RUN MODE - No files will be created ===');
        $this->newLine();

        try {
            $plan = $this->orchestrator->createExecutionPlan($config);
            
            $this->info('Execution Plan:');
            $this->table(
                ['Step', 'Package', 'Command', 'Dependencies'],
                $plan->getSteps()->map(function ($step) {
                    return [
                        $step->getOrder(),
                        $step->getPackage(),
                        $step->getCommand(),
                        implode(', ', $step->getDependencies())
                    ];
                })->toArray()
            );

            $this->newLine();
            $this->info('Files that would be generated:');
            
            foreach ($plan->getExpectedFiles() as $file) {
                $this->line("  - {$file}");
            }

            $this->newLine();
            $this->info('Run without --dry-run to execute the plan.');
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create execution plan: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Handle rollback operation.
     */
    protected function handleRollback(): int
    {
        $this->info('Rolling back last scaffold operation...');
        
        try {
            $result = $this->orchestrator->rollback($this);
            
            if ($result->isSuccess()) {
                $this->info('Rollback completed successfully.');
                return self::SUCCESS;
            } else {
                $this->error('Rollback failed: ' . $result->getMessage());
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Rollback failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Display success message with summary.
     */
    protected function displaySuccessMessage($result): void
    {
        $this->newLine();
        $this->info('🎉 Scaffold generation completed successfully!');
        $this->newLine();

        $summary = $result->getSummary();
        
        $this->info('Generated files:');
        foreach ($summary['generated_files'] as $file) {
            $this->line("  ✓ {$file}");
        }

        if (!empty($summary['skipped_files'])) {
            $this->newLine();
            $this->warn('Skipped files (already exist):');
            foreach ($summary['skipped_files'] as $file) {
                $this->line("  - {$file}");
            }
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->displayNextSteps($result->getConfiguration());
    }

    /**
     * Display error message with details.
     */
    protected function displayErrorMessage($result): void
    {
        $this->newLine();
        $this->error('❌ Scaffold generation failed!');
        $this->newLine();

        $this->error('Error: ' . $result->getMessage());

        if ($result->hasPartialSuccess()) {
            $this->newLine();
            $this->warn('Partially completed files:');
            foreach ($result->getPartialFiles() as $file) {
                $this->line("  ⚠ {$file}");
            }
            
            $this->newLine();
            $this->info('You can run with --rollback to clean up partial generation.');
        }
    }

    /**
     * Display next steps after successful generation.
     */
    protected function displayNextSteps(array $config): void
    {
        $table = $config['table'];
        $modelName = Str::studly(Str::singular($table));
        $routeName = Str::plural(Str::kebab($table));

        $this->line('1. Add routes to your routes file:');
        
        if ($config['controller']['type'] === 'api') {
            $this->line("   Route::apiResource('{$routeName}', \\App\\Http\\Controllers\\Api\\{$modelName}Controller::class);");
        } else {
            $this->line("   Route::resource('{$routeName}', \\App\\Http\\Controllers\\{$modelName}Controller::class);");
        }

        if ($config['controller']['with_policy']) {
            $this->line('2. Register the policy in AuthServiceProvider:');
            $this->line("   \\App\\Models\\{$modelName}::class => \\App\\Policies\\{$modelName}Policy::class,");
        }

        if (!$config['views']['skip'] && $config['controller']['type'] === 'resource') {
            $this->line('3. Compile your assets:');
            $this->line('   npm run dev   # or npm run build');
        }

        if ($config['migration']['generate']) {
            $this->line('4. Run the migration:');
            $this->line('   php artisan migrate');
        }

        if ($config['factory']['generate']) {
            $this->line('5. Use the factory in your tests:');
            $this->line("   {$modelName}::factory()->create();");
        }
    }
}