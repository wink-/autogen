<?php

declare(strict_types=1);

namespace AutoGen\Packages\Factory;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use AutoGen\Packages\Model\DatabaseIntrospector;

class FactoryGenerator
{
    /**
     * The fake data mapper instance.
     *
     * @var FakeDataMapper
     */
    protected FakeDataMapper $fakeDataMapper;

    /**
     * The relationship factory handler instance.
     *
     * @var RelationshipFactoryHandler
     */
    protected RelationshipFactoryHandler $relationshipHandler;

    /**
     * The database introspector instance.
     *
     * @var DatabaseIntrospector
     */
    protected DatabaseIntrospector $introspector;

    /**
     * Create a new factory generator instance.
     */
    public function __construct(
        FakeDataMapper $fakeDataMapper,
        RelationshipFactoryHandler $relationshipHandler,
        DatabaseIntrospector $introspector
    ) {
        $this->fakeDataMapper = $fakeDataMapper;
        $this->relationshipHandler = $relationshipHandler;
        $this->introspector = $introspector;
    }

    /**
     * Generate a factory for the given model.
     */
    public function generate(string $modelName, array $config): array
    {
        try {
            // Resolve model class
            $modelClass = $this->resolveModelClass($modelName);
            
            if (!$modelClass) {
                return [
                    'success' => false,
                    'error' => "Model class not found: {$modelName}"
                ];
            }

            // Get model metadata
            $modelMetadata = $this->getModelMetadata($modelClass);
            
            // Generate factory content
            $factoryContent = $this->generateFactoryContent($modelName, $modelMetadata, $config);
            
            // Write factory file
            $factoryPath = $this->getFactoryPath($modelName);
            $this->ensureDirectoryExists(dirname($factoryPath));
            
            File::put($factoryPath, $factoryContent);
            
            return [
                'success' => true,
                'path' => $factoryPath,
                'states' => $modelMetadata['states'] ?? [],
                'relationships' => $modelMetadata['relationships'] ?? []
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate the factory content.
     */
    protected function generateFactoryContent(string $modelName, array $metadata, array $config): string
    {
        $templateName = $this->getTemplateName($config['template']);
        $stubPath = $this->getStubPath($templateName);
        
        if (!File::exists($stubPath)) {
            throw new \RuntimeException("Factory template not found: {$stubPath}");
        }
        
        $stub = File::get($stubPath);
        
        // Replace placeholders
        $replacements = $this->getReplacements($modelName, $metadata, $config);
        
        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }
        
        return $stub;
    }

    /**
     * Get replacements for the factory template.
     */
    protected function getReplacements(string $modelName, array $metadata, array $config): array
    {
        $modelClass = $metadata['class'];
        $tableName = $metadata['table'];
        $fillableFields = $metadata['fillable'];
        
        return [
            '{{ namespace }}' => $this->getFactoryNamespace(),
            '{{ modelNamespace }}' => $this->getModelNamespace($modelClass),
            '{{ modelClass }}' => $modelName,
            '{{ factoryClass }}' => $modelName . 'Factory',
            '{{ definition }}' => $this->generateDefinition($fillableFields, $config),
            '{{ states }}' => $config['with_states'] ? $this->generateStates($metadata, $config) : '',
            '{{ relationships }}' => $config['with_relationships'] ? $this->generateRelationshipMethods($metadata, $config) : '',
            '{{ imports }}' => $this->generateImports($config),
        ];
    }

    /**
     * Generate the factory definition array.
     */
    protected function generateDefinition(array $fillableFields, array $config): string
    {
        $definitions = [];
        
        foreach ($fillableFields as $field) {
            $fakeData = $this->fakeDataMapper->mapFieldToFakeData($field, $config['locale']);
            $definitions[] = "            '{$field['name']}' => {$fakeData},";
        }
        
        return implode("\n", $definitions);
    }

    /**
     * Generate state methods.
     */
    protected function generateStates(array $metadata, array $config): string
    {
        $states = [];
        $fillableFields = $metadata['fillable'];
        
        // Generate common states based on field analysis
        $stateFields = $this->identifyStateFields($fillableFields);
        
        foreach ($stateFields as $field => $stateConfigs) {
            foreach ($stateConfigs as $stateName => $stateValue) {
                $methodName = Str::camel($stateName);
                
                $states[] = "
    /**
     * Indicate that the model is {$stateName}.
     */
    public function {$methodName}(): static
    {
        return \$this->state(fn (array \$attributes) => [
            '{$field}' => {$stateValue},
        ]);
    }";
            }
        }
        
        return implode("\n", $states);
    }

    /**
     * Generate relationship factory methods.
     */
    protected function generateRelationshipMethods(array $metadata, array $config): string
    {
        if (empty($metadata['relationships'])) {
            return '';
        }
        
        return $this->relationshipHandler->generateMethods($metadata['relationships'], $config);
    }

    /**
     * Generate necessary imports.
     */
    protected function generateImports(array $config): string
    {
        $imports = [
            'use Illuminate\Database\Eloquent\Factories\Factory;'
        ];
        
        if ($config['locale'] !== 'en_US') {
            $imports[] = 'use Faker\Generator as Faker;';
        }
        
        return implode("\n", $imports);
    }

    /**
     * Get model metadata including fillable fields, relationships, etc.
     */
    protected function getModelMetadata(string $modelClass): array
    {
        $model = new $modelClass();
        $reflection = new ReflectionClass($modelClass);
        
        // Get table name
        $tableName = $model->getTable();
        
        // Get fillable fields from database structure
        $fillableFields = $this->getFillableFieldsFromDatabase($modelClass, $tableName);
        
        // Get relationships
        $relationships = $this->getModelRelationships($reflection);
        
        // Identify state fields
        $stateFields = $this->identifyStateFields($fillableFields);
        
        return [
            'class' => $modelClass,
            'table' => $tableName,
            'fillable' => $fillableFields,
            'relationships' => $relationships,
            'states' => array_keys($stateFields),
        ];
    }

    /**
     * Get fillable fields from database structure.
     */
    protected function getFillableFieldsFromDatabase(string $modelClass, string $tableName): array
    {
        try {
            // Try to get from database introspection
            $connection = config('database.default');
            $tableStructure = $this->introspector->introspectTable($connection, $tableName);
            
            $fillableFields = [];
            foreach ($tableStructure['columns'] as $column) {
                // Skip timestamps and primary keys
                if (in_array($column['name'], ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }
                
                $fillableFields[] = [
                    'name' => $column['name'],
                    'type' => $column['type'],
                    'nullable' => $column['nullable'],
                    'default' => $column['default'] ?? null,
                ];
            }
            
            return $fillableFields;
            
        } catch (\Exception $e) {
            // Fallback to model's fillable property
            $model = new $modelClass();
            $fillable = $model->getFillable();
            
            return array_map(function ($field) {
                return [
                    'name' => $field,
                    'type' => 'string', // Default assumption
                    'nullable' => true,
                    'default' => null,
                ];
            }, $fillable);
        }
    }

    /**
     * Get model relationships through reflection.
     */
    protected function getModelRelationships(ReflectionClass $reflection): array
    {
        $relationships = [];
        
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && 
                !$method->isStatic() && 
                $method->getNumberOfParameters() === 0 &&
                !in_array($method->getName(), ['toArray', 'toJson', 'getAttribute'])) {
                
                // Check if method returns a relationship
                $returnType = $method->getReturnType();
                if ($returnType && 
                    str_contains($returnType->getName(), 'Illuminate\Database\Eloquent\Relations')) {
                    
                    $relationships[] = [
                        'method' => $method->getName(),
                        'type' => class_basename($returnType->getName()),
                    ];
                }
            }
        }
        
        return $relationships;
    }

    /**
     * Identify fields that can have states.
     */
    protected function identifyStateFields(array $fillableFields): array
    {
        $stateFields = [];
        
        foreach ($fillableFields as $field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'];
            
            // Status fields
            if (str_contains($fieldName, 'status')) {
                $stateFields[$fieldName] = [
                    'active' => "'active'",
                    'inactive' => "'inactive'",
                ];
            }
            
            // Boolean fields
            if (in_array($fieldType, ['boolean', 'tinyint(1)'])) {
                $baseName = str_replace(['is_', 'has_', 'can_'], '', $fieldName);
                $stateFields[$fieldName] = [
                    $baseName => 'true',
                    'without_' . $baseName => 'false',
                ];
            }
            
            // Email verification
            if ($fieldName === 'email_verified_at') {
                $stateFields[$fieldName] = [
                    'verified' => 'now()',
                    'unverified' => 'null',
                ];
            }
        }
        
        return $stateFields;
    }

    /**
     * Resolve the full model class name.
     */
    protected function resolveModelClass(string $modelName): ?string
    {
        // Try common model namespaces
        $namespaces = [
            'App\\Models\\',
            'App\\',
        ];
        
        foreach ($namespaces as $namespace) {
            $class = $namespace . $modelName;
            if (class_exists($class)) {
                return $class;
            }
        }
        
        return null;
    }

    /**
     * Get the factory file path.
     */
    public function getFactoryPath(string $modelName): string
    {
        return database_path("factories/{$modelName}Factory.php");
    }

    /**
     * Get the factory namespace.
     */
    protected function getFactoryNamespace(): string
    {
        return config('autogen.factory.namespace', 'Database\\Factories');
    }

    /**
     * Get the model namespace.
     */
    protected function getModelNamespace(string $modelClass): string
    {
        $reflection = new ReflectionClass($modelClass);
        return $reflection->getNamespaceName() . '\\' . $reflection->getShortName();
    }

    /**
     * Get the template name based on complexity.
     */
    protected function getTemplateName(string $template): string
    {
        $validTemplates = ['minimal', 'default', 'advanced'];
        
        return in_array($template, $validTemplates) ? $template : 'default';
    }

    /**
     * Get the stub file path.
     */
    protected function getStubPath(string $template): string
    {
        return __DIR__ . "/Stubs/factory.{$template}.stub";
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}