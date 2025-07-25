<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RelationshipAnalyzer
{
    /**
     * The database introspector instance.
     */
    protected DatabaseIntrospector $introspector;

    /**
     * Create a new relationship analyzer instance.
     */
    public function __construct(DatabaseIntrospector $introspector)
    {
        $this->introspector = $introspector;
    }

    /**
     * Analyze relationships for all tables.
     */
    public function analyze(string $connection, array $tables): array
    {
        $allRelationships = [];
        $allTableStructures = [];
        
        // First, get all table structures
        foreach ($tables as $table) {
            $allTableStructures[$table] = $this->introspector->introspectTable($connection, $table);
        }
        
        // Then analyze relationships
        foreach ($tables as $table) {
            $allRelationships[$table] = $this->analyzeTableRelationships(
                $connection,
                $table,
                $allTableStructures,
                $tables
            );
        }
        
        return $allRelationships;
    }

    /**
     * Analyze relationships for a specific table.
     */
    public function analyzeTableRelationships(
        string $connection,
        string $table,
        array $allTableStructures,
        array $allTables
    ): array {
        $relationships = [];
        $tableStructure = $allTableStructures[$table];
        
        // Analyze belongsTo relationships (based on foreign keys)
        $relationships = array_merge(
            $relationships,
            $this->analyzeBelongsToRelationships($tableStructure, $allTableStructures, $allTables)
        );
        
        // Analyze hasMany relationships (reverse of belongsTo)
        $relationships = array_merge(
            $relationships,
            $this->analyzeHasManyRelationships($table, $allTableStructures, $allTables)
        );
        
        // Analyze belongsToMany relationships (pivot tables)
        $relationships = array_merge(
            $relationships,
            $this->analyzeBelongsToManyRelationships($table, $allTableStructures, $allTables)
        );
        
        // Analyze polymorphic relationships
        $relationships = array_merge(
            $relationships,
            $this->analyzePolymorphicRelationships($table, $tableStructure, $allTableStructures, $allTables)
        );
        
        return $relationships;
    }

    /**
     * Analyze belongsTo relationships based on foreign keys.
     */
    protected function analyzeBelongsToRelationships(
        array $tableStructure,
        array $allTableStructures,
        array $allTables
    ): array {
        $relationships = [];
        
        foreach ($tableStructure['foreign_keys'] as $foreignKey) {
            $foreignTable = $foreignKey['foreign_table'];
            $foreignColumn = $foreignKey['foreign_column'];
            $localColumn = $foreignKey['column'];
            
            // Skip if the foreign table is not in our list
            if (!in_array($foreignTable, $allTables)) {
                continue;
            }
            
            $modelName = $this->getModelName($foreignTable);
            $methodName = $this->getBelongsToMethodName($localColumn, $foreignTable);
            
            $relationships[] = [
                'type' => 'belongsTo',
                'method_name' => $methodName,
                'related_model' => $modelName,
                'foreign_key' => $localColumn,
                'owner_key' => $foreignColumn,
                'description' => "related " . Str::singular($foreignTable),
            ];
        }
        
        // Also check for conventional foreign keys (ending with _id)
        foreach ($tableStructure['columns'] as $column) {
            if (!str_ends_with($column['name'], '_id')) {
                continue;
            }
            
            // Skip if already handled by explicit foreign key
            $alreadyHandled = collect($tableStructure['foreign_keys'])
                ->contains('column', $column['name']);
            
            if ($alreadyHandled) {
                continue;
            }
            
            $foreignTable = $this->guessForeignTable($column['name']);
            
            if (!in_array($foreignTable, $allTables)) {
                continue;
            }
            
            $modelName = $this->getModelName($foreignTable);
            $methodName = $this->getBelongsToMethodName($column['name'], $foreignTable);
            
            $relationships[] = [
                'type' => 'belongsTo',
                'method_name' => $methodName,
                'related_model' => $modelName,
                'foreign_key' => $column['name'],
                'owner_key' => 'id',
                'description' => "related " . Str::singular($foreignTable),
            ];
        }
        
        return $relationships;
    }

    /**
     * Analyze hasMany relationships (reverse of belongsTo).
     */
    protected function analyzeHasManyRelationships(
        string $table,
        array $allTableStructures,
        array $allTables
    ): array {
        $relationships = [];
        $tableSingular = Str::singular($table);
        $expectedForeignKey = $tableSingular . '_id';
        
        foreach ($allTables as $otherTable) {
            if ($otherTable === $table) {
                continue;
            }
            
            $otherStructure = $allTableStructures[$otherTable];
            
            // Check if other table has a foreign key pointing to this table
            $hasForeignKey = false;
            
            // Check explicit foreign keys
            foreach ($otherStructure['foreign_keys'] as $foreignKey) {
                if ($foreignKey['foreign_table'] === $table) {
                    $modelName = $this->getModelName($otherTable);
                    $methodName = $this->getHasManyMethodName($otherTable);
                    
                    $relationships[] = [
                        'type' => 'hasMany',
                        'method_name' => $methodName,
                        'related_model' => $modelName,
                        'foreign_key' => $foreignKey['column'],
                        'local_key' => $foreignKey['foreign_column'],
                        'description' => "associated " . Str::plural($otherTable),
                    ];
                    
                    $hasForeignKey = true;
                    break;
                }
            }
            
            // Check conventional foreign keys
            if (!$hasForeignKey) {
                $otherColumns = array_column($otherStructure['columns'], 'name');
                
                if (in_array($expectedForeignKey, $otherColumns)) {
                    $modelName = $this->getModelName($otherTable);
                    $methodName = $this->getHasManyMethodName($otherTable);
                    
                    $relationships[] = [
                        'type' => 'hasMany',
                        'method_name' => $methodName,
                        'related_model' => $modelName,
                        'foreign_key' => $expectedForeignKey,
                        'local_key' => 'id',
                        'description' => "associated " . Str::plural($otherTable),
                    ];
                }
            }
        }
        
        return $relationships;
    }

    /**
     * Analyze belongsToMany relationships (many-to-many via pivot tables).
     */
    protected function analyzeBelongsToManyRelationships(
        string $table,
        array $allTableStructures,
        array $allTables
    ): array {
        $relationships = [];
        
        foreach ($allTables as $otherTable) {
            if ($otherTable === $table) {
                continue;
            }
            
            $pivotTable = $this->guessPivotTableName($table, $otherTable);
            
            if (!in_array($pivotTable, $allTables)) {
                continue;
            }
            
            // Check if pivot table exists and has the expected structure
            if (!$this->isPivotTable($pivotTable, $table, $otherTable, $allTableStructures)) {
                continue;
            }
            
            $modelName = $this->getModelName($otherTable);
            $methodName = $this->getBelongsToManyMethodName($otherTable);
            
            $tableSingular = Str::singular($table);
            $otherTableSingular = Str::singular($otherTable);
            
            $relationships[] = [
                'type' => 'belongsToMany',
                'method_name' => $methodName,
                'related_model' => $modelName,
                'pivot_table' => $pivotTable,
                'foreign_pivot_key' => $tableSingular . '_id',
                'related_pivot_key' => $otherTableSingular . '_id',
                'description' => "associated " . Str::plural($otherTable) . " (many-to-many)",
            ];
        }
        
        return $relationships;
    }

    /**
     * Analyze polymorphic relationships.
     */
    protected function analyzePolymorphicRelationships(
        string $table,
        array $tableStructure,
        array $allTableStructures,
        array $allTables
    ): array {
        $relationships = [];
        
        // Look for morphable columns (ending with _type and _id)
        $columns = array_column($tableStructure['columns'], 'name');
        
        foreach ($columns as $column) {
            if (str_ends_with($column, '_type')) {
                $morphName = substr($column, 0, -5); // Remove '_type'
                $morphIdColumn = $morphName . '_id';
                
                if (in_array($morphIdColumn, $columns)) {
                    $methodName = $this->getMorphMethodName($morphName);
                    
                    $relationships[] = [
                        'type' => 'morphTo',
                        'method_name' => $methodName,
                        'morph_name' => $morphName,
                        'description' => "polymorphic relationship for {$morphName}",
                    ];
                }
            }
        }
        
        // Look for reverse polymorphic relationships (morphMany, morphOne)
        foreach ($allTables as $otherTable) {
            if ($otherTable === $table) {
                continue;
            }
            
            $otherStructure = $allTableStructures[$otherTable];
            $otherColumns = array_column($otherStructure['columns'], 'name');
            
            // Check if other table has polymorphic columns that could point to this table
            foreach ($otherColumns as $column) {
                if (str_ends_with($column, '_type')) {
                    $morphName = substr($column, 0, -5);
                    $morphIdColumn = $morphName . '_id';
                    
                    if (in_array($morphIdColumn, $otherColumns)) {
                        $modelName = $this->getModelName($otherTable);
                        $methodName = $this->getMorphManyMethodName($otherTable, $morphName);
                        
                        $relationships[] = [
                            'type' => 'morphMany',
                            'method_name' => $methodName,
                            'related_model' => $modelName,
                            'morph_name' => $morphName,
                            'description' => "polymorphic relationship to " . Str::plural($otherTable),
                        ];
                    }
                }
            }
        }
        
        return $relationships;
    }

    /**
     * Get model name from table name.
     */
    protected function getModelName(string $table): string
    {
        return Str::studly(Str::singular($table));
    }

    /**
     * Generate belongsTo method name.
     */
    protected function getBelongsToMethodName(string $foreignKey, string $foreignTable): string
    {
        // Remove _id suffix if present
        if (str_ends_with($foreignKey, '_id')) {
            $methodName = substr($foreignKey, 0, -3);
        } else {
            $methodName = Str::singular($foreignTable);
        }
        
        return Str::camel($methodName);
    }

    /**
     * Generate hasMany method name.
     */
    protected function getHasManyMethodName(string $relatedTable): string
    {
        return Str::camel(Str::plural($relatedTable));
    }

    /**
     * Generate belongsToMany method name.
     */
    protected function getBelongsToManyMethodName(string $relatedTable): string
    {
        return Str::camel(Str::plural($relatedTable));
    }

    /**
     * Generate morphTo method name.
     */
    protected function getMorphMethodName(string $morphName): string
    {
        return Str::camel($morphName);
    }

    /**
     * Generate morphMany method name.
     */
    protected function getMorphManyMethodName(string $relectedTable, string $morphName): string
    {
        return Str::camel(Str::plural($relectedTable));
    }

    /**
     * Guess foreign table name from foreign key column.
     */
    protected function guessForeignTable(string $foreignKey): string
    {
        if (str_ends_with($foreignKey, '_id')) {
            $tableName = substr($foreignKey, 0, -3);
            return Str::plural($tableName);
        }
        
        return $foreignKey;
    }

    /**
     * Guess pivot table name for two tables.
     */
    protected function guessPivotTableName(string $table1, string $table2): string
    {
        $tables = [Str::singular($table1), Str::singular($table2)];
        sort($tables);
        
        return implode('_', $tables);
    }

    /**
     * Check if a table is a pivot table.
     */
    protected function isPivotTable(
        string $pivotTable,
        string $table1,
        string $table2,
        array $allTableStructures
    ): bool {
        if (!isset($allTableStructures[$pivotTable])) {
            return false;
        }
        
        $pivotStructure = $allTableStructures[$pivotTable];
        $columns = array_column($pivotStructure['columns'], 'name');
        
        $table1Singular = Str::singular($table1);
        $table2Singular = Str::singular($table2);
        
        $expectedColumns = [
            $table1Singular . '_id',
            $table2Singular . '_id',
        ];
        
        // Check if pivot table has the expected foreign key columns
        foreach ($expectedColumns as $expectedColumn) {
            if (!in_array($expectedColumn, $columns)) {
                return false;
            }
        }
        
        // A pivot table typically only has foreign keys and maybe timestamps
        $nonPivotColumns = array_filter($columns, function ($column) use ($expectedColumns) {
            return !in_array($column, $expectedColumns) &&
                   !in_array($column, ['created_at', 'updated_at', 'deleted_at']);
        });
        
        // If there are too many non-pivot columns, it's probably not a pure pivot table
        return count($nonPivotColumns) <= 2;
    }
}