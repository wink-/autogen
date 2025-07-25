<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait AutoGenModelTrait
{
    /**
     * Boot the auto-gen model trait.
     */
    protected static function bootAutoGenModelTrait(): void
    {
        // Add any boot logic here
    }

    /**
     * Get the model's fillable attributes with intelligent guarding.
     */
    public function getAutoFillable(): array
    {
        $fillable = $this->getFillable();
        $guarded = $this->getGuarded();
        
        if (empty($fillable) && empty($guarded)) {
            // If no fillable/guarded specified, use all columns except primary key and timestamps
            $columns = $this->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($this->getTable());
            
            $exclude = [
                $this->getKeyName(),
                $this->getCreatedAtColumn(),
                $this->getUpdatedAtColumn(),
            ];
            
            if ($this->usesTimestamps() === false) {
                $exclude = [$this->getKeyName()];
            }
            
            return array_diff($columns, $exclude);
        }
        
        return $fillable;
    }

    /**
     * Scope query by multiple columns with AND condition.
     */
    public function scopeWhereColumns(Builder $query, array $conditions): Builder
    {
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }
        
        return $query;
    }

    /**
     * Scope query by multiple columns with OR condition.
     */
    public function scopeWhereAnyColumn(Builder $query, array $conditions): Builder
    {
        return $query->where(function ($q) use ($conditions) {
            foreach ($conditions as $column => $value) {
                if (is_array($value)) {
                    $q->orWhereIn($column, $value);
                } else {
                    $q->orWhere($column, $value);
                }
            }
        });
    }

    /**
     * Scope query to search across multiple text columns.
     */
    public function scopeSearch(Builder $query, string $search, array $columns = []): Builder
    {
        if (empty($search)) {
            return $query;
        }
        
        if (empty($columns)) {
            $columns = $this->getSearchableColumns();
        }
        
        return $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Get columns that are suitable for searching.
     */
    protected function getSearchableColumns(): array
    {
        $columns = $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable());
        
        // Filter to text-like columns
        $textColumns = [];
        foreach ($columns as $column) {
            if (in_array($column, ['name', 'title', 'description', 'content', 'email', 'slug'])) {
                $textColumns[] = $column;
            } elseif (Str::contains($column, ['name', 'title', 'description'])) {
                $textColumns[] = $column;
            }
        }
        
        return $textColumns;
    }

    /**
     * Scope for ordering by commonly used columns.
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        $columns = $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable());
        
        // Try to order by common columns in priority order
        $orderColumns = ['order', 'sort_order', 'position', 'created_at', 'id'];
        
        foreach ($orderColumns as $column) {
            if (in_array($column, $columns)) {
                return $query->orderBy($column, $direction);
            }
        }
        
        // Fallback to primary key
        return $query->orderBy($this->getKeyName(), $direction);
    }

    /**
     * Scope for recent records.
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->latest()->limit($limit);
    }

    /**
     * Scope for records created today.
     */
    public function scopeToday(Builder $query): Builder
    {
        if (!$this->usesTimestamps()) {
            return $query;
        }
        
        return $query->whereDate($this->getCreatedAtColumn(), today());
    }

    /**
     * Scope for records created this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        if (!$this->usesTimestamps()) {
            return $query;
        }
        
        return $query->whereBetween($this->getCreatedAtColumn(), [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope for records created this month.
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        if (!$this->usesTimestamps()) {
            return $query;
        }
        
        return $query->whereMonth($this->getCreatedAtColumn(), now()->month)
                    ->whereYear($this->getCreatedAtColumn(), now()->year);
    }

    /**
     * Get model data suitable for API responses.
     */
    public function toApiArray(): array
    {
        $attributes = $this->toArray();
        
        // Remove hidden attributes
        foreach ($this->getHidden() as $hidden) {
            unset($attributes[$hidden]);
        }
        
        // Format dates consistently
        foreach ($this->getDates() as $date) {
            if (isset($attributes[$date]) && $attributes[$date]) {
                $attributes[$date] = $this->asDateTime($attributes[$date])->toISOString();
            }
        }
        
        return $attributes;
    }

    /**
     * Create a new model instance with better error handling.
     */
    public static function createSafely(array $attributes): static|false
    {
        try {
            return static::create($attributes);
        } catch (\Exception $e) {
            // Log error or handle as needed
            logger()->error('Model creation failed', [
                'model' => static::class,
                'attributes' => $attributes,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Update model with better error handling.
     */
    public function updateSafely(array $attributes): bool
    {
        try {
            return $this->update($attributes);
        } catch (\Exception $e) {
            // Log error or handle as needed
            logger()->error('Model update failed', [
                'model' => static::class,
                'id' => $this->getKey(),
                'attributes' => $attributes,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Check if model has a specific relationship.
     */
    public function hasRelation(string $relation): bool
    {
        return method_exists($this, $relation);
    }

    /**
     * Get all available relationships for this model.
     */
    public function getRelationships(): array
    {
        $relationships = [];
        $methods = get_class_methods($this);
        
        foreach ($methods as $method) {
            if ($this->isRelationshipMethod($method)) {
                $relationships[] = $method;
            }
        }
        
        return $relationships;
    }

    /**
     * Check if a method is a relationship method.
     */
    protected function isRelationshipMethod(string $method): bool
    {
        if (in_array($method, ['relationLoaded', 'relationResolver', 'relations'])) {
            return false;
        }
        
        try {
            $reflection = new \ReflectionMethod($this, $method);
            
            if ($reflection->isPublic() && !$reflection->isStatic()) {
                $returnType = $reflection->getReturnType();
                
                if ($returnType && !$returnType->isBuiltin()) {
                    $typeName = $returnType->getName();
                    return Str::startsWith($typeName, 'Illuminate\\Database\\Eloquent\\Relations\\');
                }
            }
        } catch (\ReflectionException $e) {
            // Method doesn't exist or is not accessible
        }
        
        return false;
    }

    /**
     * Convert model to a format suitable for select options.
     */
    public function toSelectOption(): array
    {
        $labelColumn = $this->getSelectLabelColumn();
        
        return [
            'value' => $this->getKey(),
            'label' => $this->getAttribute($labelColumn),
        ];
    }

    /**
     * Get the column to use for select option labels.
     */
    protected function getSelectLabelColumn(): string
    {
        $columns = $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable());
        
        // Try common label columns in order of preference
        $labelColumns = ['name', 'title', 'label', 'display_name', 'description'];
        
        foreach ($labelColumns as $column) {
            if (in_array($column, $columns)) {
                return $column;
            }
        }
        
        // Fallback to primary key
        return $this->getKeyName();
    }
}