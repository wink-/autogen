<?php

declare(strict_types=1);

namespace AutoGen\Packages\Factory;

use Illuminate\Support\Str;

class RelationshipFactoryHandler
{
    /**
     * Generate relationship factory methods.
     */
    public function generateMethods(array $relationships, array $config): string
    {
        $methods = [];
        
        foreach ($relationships as $relationship) {
            $methodName = $relationship['method'];
            $relationType = $relationship['type'];
            
            switch ($relationType) {
                case 'BelongsTo':
                    $methods[] = $this->generateBelongsToMethod($methodName, $relationship);
                    break;
                    
                case 'HasOne':
                    $methods[] = $this->generateHasOneMethod($methodName, $relationship);
                    break;
                    
                case 'HasMany':
                    $methods[] = $this->generateHasManyMethod($methodName, $relationship);
                    break;
                    
                case 'BelongsToMany':
                    $methods[] = $this->generateBelongsToManyMethod($methodName, $relationship);
                    break;
                    
                case 'HasManyThrough':
                    $methods[] = $this->generateHasManyThroughMethod($methodName, $relationship);
                    break;
                    
                case 'MorphTo':
                    $methods[] = $this->generateMorphToMethod($methodName, $relationship);
                    break;
                    
                case 'MorphOne':
                    $methods[] = $this->generateMorphOneMethod($methodName, $relationship);
                    break;
                    
                case 'MorphMany':
                    $methods[] = $this->generateMorphManyMethod($methodName, $relationship);
                    break;
            }
        }
        
        // Add convenience method to create with all relationships
        if (!empty($methods)) {
            $methods[] = $this->generateWithRelationshipsMethod($relationships);
        }
        
        return implode("\n", $methods);
    }

    /**
     * Generate BelongsTo relationship method.
     */
    protected function generateBelongsToMethod(string $methodName, array $relationship): string
    {
        $relatedModel = $this->guessRelatedModel($methodName);
        $foreignKey = $this->guessForeignKey($methodName);
        
        return "
    /**
     * Create with {$methodName} relationship.
     */
    public function with" . Str::studly($methodName) . "(\$attributes = []): static
    {
        return \$this->state(fn (array \$modelAttributes) => [
            '{$foreignKey}' => {$relatedModel}::factory()->create(\$attributes)->id,
        ]);
    }";
    }

    /**
     * Generate HasOne relationship method.
     */
    protected function generateHasOneMethod(string $methodName, array $relationship): string
    {
        $relatedModel = $this->guessRelatedModel($methodName);
        
        return "
    /**
     * Create with {$methodName} relationship.
     */
    public function with" . Str::studly($methodName) . "(\$attributes = []): static
    {
        return \$this->afterCreating(function (\$model) use (\$attributes) {
            {$relatedModel}::factory()->create(array_merge([
                \$model->getForeignKey() => \$model->id,
            ], \$attributes));
        });
    }";
    }

    /**
     * Generate HasMany relationship method.
     */
    protected function generateHasManyMethod(string $methodName, array $relationship): string
    {
        $relatedModel = $this->guessRelatedModel($methodName);
        $count = $this->guessDefaultCount($methodName);
        
        return "
    /**
     * Create with {$methodName} relationship.
     */
    public function with" . Str::studly($methodName) . "(\$count = {$count}, \$attributes = []): static
    {
        return \$this->afterCreating(function (\$model) use (\$count, \$attributes) {
            {$relatedModel}::factory()->count(\$count)->create(array_merge([
                \$model->getForeignKey() => \$model->id,
            ], \$attributes));
        });
    }";
    }

    /**
     * Generate BelongsToMany relationship method.
     */
    protected function generateBelongsToManyMethod(string $methodName, array $relationship): string
    {
        $relatedModel = $this->guessRelatedModel($methodName);
        $count = $this->guessDefaultCount($methodName);
        
        return "
    /**
     * Create with {$methodName} relationship.
     */
    public function with" . Str::studly($methodName) . "(\$count = {$count}, \$attributes = []): static
    {
        return \$this->afterCreating(function (\$model) use (\$count, \$attributes) {
            \$related = {$relatedModel}::factory()->count(\$count)->create(\$attributes);
            \$model->{$methodName}()->attach(\$related);
        });
    }";
    }

    /**
     * Generate HasManyThrough relationship method.
     */
    protected function generateHasManyThroughMethod(string $methodName, array $relationship): string
    {
        $relatedModel = $this->guessRelatedModel($methodName);
        $count = $this->guessDefaultCount($methodName);
        
        return "
    /**
     * Create with {$methodName} relationship (HasManyThrough).
     */
    public function with" . Str::studly($methodName) . "(\$count = {$count}, \$attributes = []): static
    {
        return \$this->afterCreating(function (\$model) use (\$count, \$attributes) {
            // Note: You may need to adjust this based on your intermediate model
            // {$relatedModel}::factory()->count(\$count)->create(\$attributes);
        });
    }";
    }

