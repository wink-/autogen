<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class WorkflowOrchestrator
{
    /**
     * The dependency resolver instance.
     *
     * @var DependencyResolver
     */
    protected DependencyResolver $dependencyResolver;

    /**
     * The progress tracker instance.
     *
     * @var ProgressTracker
     */
    protected ProgressTracker $tracker;

    /**
     * History of executed operations for rollback.
     *
     * @var array
     */
    protected array $executionHistory = [];

    /**
     * Create a new workflow orchestrator instance.
     */
    public function __construct(DependencyResolver $dependencyResolver, ProgressTracker $tracker)
    {
        $this->dependencyResolver = $dependencyResolver;
        $this->tracker = $tracker;
    }

    /**
     * Execute the complete scaffold workflow.
     */
    public function execute(array $config, Command $command): WorkflowResult
    {
        try {
            // Create execution plan
            $plan = $this->createExecutionPlan($config);
            
            // Start tracking
            $this->tracker->start($plan);
            
            // Execute each step in order
            $generatedFiles = [];
            $skippedFiles = [];
            $errors = [];

            foreach ($plan->getSteps() as $step) {
                $command->info("Executing: {$step->getPackage()} - {$step->getDescription()}");
                
                $this->tracker->startStep($step);
                
                try {
                    $result = $this->executeStep($step, $config, $command);
                    
                    if ($result->isSuccess()) {
                        $generatedFiles = array_merge($generatedFiles, $result->getGeneratedFiles());
                        $skippedFiles = array_merge($skippedFiles, $result->getSkippedFiles());
                        
                        // Store for rollback capability
                        $this->executionHistory[] = [
                            'step' => $step,
                            'files' => $result->getGeneratedFiles(),
                            'timestamp' => now(),
                        ];
                        
                        $this->tracker->completeStep($step, $result);
                        $command->info("✓ Completed: {$step->getPackage()}");
                    } else {
                        $errors[] = "Step '{$step->getPackage()}' failed: " . $result->getMessage();
                        $this->tracker->failStep($step, $result->getMessage());
                        
                        // If it's a critical step, stop execution
                        if ($step->isCritical()) {
                            break;
                        }
                        
                        $command->warn("⚠ Step '{$step->getPackage()}' failed but continuing...");
                    }
                } catch (\Exception $e) {
                    $errors[] = "Step '{$step->getPackage()}' threw exception: " . $e->getMessage();
                    $this->tracker->failStep($step, $e->getMessage());
                    
                    if ($step->isCritical()) {
                        throw $e;
                    }
                    
                    $command->error("✗ Step '{$step->getPackage()}' failed: " . $e->getMessage());
                }
            }

            $this->tracker->complete();

            // Save execution history for rollback
            $this->saveExecutionHistory($config);

            if (empty($errors)) {
                return WorkflowResult::success($config, $generatedFiles, $skippedFiles);
            } else {
                return WorkflowResult::partialSuccess($config, $generatedFiles, $skippedFiles, $errors);
            }

        } catch (\Exception $e) {
            $this->tracker->fail($e->getMessage());
            return WorkflowResult::failure($config, $e->getMessage());
        }
    }

    /**
     * Create an execution plan based on configuration.
     */
    public function createExecutionPlan(array $config): ExecutionPlan
    {
        $steps = collect();
        $table = $config['table'];
        $modelName = Str::studly(Str::singular($table));

        // Step 1: Model generation (always first if not skipped)
        if (!$config['model']['skip']) {
            $steps->push(new ExecutionStep(
                order: 1,
                package: 'model',
                command: 'autogen:model',
                description: "Generate {$modelName} model",
                dependencies: [],
                isCritical: true,
                parameters: $this->buildModelParameters($config)
            ));
        }

        // Step 2: Controller generation (depends on model)
        if (!$config['controller']['skip']) {
            $dependencies = $config['model']['skip'] ? [] : ['model'];
            
            $steps->push(new ExecutionStep(
                order: 2,
                package: 'controller',
                command: 'autogen:controller',
                description: "Generate {$modelName}Controller",
                dependencies: $dependencies,
                isCritical: true,
                parameters: $this->buildControllerParameters($config)
            ));
        }

        // Step 3: Views generation (depends on controller, only for resource controllers)
        if (!$config['views']['skip'] && $config['controller']['type'] === 'resource') {
            $dependencies = [];
            if (!$config['model']['skip']) $dependencies[] = 'model';
            if (!$config['controller']['skip']) $dependencies[] = 'controller';
            
            $steps->push(new ExecutionStep(
                order: 3,
                package: 'views',
                command: 'autogen:views',
                description: "Generate views for {$modelName}",
                dependencies: $dependencies,
                isCritical: false,
                parameters: $this->buildViewsParameters($config)
            ));
        }

        // Step 4: Factory generation (depends on model)
        if ($config['factory']['generate']) {
            $dependencies = $config['model']['skip'] ? [] : ['model'];
            
            $steps->push(new ExecutionStep(
                order: 4,
                package: 'factory',
                command: 'make:factory',
                description: "Generate {$modelName}Factory",
                dependencies: $dependencies,
                isCritical: false,
                parameters: $this->buildFactoryParameters($config)
            ));
        }

        // Step 5: DataTable generation (depends on model and views)
        if ($config['datatable']['generate']) {
            $dependencies = [];
            if (!$config['model']['skip']) $dependencies[] = 'model';
            if (!$config['views']['skip']) $dependencies[] = 'views';
            
            $steps->push(new ExecutionStep(
                order: 5,
                package: 'datatable',
                command: 'autogen:datatable',
                description: "Generate {$modelName}DataTable",
                dependencies: $dependencies,
                isCritical: false,
                parameters: $this->buildDataTableParameters($config)
            ));
        }

        // Step 6: Migration generation (independent, can run anytime)
        if ($config['migration']['generate']) {
            $steps->push(new ExecutionStep(
                order: 6,
                package: 'migration',
                command: 'autogen:migration',
                description: "Generate migration for {$table}",
                dependencies: [],
                isCritical: false,
                parameters: $this->buildMigrationParameters($config)
            ));
        }

        // Resolve dependencies and optimize order
        $optimizedSteps = $this->dependencyResolver->resolveDependencies($steps);

        return new ExecutionPlan($optimizedSteps, $this->calculateExpectedFiles($config));
    }

    /**
     * Execute a single step of the workflow.
     */
    protected function executeStep(ExecutionStep $step, array $config, Command $command): StepResult
    {
        $commandName = $step->getCommand();
        $parameters = $step->getParameters();

        try {
            // Special handling for different command types
            switch ($step->getPackage()) {
                case 'model':
                    return $this->executeModelStep($parameters, $command);
                
                case 'controller':
                    return $this->executeControllerStep($parameters, $command);
                
                case 'views':
                    return $this->executeViewsStep($parameters, $command);
                
                case 'factory':
                    return $this->executeFactoryStep($parameters, $command);
                
                case 'datatable':
                    return $this->executeDataTableStep($parameters, $command);
                
                case 'migration':
                    return $this->executeMigrationStep($parameters, $command);
                
                default:
                    throw new \InvalidArgumentException("Unknown package: {$step->getPackage()}");
            }
        } catch (\Exception $e) {
            return StepResult::failure($e->getMessage());
        }
    }

    /**
     * Execute model generation step.
     */
    protected function executeModelStep(array $parameters, Command $command): StepResult
    {
        $exitCode = Artisan::call('autogen:model', $parameters);
        
        if ($exitCode === 0) {
            $generatedFiles = $this->getGeneratedModelFiles($parameters);
            return StepResult::success($generatedFiles);
        } else {
            return StepResult::failure('Model generation failed');
        }
    }

    /**
     * Execute controller generation step.
     */
    protected function executeControllerStep(array $parameters, Command $command): StepResult
    {
        $exitCode = Artisan::call('autogen:controller', $parameters);
        
        if ($exitCode === 0) {
            $generatedFiles = $this->getGeneratedControllerFiles($parameters);
            return StepResult::success($generatedFiles);
        } else {
            return StepResult::failure('Controller generation failed');
        }
    }

    /**
     * Execute views generation step.
     */
    protected function executeViewsStep(array $parameters, Command $command): StepResult
    {
        $exitCode = Artisan::call('autogen:views', $parameters);
        
        if ($exitCode === 0) {
            $generatedFiles = $this->getGeneratedViewFiles($parameters);
            return StepResult::success($generatedFiles);
        } else {
            return StepResult::failure('Views generation failed');
        }
    }

    /**
     * Execute factory generation step.
     */
    protected function executeFactoryStep(array $parameters, Command $command): StepResult
    {
        $exitCode = Artisan::call('make:factory', $parameters);
        
        if ($exitCode === 0) {
            $generatedFiles = $this->getGeneratedFactoryFiles($parameters);
            return StepResult::success($generatedFiles);
        } else {
            return StepResult::failure('Factory generation failed');
        }
    }

    /**
     * Execute DataTable generation step.
     */
    protected function executeDataTableStep(array $parameters, Command $command): StepResult
    {
        $exitCode = Artisan::call('autogen:datatable', $parameters);
        
        if ($exitCode === 0) {
            $generatedFiles = $this->getGeneratedDataTableFiles($parameters);
            return StepResult::success($generatedFiles);
        } else {
            return StepResult::failure('DataTable generation failed');
        }
    }

    /**
     * Execute migration generation step.
     */
    protected function executeMigrationStep(array $parameters, Command $command): StepResult
    {
        $exitCode = Artisan::call('autogen:migration', $parameters);
        
        if ($exitCode === 0) {
            $generatedFiles = $this->getGeneratedMigrationFiles($parameters);
            return StepResult::success($generatedFiles);
        } else {
            return StepResult::failure('Migration generation failed');
        }
    }

    /**
     * Build model generation parameters.
     */
    protected function buildModelParameters(array $config): array
    {
        $params = [
            '--connection' => $config['connection'],
            '--tables' => $config['table'],
        ];

        if ($config['model']['dir']) {
            $params['--dir'] = $config['model']['dir'];
        }

        if ($config['model']['namespace']) {
            $params['--namespace'] = $config['model']['namespace'];
        }

        if ($config['model']['with_relationships']) {
            $params['--with-relationships'] = true;
        }

        if ($config['model']['with_scopes']) {
            $params['--with-scopes'] = true;
        }

        if ($config['model']['with_traits']) {
            $params['--with-traits'] = true;
        }

        if ($config['force']) {
            $params['--force'] = true;
        }

        return $params;
    }

    /**
     * Build controller generation parameters.
     */
    protected function buildControllerParameters(array $config): array
    {
        $modelName = Str::studly(Str::singular($config['table']));
        
        $params = [
            'model' => $modelName,
        ];

        if ($config['controller']['type'] === 'api') {
            $params['--api'] = true;
        } else {
            $params['--resource'] = true;
        }

        if ($config['controller']['with_validation']) {
            $params['--with-validation'] = true;
        }

        if ($config['controller']['with_policy']) {
            $params['--with-policy'] = true;
        }

        if ($config['controller']['paginate']) {
            $params['--paginate'] = $config['controller']['paginate'];
        }

        if ($config['force']) {
            $params['--force'] = true;
        }

        return $params;
    }

    /**
     * Build views generation parameters.
     */
    protected function buildViewsParameters(array $config): array
    {
        $modelName = Str::studly(Str::singular($config['table']));
        
        $params = [
            'model' => $modelName,
            '--framework' => $config['views']['framework'],
            '--layout' => $config['views']['layout'],
        ];

        if (!empty($config['views']['only'])) {
            $params['--only'] = implode(',', $config['views']['only']);
        }

        if ($config['views']['with_datatable']) {
            $params['--with-datatable'] = true;
        }

        if ($config['views']['with_search']) {
            $params['--with-search'] = true;
        }

        if ($config['views']['with_modals']) {
            $params['--with-modals'] = true;
        }

        if ($config['force']) {
            $params['--force'] = true;
        }

        return $params;
    }

    /**
     * Build factory generation parameters.
     */
    protected function buildFactoryParameters(array $config): array
    {
        $modelName = Str::studly(Str::singular($config['table']));
        
        return [
            'name' => "{$modelName}Factory",
            '--model' => "App\\Models\\{$modelName}",
        ];
    }

    /**
     * Build DataTable generation parameters.
     */
    protected function buildDataTableParameters(array $config): array
    {
        $modelName = Str::studly(Str::singular($config['table']));
        
        $params = [
            'model' => $modelName,
        ];

        if ($config['force']) {
            $params['--force'] = true;
        }

        return $params;
    }

    /**
     * Build migration generation parameters.
     */
    protected function buildMigrationParameters(array $config): array
    {
        return [
            'table' => $config['table'],
            '--connection' => $config['connection'],
        ];
    }

    /**
     * Calculate expected files to be generated.
     */
    protected function calculateExpectedFiles(array $config): array
    {
        $files = [];
        $modelName = Str::studly(Str::singular($config['table']));
        $tableName = $config['table'];

        // Model files
        if (!$config['model']['skip']) {
            $modelDir = $config['model']['dir'] ? '/' . trim($config['model']['dir'], '/') : '';
            $files[] = "app/Models{$modelDir}/{$modelName}.php";
        }

        // Controller files
        if (!$config['controller']['skip']) {
            $controllerDir = $config['controller']['type'] === 'api' ? '/Api' : '';
            $files[] = "app/Http/Controllers{$controllerDir}/{$modelName}Controller.php";
            
            if ($config['controller']['with_validation']) {
                $files[] = "app/Http/Requests/Store{$modelName}Request.php";
                $files[] = "app/Http/Requests/Update{$modelName}Request.php";
            }
            
            if ($config['controller']['with_policy']) {
                $files[] = "app/Policies/{$modelName}Policy.php";
            }
        }

        // View files
        if (!$config['views']['skip'] && $config['controller']['type'] === 'resource') {
            $viewDir = Str::plural(Str::kebab($modelName));
            $views = $config['views']['only'] ?: ['index', 'create', 'edit', 'show'];
            
            foreach ($views as $view) {
                $files[] = "resources/views/{$viewDir}/{$view}.blade.php";
            }
        }

        // Factory files
        if ($config['factory']['generate']) {
            $files[] = "database/factories/{$modelName}Factory.php";
        }

        // DataTable files
        if ($config['datatable']['generate']) {
            $files[] = "app/DataTables/{$modelName}DataTable.php";
        }

        // Migration files
        if ($config['migration']['generate']) {
            $timestamp = date('Y_m_d_His');
            $files[] = "database/migrations/{$timestamp}_create_{$tableName}_table.php";
        }

        return $files;
    }

    /**
     * Get generated model files.
     */
    protected function getGeneratedModelFiles(array $parameters): array
    {
        // This would be implemented to scan the actual generated files
        // For now, return expected files based on parameters
        return [];
    }

    /**
     * Get generated controller files.
     */
    protected function getGeneratedControllerFiles(array $parameters): array
    {
        // This would be implemented to scan the actual generated files
        return [];
    }

    /**
     * Get generated view files.
     */
    protected function getGeneratedViewFiles(array $parameters): array
    {
        // This would be implemented to scan the actual generated files
        return [];
    }

    /**
     * Get generated factory files.
     */
    protected function getGeneratedFactoryFiles(array $parameters): array
    {
        // This would be implemented to scan the actual generated files
        return [];
    }

    /**
     * Get generated DataTable files.
     */
    protected function getGeneratedDataTableFiles(array $parameters): array
    {
        // This would be implemented to scan the actual generated files
        return [];
    }

    /**
     * Get generated migration files.
     */
    protected function getGeneratedMigrationFiles(array $parameters): array
    {
        // This would be implemented to scan the actual generated files
        return [];
    }

    /**
     * Save execution history for rollback capability.
     */
    protected function saveExecutionHistory(array $config): void
    {
        $historyFile = storage_path('autogen/scaffold_history.json');
        
        // Ensure directory exists
        File::ensureDirectoryExists(dirname($historyFile));
        
        $history = [
            'timestamp' => now()->toISOString(),
            'config' => $config,
            'execution_history' => $this->executionHistory,
        ];
        
        File::put($historyFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    /**
     * Rollback the last scaffold operation.
     */
    public function rollback(Command $command): WorkflowResult
    {
        $historyFile = storage_path('autogen/scaffold_history.json');
        
        if (!File::exists($historyFile)) {
            return WorkflowResult::failure([], 'No scaffold history found for rollback.');
        }

        try {
            $history = json_decode(File::get($historyFile), true);
            $executionHistory = $history['execution_history'] ?? [];
            
            $rolledBackFiles = [];
            
            // Rollback in reverse order
            foreach (array_reverse($executionHistory) as $execution) {
                foreach ($execution['files'] as $file) {
                    if (File::exists($file)) {
                        File::delete($file);
                        $rolledBackFiles[] = $file;
                        $command->info("Removed: {$file}");
                    }
                }
            }
            
            // Remove history file
            File::delete($historyFile);
            
            return WorkflowResult::success(
                $history['config'],
                [],
                [],
                "Rollback completed. Removed " . count($rolledBackFiles) . " files."
            );
            
        } catch (\Exception $e) {
            return WorkflowResult::failure([], 'Rollback failed: ' . $e->getMessage());
        }
    }
}