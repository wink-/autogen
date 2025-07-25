<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model;

class ValidationRuleGenerator
{
    /**
     * Default validation rule mappings for database types.
     */
    protected array $validationMappings = [
        'varchar' => 'string|max:{length}',
        'char' => 'string|size:{length}',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
        'tinytext' => 'string',
        'integer' => 'integer',
        'int' => 'integer',
        'tinyint' => 'integer|min:0|max:255',
        'smallint' => 'integer',
        'mediumint' => 'integer',
        'bigint' => 'integer',
        'decimal' => 'numeric',
        'numeric' => 'numeric',
        'float' => 'numeric',
        'double' => 'numeric',
        'real' => 'numeric',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'date' => 'date',
        'datetime' => 'date',
        'timestamp' => 'date',
        'time' => 'date_format:H:i:s',
        'year' => 'integer|min:1901|max:2155',
        'json' => 'json',
        'jsonb' => 'json',
        'enum' => 'in:{values}',
        'set' => 'array',
    ];

    /**
     * Field-specific validation rules.
     */
    protected array $fieldSpecificRules = [
        'email' => 'email|max:255',
        'password' => 'string|min:8',
        'phone' => 'string|max:20',
        'url' => 'url|max:255',
        'slug' => 'alpha_dash|max:255',
        'uuid' => 'uuid',
        'ip' => 'ip',
        'mac_address' => 'mac_address',
    ];

    /**
     * Generate validation rules for table columns.
     */
    public function generateRules(array $tableStructure): array
    {
        $rules = [];
        $primaryKey = $tableStructure['primary_key']['columns'][0] ?? null;
        $timestampColumns = ['created_at', 'updated_at', 'deleted_at'];
        
        foreach ($tableStructure['columns'] as $column) {
            $columnName = $column['name'];
            
            // Skip primary key and timestamp columns
            if ($columnName === $primaryKey || in_array($columnName, $timestampColumns)) {
                continue;
            }
            
            $columnRules = $this->generateColumnRules($column, $tableStructure);
            
            if (!empty($columnRules)) {
                $rules[$columnName] = $columnRules;
            }
        }
        
        return $rules;
    }

    /**
     * Generate validation rules for a specific column.
     */
    protected function generateColumnRules(array $column, array $tableStructure): array
    {
        $rules = [];
        $columnName = $column['name'];
        $type = strtolower($column['type']);
        $nullable = $column['nullable'] ?? false;
        
        // Add nullable rule if column is nullable
        if ($nullable) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }
        
        // Check for field-specific rules first
        foreach ($this->fieldSpecificRules as $fieldPattern => $rule) {
            if (str_contains($columnName, $fieldPattern)) {
                $rules[] = $rule;
                return $rules;
            }
        }
        
        // Apply type-based rules
        $typeRule = $this->getTypeRule($column);
        if ($typeRule) {
            $rules[] = $typeRule;
        }
        
        // Add unique constraint if applicable
        if ($this->hasUniqueIndex($columnName, $tableStructure)) {
            $tableName = $tableStructure['name'];
            $rules[] = "unique:{$tableName},{$columnName}";
        }
        
        // Add foreign key constraint if applicable
        $foreignKey = $this->getForeignKeyConstraint($columnName, $tableStructure);
        if ($foreignKey) {
            $rules[] = $foreignKey;
        }
        
        return $rules;
    }

    /**
     * Get validation rule for column type.
     */
    protected function getTypeRule(array $column): ?string
    {
        $type = strtolower($column['type']);
        $fullType = strtolower($column['full_type'] ?? $type);
        
        // Handle special cases
        if ($type === 'tinyint' && str_contains($fullType, '(1)')) {
            return 'boolean';
        }
        
        if ($type === 'enum') {
            $enumValues = $this->extractEnumValues($fullType);
            if ($enumValues) {
                return 'in:' . implode(',', $enumValues);
            }
        }
        
        if (in_array($type, ['decimal', 'numeric', 'float', 'double'])) {
            $rule = $this->validationMappings[$type] ?? 'numeric';
            
            // Add precision/scale constraints if available
            if (isset($column['precision']) && isset($column['scale'])) {
                $rule .= "|between:0,{$column['precision']}";
            }
            
            return $rule;
        }
        
        $rule = $this->validationMappings[$type] ?? null;
        
        if ($rule && str_contains($rule, '{length}') && isset($column['length'])) {
            $rule = str_replace('{length}', (string) $column['length'], $rule);
        }
        
        return $rule;
    }

    /**
     * Check if column has a unique index.
     */
    protected function hasUniqueIndex(string $columnName, array $tableStructure): bool
    {
        foreach ($tableStructure['indexes'] as $index) {
            if ($index['unique'] && in_array($columnName, $index['columns'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get foreign key validation constraint.
     */
    protected function getForeignKeyConstraint(string $columnName, array $tableStructure): ?string
    {
        foreach ($tableStructure['foreign_keys'] as $foreignKey) {
            if ($foreignKey['column'] === $columnName) {
                $foreignTable = $foreignKey['foreign_table'];
                $foreignColumn = $foreignKey['foreign_column'];
                return "exists:{$foreignTable},{$foreignColumn}";
            }
        }
        
        return null;
    }

    /**
     * Extract enum values from column definition.
     */
    protected function extractEnumValues(string $enumDefinition): array
    {
        // Match enum('value1','value2',...) pattern
        if (preg_match("/enum\(([^)]+)\)/i", $enumDefinition, $matches)) {
            $values = str_getcsv($matches[1], ',', "'");
            return array_map('trim', $values);
        }
        
        return [];
    }

    /**
     * Generate update validation rules (with ignore clauses for unique constraints).
     */
    public function generateUpdateRules(array $tableStructure, string $primaryKeyPlaceholder = '{id}'): array
    {
        $rules = $this->generateRules($tableStructure);
        
        // Modify unique rules to ignore current record
        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = array_map(function ($rule) use ($tableStructure, $primaryKeyPlaceholder, $field) {
                if (str_starts_with($rule, 'unique:')) {
                    $tableName = $tableStructure['name'];
                    return "unique:{$tableName},{$field},{$primaryKeyPlaceholder}";
                }
                return $rule;
            }, $fieldRules);
        }
        
        return $rules;
    }

    /**
     * Generate form request validation rules as PHP array code.
     */
    public function generateRulesAsCode(array $rules, int $indent = 8): string
    {
        if (empty($rules)) {
            return 'return [];';
        }
        
        $spacing = str_repeat(' ', $indent);
        $lines = [];
        
        foreach ($rules as $field => $fieldRules) {
            $rulesString = implode('|', $fieldRules);
            $lines[] = "'{$field}' => '{$rulesString}'";
        }
        
        return "return [\n" . 
               $spacing . implode(",\n" . $spacing, $lines) . 
               ",\n" . str_repeat(' ', $indent - 4) . "];";
    }

    /**
     * Generate custom validation messages.
     */
    public function generateCustomMessages(array $rules): array
    {
        $messages = [];
        
        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                if (str_starts_with($rule, 'unique:')) {
                    $messages["{$field}.unique"] = "The {$field} has already been taken.";
                } elseif (str_starts_with($rule, 'exists:')) {
                    $messages["{$field}.exists"] = "The selected {$field} is invalid.";
                } elseif ($rule === 'required') {
                    $messages["{$field}.required"] = "The {$field} field is required.";
                }
            }
        }
        
        return $messages;
    }
}