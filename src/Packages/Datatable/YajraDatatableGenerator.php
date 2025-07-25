<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class YajraDatatableGenerator
{
    protected string $modelName;
    protected array $options;
    protected array $generatedFiles = [];

    public function __construct(string $modelName, array $options = [])
    {
        $this->modelName = $modelName;
        $this->options = $options;
    }

    /**
     * Generate Yajra DataTable files.
     */
    public function generate(): array
    {
        $this->generateDataTableClass();
        
        return $this->generatedFiles;
    }

    /**
     * Generate the DataTable class.
     */
    protected function generateDataTableClass(): void
    {
        $path = $this->getDataTablePath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("DataTable already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Get the appropriate stub file.
     */
    protected function getStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/yajra.datatable.stub')) {
            return File::get($customStubPath . '/datatable/yajra.datatable.stub');
        }

        return File::get(__DIR__ . '/Stubs/yajra.datatable.stub');
    }

    /**
     * Replace variables in the stub.
     */
    protected function replaceStubVariables(string $stub): string
    {
        $replacements = $this->getReplacements();

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Get all replacement variables.
     */
    protected function getReplacements(): array
    {
        $modelClass = $this->getModelClass();
        $modelNamespace = $this->getModelNamespace();
        $datatableNamespace = $this->getDataTableNamespace();
        $datatableName = $modelClass . 'DataTable';
        $modelVariable = $this->getModelVariable();
        $routeName = $this->getRouteName();

        $replacements = [
            'namespace' => $datatableNamespace,
            'modelNamespace' => $modelNamespace,
            'modelClass' => $modelClass,
            'datatableName' => $datatableName,
            'modelVariable' => $modelVariable,
            'routeName' => $routeName,
            'columns' => $this->getColumnsDefinition(),
            'searchableColumns' => $this->getSearchableColumns(),
            'queryOptimizations' => $this->getQueryOptimizations(),
            'cacheImplementation' => $this->getCacheImplementation(),
            'exportColumns' => $this->getExportColumns(),
            'bulkActions' => $this->getBulkActions(),
        ];

        return $replacements;
    }

    /**
     * Get columns definition based on model.
     */
    protected function getColumnsDefinition(): string
    {
        // This would typically introspect the model to get actual columns
        // For now, we'll provide a basic structure
        $columns = [
            'id' => ['title' => 'ID', 'searchable' => false, 'orderable' => true],
            'name' => ['title' => 'Name', 'searchable' => true, 'orderable' => true],
            'email' => ['title' => 'Email', 'searchable' => true, 'orderable' => true],
            'created_at' => ['title' => 'Created', 'searchable' => false, 'orderable' => true],
            'updated_at' => ['title' => 'Updated', 'searchable' => false, 'orderable' => true],
        ];

        if ($this->options['withBulk'] ?? false) {
            $columns = ['select' => ['title' => '', 'searchable' => false, 'orderable' => false]] + $columns;
        }

        $columnDefinitions = [];
        foreach ($columns as $column => $config) {
            $searchable = $config['searchable'] ? 'true' : 'false';
            $orderable = $config['orderable'] ? 'true' : 'false';
            
            if ($column === 'select') {
                $columnDefinitions[] = "            Column::checkbox('select')
                ->title('<input type=\"checkbox\" id=\"select-all\">')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width(30)";
            } elseif ($column === 'created_at' || $column === 'updated_at') {
                $columnDefinitions[] = "            Column::make('{$column}')
                ->title('{$config['title']}')
                ->searchable({$searchable})
                ->orderable({$orderable})
                ->addClass('text-center')
                ->renderWith(function (\$data) {
                    return \$data->{$column}->format('Y-m-d H:i:s');
                })";
            } else {
                $columnDefinitions[] = "            Column::make('{$column}')
                ->title('{$config['title']}')
                ->searchable({$searchable})
                ->orderable({$orderable})";
            }
        }

        // Add actions column
        $columnDefinitions[] = "            Column::computed('actions')
                ->title('Actions')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width(100)";

        return implode(",\n", $columnDefinitions);
    }

    /**
     * Get searchable columns for advanced search.
     */
    protected function getSearchableColumns(): string
    {
        if (!($this->options['withSearch'] ?? false)) {
            return '';
        }

        return "
        // Advanced search implementation
        if (\$request->has('search_name') && \$request->get('search_name')) {
            \$query->where('name', 'like', '%' . \$request->get('search_name') . '%');
        }
        
        if (\$request->has('search_email') && \$request->get('search_email')) {
            \$query->where('email', 'like', '%' . \$request->get('search_email') . '%');
        }
        
        if (\$request->has('date_from') && \$request->get('date_from')) {
            \$query->whereDate('created_at', '>=', \$request->get('date_from'));
        }
        
        if (\$request->has('date_to') && \$request->get('date_to')) {
            \$query->whereDate('created_at', '<=', \$request->get('date_to'));
        }";
    }

    /**
     * Get query optimizations.
     */
    protected function getQueryOptimizations(): string
    {
        $optimizations = [];

        // Add select specific columns for performance
        $optimizations[] = "        \$query->select(['id', 'name', 'email', 'created_at', 'updated_at']);";

        // Add cursor pagination if enabled
        if ($this->options['cursorPagination'] ?? false) {
            $optimizations[] = "        
        // Cursor-based pagination for better performance
        if (\$request->has('cursor')) {
            \$query->where('id', '>', \$request->get('cursor'));
        }
        
        \$query->orderBy('id', 'asc');";
        }

        // Add database-specific optimizations
        $optimizations[] = "        
        // Database-specific optimizations
        \$query->when(config('database.default') === 'mysql', function (\$q) {
            \$q->addSelect(DB::raw('SQL_CALC_FOUND_ROWS *'));
        });";

        return implode("\n", $optimizations);
    }

    /**
     * Get cache implementation.
     */
    protected function getCacheImplementation(): string
    {
        if (!($this->options['cache'] ?? false)) {
            return '';
        }

        return "
    /**
     * Cache the query results for better performance.
     */
    protected function cacheQuery(\$query, \$request)
    {
        \$cacheKey = 'datatable:' . \$this->getTableId() . ':' . md5(serialize(\$request->all()));
        \$cacheDuration = config('datatables.cache.duration', 300); // 5 minutes
        
        return Cache::tags(['datatables', \$this->getTableId()])
            ->remember(\$cacheKey, \$cacheDuration, function () use (\$query) {
                return \$query->get();
            });
    }
    
    /**
     * Clear cache when data is modified.
     */
    public function clearCache()
    {
        Cache::tags(['datatables', \$this->getTableId()])->flush();
    }";
    }

    /**
     * Get export columns definition.
     */
    protected function getExportColumns(): string
    {
        if (!($this->options['withExports'] ?? false)) {
            return '';
        }

        return "
    /**
     * Get export columns.
     */
    protected function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }";
    }

    /**
     * Get bulk actions implementation.
     */
    protected function getBulkActions(): string
    {
        if (!($this->options['withBulk'] ?? false)) {
            return '';
        }

        return "
    /**
     * Handle bulk actions.
     */
    public function bulkAction(Request \$request)
    {
        \$action = \$request->get('action');
        \$ids = \$request->get('ids', []);
        
        if (empty(\$ids)) {
            return response()->json(['message' => 'No items selected'], 400);
        }
        
        switch (\$action) {
            case 'delete':
                {$this->getModelClass()}::whereIn('id', \$ids)->delete();
                \$message = 'Selected items deleted successfully';
                break;
                
            case 'activate':
                {$this->getModelClass()}::whereIn('id', \$ids)->update(['status' => 'active']);
                \$message = 'Selected items activated successfully';
                break;
                
            case 'deactivate':
                {$this->getModelClass()}::whereIn('id', \$ids)->update(['status' => 'inactive']);
                \$message = 'Selected items deactivated successfully';
                break;
                
            default:
                return response()->json(['message' => 'Invalid action'], 400);
        }
        
        // Clear cache after bulk operations
        \$this->clearCache();
        
        return response()->json(['message' => \$message]);
    }";
    }

    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return class_basename($this->modelName);
    }

    /**
     * Get the model namespace.
     */
    protected function getModelNamespace(): string
    {
        $modelPath = str_replace('/', '\\', $this->modelName);
        $namespace = 'App\\Models';

        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the DataTable namespace.
     */
    protected function getDataTableNamespace(): string
    {
        $namespace = 'App\\DataTables';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the model variable name.
     */
    protected function getModelVariable(): string
    {
        return Str::camel($this->getModelClass());
    }

    /**
     * Get the route name prefix.
     */
    protected function getRouteName(): string
    {
        $parts = explode('/', $this->modelName);
        $names = array_map(fn($part) => Str::kebab($part), $parts);
        return implode('.', $names);
    }

    /**
     * Get the DataTable file path.
     */
    protected function getDataTablePath(): string
    {
        $path = app_path('DataTables');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getModelClass() . 'DataTable.php';
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}