<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ModelGenerator
{
    /**
     * Default cast mappings for database types.
     */
    protected array $castMappings = [
        'tinyint(1)' => 'boolean',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'json' => 'array',
        'jsonb' => 'array',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
        'smallint' => 'integer',
        'bigint' => 'integer',
        'int' => 'integer',
        'integer' => 'integer',
        'decimal' => 'decimal:2',
        'numeric' => 'decimal:2',
        'float' => 'float',
        'double' => 'double',
        'real' => 'float',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'time' => 'string',
        'year' => 'integer',
    ];

    /**
     * Fields that should be hidden by default.
     */
    protected array $hiddenFields = [
        'password',
        'remember_token',
        'api_token',
        'token',
        'secret',
        'private_key',
    ];

    /**
     * Generate a model for the given table.
     */
    public function generate(
        string $connection,
        string $table,
        array $tableStructure,
        array $relationships,
        array $config
    ): void {
        $modelName = $this->getModelName($table);
        $namespace = $this->getNamespace($config);
        $modelPath = $this->getModelPath($table, $config);
        
        // Ensure directory exists
        $directory = dirname($modelPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        // Generate model content
        $content = $this->generateModelContent(
            $modelName,
            $namespace,
            $connection,
            $table,
            $tableStructure,
            $relationships,
            $config
        );
        
        File::put($modelPath, $content);
    }

    /**
     * Get the model name from table name.
     */
    public function getModelName(string $table): string
    {
        return Str::studly(Str::singular($table));
    }

    /**
     * Get the namespace for the model.
     */
    public function getNamespace(array $config): string
    {
        if (!empty($config['namespace'])) {
            return $config['namespace'];
        }
        
        $namespace = 'App\\Models';
        
        if (!empty($config['dir'])) {
            $namespace .= '\\' . str_replace('/', '\\', trim($config['dir'], '/'));
        }
        
        return $namespace;
    }

    /**
     * Get the file path for the model.
     */
    public function getModelPath(string $table, array $config): string
    {
        $modelName = $this->getModelName($table);
        $basePath = app_path('Models');
        
        if (!empty($config['dir'])) {
            $basePath .= '/' . trim($config['dir'], '/');
        }
        
        return $basePath . '/' . $modelName . '.php';
    }

    /**
     * Generate the complete model content.
     */
    protected function generateModelContent(
        string $modelName,
        string $namespace,
        string $connection,
        string $table,
        array $tableStructure,
        array $relationships,
        array $config
    ): string {
        $stub = $this->getStub();
        
        $replacements = [
            '{{namespace}}' => $namespace,
            '{{modelName}}' => $modelName,
            '{{connection}}' => $connection,
            '{{table}}' => $table,
            '{{uses}}' => $this->generateUses($tableStructure, $relationships, $config),
            '{{classDocBlock}}' => $this->generateClassDocBlock($tableStructure),
            '{{traits}}' => $this->generateTraits($tableStructure, $config),
            '{{constants}}' => $this->generateConstants($connection),
            '{{properties}}' => $this->generateProperties($tableStructure),
            '{{fillable}}' => $this->generateFillable($tableStructure),
            '{{hidden}}' => $this->generateHidden($tableStructure),
            '{{casts}}' => $this->generateCasts($tableStructure),
            '{{relationships}}' => $this->generateRelationships($relationships),
            '{{scopes}}' => $this->generateScopes($tableStructure, $config),
            '{{accessors}}' => $this->generateAccessors($tableStructure, $config),
            '{{mutators}}' => $this->generateMutators($tableStructure, $config),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Get the model stub template.
     */
    protected function getStub(): string
    {
        $stubPath = __DIR__ . '/Stubs/model.stub';
        
        if (File::exists($stubPath)) {
            return File::get($stubPath);
        }
        
        // Return default stub if file doesn't exist
        return $this->getDefaultStub();
    }

    /**
     * Generate use statements.
     */
    protected function generateUses(array $tableStructure, array $relationships, array $config): string
    {
        $uses = [
            'Illuminate\Database\Eloquent\Model',
            'Illuminate\Database\Eloquent\Factories\HasFactory',
        ];
        
        if ($tableStructure['has_soft_deletes']) {
            $uses[] = 'Illuminate\Database\Eloquent\SoftDeletes';
        }
        
        if ($config['with_validation'] ?? false) {
            $uses[] = 'Illuminate\Database\Eloquent\Casts\Attribute';
        }
        
        // Add relationship uses
        if (!empty($relationships)) {
            $uses[] = 'Illuminate\Database\Eloquent\Relations\BelongsTo';
            $uses[] = 'Illuminate\Database\Eloquent\Relations\HasMany';
            $uses[] = 'Illuminate\Database\Eloquent\Relations\BelongsToMany';
        }
        
        return implode("\n", array_map(fn($use) => "use {$use};", array_unique($uses)));
    }

    /**
     * Generate class-level PHPDoc comment.
     */
    protected function generateClassDocBlock(array $tableStructure): string
    {
        $doc = "/**\n";
        
        // Add property documentation
        foreach ($tableStructure['columns'] as $column) {
            $phpType = $this->getPhpType($column);
            $nullable = $column['nullable'] ? '?' : '';
            
            if ($column['name'] === $tableStructure['primary_key']['columns'][0] ?? null) {
                $doc .= " * @property-read {$nullable}{$phpType} \${$column['name']}\n";
            } else {
                $doc .= " * @property {$nullable}{$phpType} \${$column['name']}\n";
            }
        }
        
        // Add index documentation
        if (!empty($tableStructure['indexes'])) {
            $doc .= " *\n * Indexes:\n";
            foreach ($tableStructure['indexes'] as $index) {
                $columns = implode(', ', $index['columns']);
                $doc .= " * - {$index['name']} ({$columns})\n";
            }
        }
        
        $doc .= " */";
        
        return $doc;
    }

    /**
     * Generate trait usage.
     */
    protected function generateTraits(array $tableStructure, array $config): string
    {
        $traits = ['HasFactory'];
        
        if ($tableStructure['has_soft_deletes']) {
            $traits[] = 'SoftDeletes';
        }
        
        if (empty($traits)) {
            return '';
        }
        
        return 'use ' . implode(', ', $traits) . ';';
    }

    /**
     * Generate typed constants.
     */
    protected function generateConstants(string $connection): string
    {
        return "protected const string CONNECTION = '{$connection}';";
    }

    /**
     * Generate model properties.
     */
    protected function generateProperties(array $tableStructure): string
    {
        $properties = [];
        
        $properties[] = 'protected $connection = self::CONNECTION;';
        $properties[] = "protected \$table = '{$tableStructure['name']}';";
        
        if ($primaryKey = $tableStructure['primary_key']) {
            $keyName = $primaryKey['columns'][0];
            $properties[] = "protected readonly string \$primaryKey = '{$keyName}';";
            
            // Determine if auto-incrementing
            $primaryColumn = collect($tableStructure['columns'])->firstWhere('name', $keyName);
            if ($primaryColumn) {
                $incrementing = $primaryColumn['auto_increment'] ?? false;
                $properties[] = 'public readonly bool $incrementing = ' . ($incrementing ? 'true' : 'false') . ';';
                
                $keyType = $primaryColumn['type'] === 'int' || $primaryColumn['type'] === 'integer' ? 'int' : 'string';
                $properties[] = "protected readonly string \$keyType = '{$keyType}';";
            }
        }
        
        // Timestamps
        $hasTimestamps = $tableStructure['has_timestamps'];
        $properties[] = 'public $timestamps = ' . ($hasTimestamps ? 'true' : 'false') . ';';
        
        return implode("\n    ", $properties);
    }

    /**
     * Generate fillable array.
     */
    protected function generateFillable(array $tableStructure): string
    {
        $fillable = [];
        $primaryKey = $tableStructure['primary_key']['columns'][0] ?? null;
        $timestampColumns = ['created_at', 'updated_at', 'deleted_at'];
        
        foreach ($tableStructure['columns'] as $column) {
            $columnName = $column['name'];
            
            // Skip primary key and timestamp columns
            if ($columnName === $primaryKey || in_array($columnName, $timestampColumns)) {
                continue;
            }
            
            $fillable[] = "'{$columnName}'";
        }
        
        return $this->formatArray($fillable, 8);
    }

    /**
     * Generate hidden array.
     */
    protected function generateHidden(array $tableStructure): string
    {
        $hidden = [];
        $columnNames = array_column($tableStructure['columns'], 'name');
        
        foreach ($this->hiddenFields as $field) {
            if (in_array($field, $columnNames)) {
                $hidden[] = "'{$field}'";
            }
        }
        
        if (empty($hidden)) {
            return '[]';
        }
        
        return $this->formatArray($hidden, 8);
    }

    /**
     * Generate casts method.
     */
    protected function generateCasts(array $tableStructure): string
    {
        $casts = [];
        
        foreach ($tableStructure['columns'] as $column) {
            $cast = $this->getCastForColumn($column);
            if ($cast) {
                $casts[] = "'{$column['name']}' => '{$cast}'";
            }
        }
        
        if (empty($casts)) {
            return "return [];";
        }
        
        return "return [\n" . 
               str_repeat(' ', 12) . implode(",\n" . str_repeat(' ', 12), $casts) . "\n" .
               str_repeat(' ', 8) . "];";
    }

    /**
     * Generate relationship methods.
     */
    protected function generateRelationships(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }
        
        $methods = [];
        
        foreach ($relationships as $relationship) {
            $methods[] = $this->generateRelationshipMethod($relationship);
        }
        
        return implode("\n\n", $methods);
    }

    /**
     * Generate individual relationship method.
     */
    protected function generateRelationshipMethod(array $relationship): string
    {
        $type = $relationship['type'];
        $methodName = $relationship['method_name'];
        $relatedModel = $relationship['related_model'];
        
        $docComment = "    /**\n     * Get the {$relationship['description']}.\n     */";
        
        switch ($type) {
            case 'belongsTo':
                $foreignKey = $relationship['foreign_key'];
                $ownerKey = $relationship['owner_key'] ?? 'id';
                return "{$docComment}\n    public function {$methodName}(): BelongsTo\n    {\n        return \$this->belongsTo({$relatedModel}::class, '{$foreignKey}', '{$ownerKey}');\n    }";
                
            case 'hasMany':
                $foreignKey = $relationship['foreign_key'];
                $localKey = $relationship['local_key'] ?? 'id';
                return "{$docComment}\n    public function {$methodName}(): HasMany\n    {\n        return \$this->hasMany({$relatedModel}::class, '{$foreignKey}', '{$localKey}');\n    }";
                
            case 'belongsToMany':
                $pivotTable = $relationship['pivot_table'];
                $foreignPivotKey = $relationship['foreign_pivot_key'];
                $relatedPivotKey = $relationship['related_pivot_key'];
                return "{$docComment}\n    public function {$methodName}(): BelongsToMany\n    {\n        return \$this->belongsToMany({$relatedModel}::class, '{$pivotTable}', '{$foreignPivotKey}', '{$relatedPivotKey}');\n    }";
        }
        
        return '';
    }

    /**
     * Generate query scopes.
     */
    protected function generateScopes(array $tableStructure, array $config): string
    {
        if (!($config['with_scopes'] ?? false)) {
            return '';
        }
        
        $scopes = [];
        
        // Generate common scopes based on column names
        foreach ($tableStructure['columns'] as $column) {
            $columnName = $column['name'];
            
            if ($columnName === 'status') {
                $scopes[] = $this->generateStatusScope();
            } elseif ($columnName === 'is_active') {
                $scopes[] = $this->generateActiveScope();
            } elseif ($columnName === 'published_at') {
                $scopes[] = $this->generatePublishedScope();
            }
        }
        
        return implode("\n\n", $scopes);
    }

    /**
     * Generate accessor methods.
     */
    protected function generateAccessors(array $tableStructure, array $config): string
    {
        if (!($config['with_validation'] ?? false)) {
            return '';
        }
        
        $accessors = [];
        
        // Generate common accessors
        $columnNames = array_column($tableStructure['columns'], 'name');
        
        if (in_array('first_name', $columnNames) && in_array('last_name', $columnNames)) {
            $accessors[] = $this->generateFullNameAccessor();
        }
        
        return implode("\n\n", $accessors);
    }

    /**
     * Generate mutator methods.
     */
    protected function generateMutators(array $tableStructure, array $config): string
    {
        if (!($config['with_validation'] ?? false)) {
            return '';
        }
        
        $mutators = [];
        
        // Generate common mutators
        foreach ($tableStructure['columns'] as $column) {
            if ($column['name'] === 'password') {
                $mutators[] = $this->generatePasswordMutator();
            }
        }
        
        return implode("\n\n", $mutators);
    }

    /**
     * Get PHP type for a database column.
     */
    protected function getPhpType(array $column): string
    {
        $type = strtolower($column['type']);
        
        return match ($type) {
            'tinyint' => $column['length'] === 1 ? 'bool' : 'int',
            'boolean', 'bool' => 'bool',
            'smallint', 'mediumint', 'int', 'integer', 'bigint' => 'int',
            'decimal', 'numeric', 'float', 'double', 'real' => 'float',
            'date' => '\\Carbon\\Carbon',
            'datetime', 'timestamp' => '\\Carbon\\Carbon',
            'json', 'jsonb' => 'array',
            default => 'string',
        };
    }

    /**
     * Get cast type for a database column.
     */
    protected function getCastForColumn(array $column): ?string
    {
        $type = strtolower($column['type']);
        $fullType = strtolower($column['full_type'] ?? $type);
        
        // Handle special cases first
        if ($type === 'tinyint' && str_contains($fullType, '(1)')) {
            return 'boolean';
        }
        
        if ($type === 'decimal' || $type === 'numeric') {
            $scale = $column['scale'] ?? 2;
            return "decimal:{$scale}";
        }
        
        return $this->castMappings[$type] ?? null;
    }

    /**
     * Format array for code generation.
     */
    protected function formatArray(array $items, int $indent = 0): string
    {
        if (empty($items)) {
            return '[]';
        }
        
        $spacing = str_repeat(' ', $indent);
        return "[\n{$spacing}    " . 
               implode(",\n{$spacing}    ", $items) . 
               ",\n{$spacing}]";
    }

    /**
     * Generate status scope.
     */
    protected function generateStatusScope(): string
    {
        return "    /**\n     * Scope a query to only include records with specific status.\n     */\n    public function scopeStatus(\$query, string \$status)\n    {\n        return \$query->where('status', \$status);\n    }";
    }

    /**
     * Generate active scope.
     */
    protected function generateActiveScope(): string
    {
        return "    /**\n     * Scope a query to only include active records.\n     */\n    public function scopeActive(\$query)\n    {\n        return \$query->where('is_active', true);\n    }";
    }

    /**
     * Generate published scope.
     */
    protected function generatePublishedScope(): string
    {
        return "    /**\n     * Scope a query to only include published records.\n     */\n    public function scopePublished(\$query)\n    {\n        return \$query->whereNotNull('published_at')\n                     ->where('published_at', '<=', now());\n    }";
    }

    /**
     * Generate full name accessor.
     */
    protected function generateFullNameAccessor(): string
    {
        return "    /**\n     * Get the user's full name.\n     */\n    protected function fullName(): Attribute\n    {\n        return Attribute::make(\n            get: fn () => \$this->first_name . ' ' . \$this->last_name,\n        );\n    }";
    }

    /**
     * Generate password mutator.
     */
    protected function generatePasswordMutator(): string
    {
        return "    /**\n     * Set the password attribute.\n     */\n    protected function password(): Attribute\n    {\n        return Attribute::make(\n            set: fn (string \$value) => bcrypt(\$value),\n        );\n    }";
    }

    /**
     * Get default model stub template.
     */
    protected function getDefaultStub(): string
    {
        return <<<'STUB'
<?php

declare(strict_types=1);

namespace {{namespace}};

{{uses}}

{{classDocBlock}}
class {{modelName}} extends Model
{
    {{traits}}

    {{constants}}
    
    {{properties}}
    
    {{fillable}}
    
    {{hidden}}
    
    protected function casts(): array
    {
        {{casts}}
    }
    
    {{relationships}}
    
    {{scopes}}
    
    {{accessors}}
    
    {{mutators}}
}
STUB;
    }
}