    /**
     * Generate MorphTo relationship method.
     */
    protected function generateMorphToMethod(string $methodName, array $relationship): string
    {
        return "
    /**
     * Create with {$methodName} relationship (MorphTo).
     */
    public function with" . Str::studly($methodName) . "(\$morphable): static
    {
        return \$this->state(fn (array \$attributes) => [
            '{$methodName}_type' => get_class(\$morphable),
            '{$methodName}_id' => \$morphable->id,
        ]);
    }";
    }

    /**
     * Generate MorphOne relationship method.
     */
    protected function generateMorphOneMethod(string $methodName, array $relationship): string
    {
        $relatedModel = $this->guessRelatedModel($methodName);
        
        return "
    /**
     * Create with {$methodName} relationship (MorphOne).
     */
    public function with" . Str::studly($methodName) . "(\$attributes = []): static
    {
        return \$this->afterCreating(function (\$model) use (\$attributes) {
            {$relatedModel}::factory()->create(array_merge([
                '{$methodName}_type' => get_class(\$model),
                '{$methodName}_id' => \$model->id,
            ], \$attributes));
        });
    }";
    }

    /**
     * Generate MorphMany relationship method.
     */
    protected function generateMorphManyMethod(string $methodName, array $relationship): string
    {
        $relatedModel = $this->guessRelatedModel($methodName);
        $count = $this->guessDefaultCount($methodName);
        
        return "
    /**
     * Create with {$methodName} relationship (MorphMany).
     */
    public function with" . Str::studly($methodName) . "(\$count = {$count}, \$attributes = []): static
    {
        return \$this->afterCreating(function (\$model) use (\$count, \$attributes) {
            {$relatedModel}::factory()->count(\$count)->create(array_merge([
                '{$methodName}_type' => get_class(\$model),
                '{$methodName}_id' => \$model->id,
            ], \$attributes));
        });
    }";
    }

    /**
     * Generate convenience method to create with all relationships.
     */
    protected function generateWithRelationshipsMethod(array $relationships): string
    {
        $relationshipCalls = [];
        
        foreach ($relationships as $relationship) {
            $methodName = $relationship['method'];
            $relationType = $relationship['type'];
            $studlyMethod = Str::studly($methodName);
            
            if (in_array($relationType, ['HasMany', 'BelongsToMany', 'MorphMany'])) {
                $relationshipCalls[] = "            ->with{$studlyMethod}(3)";
            } elseif ($relationType !== 'MorphTo') {
                $relationshipCalls[] = "            ->with{$studlyMethod}()";
            }
        }
        
        $chainedCalls = implode("\n", $relationshipCalls);
        
        return "
    /**
     * Create with all relationships populated.
     */
    public function withRelationships(): static
    {
        return \$this{$chainedCalls};
    }";
    }

    /**
     * Guess the related model class name from relationship method name.
     */
    protected function guessRelatedModel(string $methodName): string
    {
        // Convert method name to model class name
        $modelName = Str::studly(Str::singular($methodName));
        
        // Try to find the actual model class
        $possibleClasses = [
            "\\App\\Models\\{$modelName}",
            "\\App\\{$modelName}",
            $modelName,
        ];
        
        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return class_basename($class);
            }
        }
        
        // Fallback to the guessed name
        return $modelName;
    }

    /**
     * Guess the foreign key name from relationship method name.
     */
    protected function guessForeignKey(string $methodName): string
    {
        return Str::snake($methodName) . '_id';
    }

    /**
     * Guess default count for has-many relationships.
     */
    protected function guessDefaultCount(string $methodName): int
    {
        // Common relationship patterns and their typical counts
        $patterns = [
            'comments' => 5,
            'posts' => 3,
            'orders' => 2,
            'items' => 5,
            'tags' => 3,
            'categories' => 2,
            'images' => 4,
            'files' => 3,
        ];
        
        foreach ($patterns as $pattern => $count) {
            if (str_contains(strtolower($methodName), $pattern)) {
                return $count;
            }
        }
        
        return 3; // Default count
    }

    /**
     * Generate pivot table data for many-to-many relationships.
     */
    public function generatePivotData(string $relationshipName, array $pivotFields = []): string
    {
        if (empty($pivotFields)) {
            return '';
        }
        
        $pivotData = [];
        foreach ($pivotFields as $field) {
            // Simple mapping for pivot fields
            if ($field === 'created_at' || $field === 'updated_at') {
                $pivotData[] = "            '{$field}' => now(),";
            } else {
                $pivotData[] = "            '{$field}' => \$this->faker->word(),";
            }
        }
        
        $pivotDataString = implode("\n", $pivotData);
        
        return "
        // Pivot data for {$relationshipName}
        \$pivotData = [
{$pivotDataString}
        ];
        \$model->{$relationshipName}()->attach(\$related, \$pivotData);";
    }

    /**
     * Generate foreign key constraint handling.
     */
    public function generateForeignKeyConstraints(array $foreignKeys): string
    {
        $constraints = [];
        
        foreach ($foreignKeys as $foreignKey) {
            $relatedModel = $this->guessRelatedModel(str_replace('_id', '', $foreignKey));
            $constraints[] = "            '{$foreignKey}' => {$relatedModel}::factory(),";
        }
        
        return implode("\n", $constraints);
    }
}