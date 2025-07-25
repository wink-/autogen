<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class ConfigurationValidator
{
    /**
     * Validation errors.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Validation warnings.
     *
     * @var array
     */
    protected array $warnings = [];

    /**
     * Validate the complete configuration.
     */
    public function validate(array $config): bool
    {
        $this->errors = [];
        $this->warnings = [];

        // Core validation
        $this->validateCoreConfiguration($config);
        
        // Database validation
        $this->validateDatabaseConfiguration($config);
        
        // Package-specific validation
        $this->validateModelConfiguration($config);
        $this->validateControllerConfiguration($config);
        $this->validateViewsConfiguration($config);
        $this->validateFactoryConfiguration($config);
        $this->validateDataTableConfiguration($config);
        $this->validateMigrationConfiguration($config);
        
        // Cross-package validation
        $this->validatePackageDependencies($config);
        
        // File system validation
        $this->validateFileSystemPermissions($config);

        return empty($this->errors);
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings.
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Validate core configuration requirements.
     */
    protected function validateCoreConfiguration(array $config): void
    {
        // Table name is required
        if (empty($config['table'])) {
            $this->errors[] = 'Table name is required';
            return;
        }

        // Validate table name format
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $config['table'])) {
            $this->errors[] = 'Table name must be a valid identifier (letters, numbers, underscores)';
        }

        // Connection is required
        if (empty($config['connection'])) {
            $this->errors[] = 'Database connection is required';
            return;
        }

        // Validate that at least one package is enabled
        $enabledPackages = $this->getEnabledPackages($config);
        if (empty($enabledPackages)) {
            $this->errors[] = 'At least one package must be enabled for generation';
        }

        // Validate force option conflicts
        if ($config['force'] && $config['dry_run']) {
            $this->warnings[] = 'Force option has no effect in dry-run mode';
        }
    }

    /**
     * Validate database configuration.
     */
    protected function validateDatabaseConfiguration(array $config): void
    {
        $connection = $config['connection'];

        try {
            // Test database connection
            $pdo = DB::connection($connection)->getPdo();
            
            // Check if table exists
            if (Schema::connection($connection)->hasTable($config['table'])) {
                $this->validateExistingTable($config);
            } else {
                // Table doesn't exist - check if migration is enabled
                if (!$config['migration']['generate']) {
                    $this->warnings[] = "Table '{$config['table']}' does not exist and migration generation is disabled";
                }
            }
        } catch (QueryException $e) {
            $this->errors[] = "Cannot connect to database '{$connection}': " . $e->getMessage();
        } catch (\Exception $e) {
            $this->errors[] = "Database configuration error: " . $e->getMessage();
        }
    }

    /**
     * Validate existing table structure.
     */
    protected function validateExistingTable(array $config): void
    {
        $connection = $config['connection'];
        $table = $config['table'];

        try {
            // Get table columns
            $columns = Schema::connection($connection)->getColumnListing($table);
            
            if (empty($columns)) {
                $this->warnings[] = "Table '{$table}' exists but has no columns";
                return;
            }

            // Check for common required columns
            $requiredColumns = ['id', 'created_at', 'updated_at'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (!empty($missingColumns)) {
                $this->warnings[] = "Table '{$table}' is missing recommended columns: " . implode(', ', $missingColumns);
            }

            // Check for primary key
            $primaryKey = Schema::connection($connection)->getConnection()->getDoctrineSchemaManager()
                ->listTableDetails($table)->getPrimaryKey();
                
            if (!$primaryKey) {
                $this->warnings[] = "Table '{$table}' has no primary key defined";
            }

        } catch (\Exception $e) {
            $this->warnings[] = "Could not analyze table structure: " . $e->getMessage();
        }
    }

    /**
     * Validate model configuration.
     */
    protected function validateModelConfiguration(array $config): void
    {
        if ($config['model']['skip']) {
            return;
        }

        // Validate model directory
        if ($config['model']['dir']) {
            $dir = trim($config['model']['dir'], '/');
            if (!preg_match('/^[a-zA-Z0-9_\/]+$/', $dir)) {
                $this->errors[] = 'Model directory contains invalid characters';
            }
        }

        // Validate custom namespace
        if ($config['model']['namespace']) {
            if (!$this->isValidNamespace($config['model']['namespace'])) {
                $this->errors[] = 'Model namespace is not valid';
            }
        }

        // Check for potential model name conflicts
        $modelName = Str::studly(Str::singular($config['table']));
        $modelPath = $this->getModelPath($modelName, $config['model']);
        
        if (file_exists($modelPath) && !$config['force']) {
            $this->warnings[] = "Model file already exists: {$modelPath}";
        }

        // Validate relationships option requires database analysis
        if ($config['model']['with_relationships']) {
            try {
                // Check if foreign key constraints exist
                $connection = $config['connection'];
                // This is a simplified check - in reality you'd analyze the schema
                $this->warnings[] = 'Relationship analysis will be performed - ensure foreign key constraints are properly defined';
            } catch (\Exception $e) {
                $this->warnings[] = 'Cannot analyze relationships: ' . $e->getMessage();
            }
        }
    }

    /**
     * Validate controller configuration.
     */
    protected function validateControllerConfiguration(array $config): void
    {
        if ($config['controller']['skip']) {
            return;
        }

        // Model dependency check
        if ($config['model']['skip']) {
            $modelName = Str::studly(Str::singular($config['table']));
            $modelClass = "App\\Models\\{$modelName}";
            
            if (!class_exists($modelClass)) {
                $this->errors[] = "Controller generation requires model '{$modelClass}' to exist";
            }
        }

        // Validate pagination setting
        $paginate = $config['controller']['paginate'];
        if (!is_numeric($paginate) || $paginate < 1 || $paginate > 1000) {
            $this->errors[] = 'Pagination value must be between 1 and 1000';
        }

        // Check for existing controller
        $modelName = Str::studly(Str::singular($config['table']));
        $controllerName = "{$modelName}Controller";
        $controllerPath = $this->getControllerPath($controllerName, $config['controller']['type']);
        
        if (file_exists($controllerPath) && !$config['force']) {
            $this->warnings[] = "Controller file already exists: {$controllerPath}";
        }

        // Validate policy generation
        if ($config['controller']['with_policy']) {
            $policyPath = app_path("Policies/{$modelName}Policy.php");
            if (file_exists($policyPath) && !$config['force']) {
                $this->warnings[] = "Policy file already exists: {$policyPath}";
            }
        }

        // Validate validation generation
        if ($config['controller']['with_validation']) {
            $storePath = app_path("Http/Requests/Store{$modelName}Request.php");
            $updatePath = app_path("Http/Requests/Update{$modelName}Request.php");
            
            if (file_exists($storePath) && !$config['force']) {
                $this->warnings[] = "Store request file already exists: {$storePath}";
            }
            
            if (file_exists($updatePath) && !$config['force']) {
                $this->warnings[] = "Update request file already exists: {$updatePath}";
            }
        }
    }

    /**
     * Validate views configuration.
     */
    protected function validateViewsConfiguration(array $config): void
    {
        if ($config['views']['skip']) {
            return;
        }

        // Views only make sense for resource controllers
        if ($config['controller']['type'] === 'api') {
            $this->warnings[] = 'View generation is typically not needed for API controllers';
        }

        // Validate framework
        $validFrameworks = ['tailwind', 'bootstrap', 'css'];
        if (!in_array($config['views']['framework'], $validFrameworks)) {
            $this->errors[] = 'Invalid CSS framework. Must be one of: ' . implode(', ', $validFrameworks);
        }

        // Validate layout file
        $layout = $config['views']['layout'];
        $layoutPath = resource_path("views/{$layout}.blade.php");
        if (!file_exists($layoutPath)) {
            $this->warnings[] = "Layout file does not exist: {$layoutPath}";
        }

        // Validate specific views
        if (!empty($config['views']['only'])) {
            $validViews = ['index', 'create', 'edit', 'show', 'form', 'table', 'filters'];
            $invalidViews = array_diff($config['views']['only'], $validViews);
            
            if (!empty($invalidViews)) {
                $this->errors[] = 'Invalid view types: ' . implode(', ', $invalidViews);
            }
        }

        // Check for existing view files
        $modelName = Str::studly(Str::singular($config['table']));
        $viewDir = resource_path('views/' . Str::plural(Str::kebab($modelName)));
        
        if (is_dir($viewDir) && !$config['force']) {
            $this->warnings[] = "View directory already exists: {$viewDir}";
        }

        // DataTable dependencies
        if ($config['views']['with_datatable']) {
            if (!$config['datatable']['generate']) {
                $this->warnings[] = 'DataTable views enabled but DataTable generation is disabled';
            }
        }
    }

    /**
     * Validate factory configuration.
     */
    protected function validateFactoryConfiguration(array $config): void
    {
        if (!$config['factory']['generate']) {
            return;
        }

        // Factory requires model
        if ($config['model']['skip']) {
            $modelName = Str::studly(Str::singular($config['table']));
            $modelClass = "App\\Models\\{$modelName}";
            
            if (!class_exists($modelClass)) {
                $this->errors[] = "Factory generation requires model '{$modelClass}' to exist";
            }
        }

        // Check for existing factory
        $modelName = Str::studly(Str::singular($config['table']));
        $factoryPath = database_path("factories/{$modelName}Factory.php");
        
        if (file_exists($factoryPath) && !$config['force']) {
            $this->warnings[] = "Factory file already exists: {$factoryPath}";
        }
    }

    /**
     * Validate DataTable configuration.
     */
    protected function validateDataTableConfiguration(array $config): void
    {
        if (!$config['datatable']['generate']) {
            return;
        }

        // DataTable typically requires model
        if ($config['model']['skip']) {
            $modelName = Str::studly(Str::singular($config['table']));
            $modelClass = "App\\Models\\{$modelName}";
            
            if (!class_exists($modelClass)) {
                $this->warnings[] = "DataTable generation works best with model '{$modelClass}'";
            }
        }

        // Check if DataTables package is available
        if (!class_exists('Yajra\DataTables\DataTablesServiceProvider')) {
            $this->warnings[] = 'DataTables package not detected. Run: composer require yajra/laravel-datatables-oracle';
        }

        // Check for existing DataTable
        $modelName = Str::studly(Str::singular($config['table']));
        $dataTablePath = app_path("DataTables/{$modelName}DataTable.php");
        
        if (file_exists($dataTablePath) && !$config['force']) {
            $this->warnings[] = "DataTable file already exists: {$dataTablePath}";
        }
    }

    /**
     * Validate migration configuration.
     */
    protected function validateMigrationConfiguration(array $config): void
    {
        if (!$config['migration']['generate']) {
            return;
        }

        // Check if table already exists
        if (Schema::connection($config['connection'])->hasTable($config['table'])) {
            $this->warnings[] = "Table '{$config['table']}' already exists. Migration will create a new table.";
        }

        // Check migration directory permissions
        $migrationPath = database_path('migrations');
        if (!is_writable($migrationPath)) {
            $this->errors[] = "Migration directory is not writable: {$migrationPath}";
        }
    }

    /**
     * Validate package dependencies.
     */
    protected function validatePackageDependencies(array $config): void
    {
        // Controller needs model (unless existing)
        if (!$config['controller']['skip'] && $config['model']['skip']) {
            $modelName = Str::studly(Str::singular($config['table']));
            $modelClass = "App\\Models\\{$modelName}";
            
            if (!class_exists($modelClass)) {
                $this->errors[] = 'Controller generation requires a model to exist';
            }
        }

        // Views need controller and model
        if (!$config['views']['skip']) {
            if ($config['controller']['skip'] && $config['model']['skip']) {
                $this->warnings[] = 'Views work best with both controller and model generated';
            }
        }

        // DataTable needs model
        if ($config['datatable']['generate'] && $config['model']['skip']) {
            $modelName = Str::studly(Str::singular($config['table']));
            $modelClass = "App\\Models\\{$modelName}";
            
            if (!class_exists($modelClass)) {
                $this->warnings[] = 'DataTable generation works best with a model';
            }
        }

        // Factory needs model
        if ($config['factory']['generate'] && $config['model']['skip']) {
            $modelName = Str::studly(Str::singular($config['table']));
            $modelClass = "App\\Models\\{$modelName}";
            
            if (!class_exists($modelClass)) {
                $this->errors[] = 'Factory generation requires a model';
            }
        }
    }

    /**
     * Validate file system permissions.
     */
    protected function validateFileSystemPermissions(array $config): void
    {
        $directories = [
            app_path() => 'App directory',
            resource_path() => 'Resources directory',
            database_path() => 'Database directory',
        ];

        foreach ($directories as $path => $name) {
            if (!is_dir($path)) {
                $this->errors[] = "{$name} does not exist: {$path}";
            } elseif (!is_writable($path)) {
                $this->errors[] = "{$name} is not writable: {$path}";
            }
        }
    }

    /**
     * Get enabled packages from configuration.
     */
    protected function getEnabledPackages(array $config): array
    {
        $enabled = [];

        if (!$config['model']['skip']) {
            $enabled[] = 'model';
        }

        if (!$config['controller']['skip']) {
            $enabled[] = 'controller';
        }

        if (!$config['views']['skip']) {
            $enabled[] = 'views';
        }

        if ($config['factory']['generate']) {
            $enabled[] = 'factory';
        }

        if ($config['datatable']['generate']) {
            $enabled[] = 'datatable';
        }

        if ($config['migration']['generate']) {
            $enabled[] = 'migration';
        }

        return $enabled;
    }

    /**
     * Check if a namespace is valid.
     */
    protected function isValidNamespace(string $namespace): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/', $namespace);
    }

    /**
     * Get the expected model file path.
     */
    protected function getModelPath(string $modelName, array $modelConfig): string
    {
        $dir = $modelConfig['dir'] ? '/' . trim($modelConfig['dir'], '/') : '';
        return app_path("Models{$dir}/{$modelName}.php");
    }

    /**
     * Get the expected controller file path.
     */
    protected function getControllerPath(string $controllerName, string $type): string
    {
        $dir = $type === 'api' ? '/Api' : '';
        return app_path("Http/Controllers{$dir}/{$controllerName}.php");
    }

    /**
     * Check if the configuration is valid for dry run.
     */
    public function validateForDryRun(array $config): bool
    {
        // Dry run only needs basic validation
        $this->errors = [];
        
        $this->validateCoreConfiguration($config);
        
        // Don't validate database connection for dry run
        if (empty($config['connection'])) {
            $this->errors[] = 'Database connection is required even for dry run';
        }

        return empty($this->errors);
    }

    /**
     * Get a summary of the validation results.
     */
    public function getSummary(): array
    {
        return [
            'valid' => empty($this->errors),
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}