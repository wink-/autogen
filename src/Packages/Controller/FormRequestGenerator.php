<?php

declare(strict_types=1);

namespace AutoGen\Packages\Controller;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FormRequestGenerator
{
    protected string $modelName;
    protected bool $force;
    protected array $generatedFiles = [];

    public function __construct(string $modelName, bool $force)
    {
        $this->modelName = $modelName;
        $this->force = $force;
    }

    /**
     * Generate form request classes.
     */
    public function generate(): array
    {
        $this->generateStoreRequest();
        $this->generateUpdateRequest();

        return $this->generatedFiles;
    }

    /**
     * Generate store request class.
     */
    protected function generateStoreRequest(): void
    {
        $stub = $this->getStub('store');
        $content = $this->replaceStubVariables($stub, 'store');
        $path = $this->getRequestPath('Store');

        $this->ensureDirectoryExists(dirname($path));

        if (File::exists($path) && !$this->force) {
            throw new \Exception("Store request already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Generate update request class.
     */
    protected function generateUpdateRequest(): void
    {
        $stub = $this->getStub('update');
        $content = $this->replaceStubVariables($stub, 'update');
        $path = $this->getRequestPath('Update');

        $this->ensureDirectoryExists(dirname($path));

        if (File::exists($path) && !$this->force) {
            throw new \Exception("Update request already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Get the stub file.
     */
    protected function getStub(string $type): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        $stubName = $type . '.stub';

        if ($customStubPath && File::exists($customStubPath . '/request/' . $stubName)) {
            return File::get($customStubPath . '/request/' . $stubName);
        }

        return File::get(__DIR__ . '/Stubs/request.' . $stubName);
    }

    /**
     * Replace variables in the stub.
     */
    protected function replaceStubVariables(string $stub, string $type): string
    {
        $replacements = [
            'namespace' => $this->getRequestNamespace(),
            'className' => ($type === 'store' ? 'Store' : 'Update') . $this->getModelClass() . 'Request',
            'modelClass' => $this->getModelClass(),
            'modelNamespace' => $this->getModelNamespace(),
            'rules' => $this->generateValidationRules($type),
            'messages' => $this->generateValidationMessages(),
            'attributes' => $this->generateAttributes(),
        ];

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Generate validation rules based on model's table schema.
     */
    protected function generateValidationRules(string $type): string
    {
        $modelClass = $this->getFullModelClass();
        
        if (!class_exists($modelClass)) {
            return $this->getDefaultRules($type);
        }

        try {
            $model = new $modelClass();
            $table = $model->getTable();
            $connection = $model->getConnection();
            
            $columns = Schema::connection($connection->getName())->getColumnListing($table);
            $rules = [];

            foreach ($columns as $column) {
                $columnType = Schema::connection($connection->getName())->getColumnType($table, $column);
                $rule = $this->getValidationRuleForColumn($column, $columnType, $table, $connection->getName(), $type);
                
                if ($rule) {
                    $rules[] = "            '{$column}' => {$rule},";
                }
            }

            return implode("\n", $rules);
        } catch (\Exception $e) {
            return $this->getDefaultRules($type);
        }
    }

    /**
     * Get validation rule for a specific column.
     */
    protected function getValidationRuleForColumn(
        string $column,
        string $columnType,
        string $table,
        string $connection,
        string $requestType
    ): ?string {
        // Skip auto-generated columns
        if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
            return null;
        }

        $rules = [];
        
        // Check if nullable
        $nullable = $this->isColumnNullable($table, $column, $connection);
        if ($nullable) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        // Add type-specific rules
        $typeRules = config('autogen.validation_rules.' . $columnType, 'string');
        if (is_string($typeRules)) {
            $rules[] = $typeRules;
        }

        // Special handling for email columns
        if (str_contains($column, 'email')) {
            $rules[] = 'email';
            $rules[] = 'max:255';
        }

        // Handle unique constraints
        if ($this->hasUniqueIndex($table, $column, $connection)) {
            if ($requestType === 'update') {
                $rules[] = "unique:{$table},{$column},\" . \$this->route('" . Str::camel($this->getModelClass()) . "')->id";
            } else {
                $rules[] = "unique:{$table},{$column}";
            }
        }

        // Handle file uploads
        if (in_array($column, ['avatar', 'image', 'photo', 'file', 'document'])) {
            $rules = ['nullable', 'file', 'max:2048'];
            if (in_array($column, ['avatar', 'image', 'photo'])) {
                $rules[] = 'image';
            }
        }

        return "['" . implode("', '", $rules) . "']";
    }

    /**
     * Check if column is nullable.
     */
    protected function isColumnNullable(string $table, string $column, string $connection): bool
    {
        try {
            $schema = \DB::connection($connection)
                ->getDoctrineSchemaManager()
                ->listTableDetails($table);
            
            return !$schema->getColumn($column)->getNotnull();
        } catch (\Exception $e) {
            return true; // Default to nullable if we can't determine
        }
    }

    /**
     * Check if column has unique index.
     */
    protected function hasUniqueIndex(string $table, string $column, string $connection): bool
    {
        try {
            $indexes = \DB::connection($connection)
                ->getDoctrineSchemaManager()
                ->listTableIndexes($table);
            
            foreach ($indexes as $index) {
                if ($index->isUnique() && in_array($column, $index->getColumns())) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get default validation rules.
     */
    protected function getDefaultRules(string $type): string
    {
        $rules = [
            "            'name' => ['required', 'string', 'max:255'],",
            "            'email' => ['required', 'email', 'max:255'" . ($type === 'store' ? ", 'unique:users'" : "") . "],",
            "            'status' => ['required', 'in:active,inactive'],",
        ];

        return implode("\n", $rules);
    }

    /**
     * Generate validation messages.
     */
    protected function generateValidationMessages(): string
    {
        return "            // Add custom validation messages here\n" .
               "            // 'email.required' => 'The email address is required.',\n" .
               "            // 'email.unique' => 'This email address is already registered.',";
    }

    /**
     * Generate custom attributes.
     */
    protected function generateAttributes(): string
    {
        return "            // Add custom attribute names here\n" .
               "            // 'email' => 'email address',";
    }

    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return class_basename($this->modelName);
    }

    /**
     * Get the full model class with namespace.
     */
    protected function getFullModelClass(): string
    {
        $modelPath = str_replace('/', '\\', $this->modelName);
        return 'App\\Models\\' . $modelPath;
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
     * Get the request namespace.
     */
    protected function getRequestNamespace(): string
    {
        $namespace = 'App\\Http\\Requests';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the request file path.
     */
    protected function getRequestPath(string $prefix): string
    {
        $path = app_path('Http/Requests');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $prefix . $this->getModelClass() . 'Request.php';
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