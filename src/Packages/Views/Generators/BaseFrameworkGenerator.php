<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views\Generators;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

abstract class BaseFrameworkGenerator
{
    protected array $columns;
    protected array $fillableFields;
    protected string $primaryKey;
    protected string $tableName;
    protected array $casts;
    protected array $columnTypes;

    public function __construct(
        protected Model $modelInstance,
        protected string $modelName,
        protected string $modelBaseName,
        protected string $routeName,
        protected string $layout,
        protected bool $withDatatable,
        protected bool $withSearch,
        protected bool $withModals
    ) {
        $this->initializeModelData();
    }

    protected function initializeModelData(): void
    {
        $this->fillableFields = $this->modelInstance->getFillable();
        $this->primaryKey = $this->modelInstance->getKeyName();
        $this->tableName = $this->modelInstance->getTable();
        $this->casts = $this->modelInstance->getCasts();
        
        // Get column information
        $this->columns = Schema::getColumnListing($this->tableName);
        $this->columnTypes = [];
        
        foreach ($this->columns as $column) {
            $this->columnTypes[$column] = Schema::getColumnType($this->tableName, $column);
        }
    }

    abstract public function generateView(string $viewType): string;

    public function generateCss(): string
    {
        return '';
    }

    protected function getFieldType(string $field): string
    {
        // Check casts first
        if (isset($this->casts[$field])) {
            return match ($this->casts[$field]) {
                'boolean' => 'checkbox',
                'date', 'datetime' => 'datetime-local',
                'array', 'json' => 'textarea',
                default => 'text'
            };
        }

        // Check column type
        $columnType = $this->columnTypes[$field] ?? 'string';
        
        return match ($columnType) {
            'integer', 'bigint', 'smallint' => 'number',
            'decimal', 'float', 'double' => 'number',
            'boolean' => 'checkbox',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime-local',
            'time' => 'time',
            'text', 'longtext', 'mediumtext' => 'textarea',
            'json' => 'textarea',
            default => $this->guessFieldTypeByName($field)
        };
    }

    protected function guessFieldTypeByName(string $field): string
    {
        $field = strtolower($field);
        
        return match (true) {
            str_contains($field, 'email') => 'email',
            str_contains($field, 'password') => 'password',
            str_contains($field, 'phone') || str_contains($field, 'tel') => 'tel',
            str_contains($field, 'url') || str_contains($field, 'website') => 'url',
            str_contains($field, 'color') => 'color',
            str_contains($field, 'price') || str_contains($field, 'amount') || str_contains($field, 'cost') => 'number',
            str_contains($field, 'description') || str_contains($field, 'body') || str_contains($field, 'content') => 'textarea',
            default => 'text'
        };
    }

    protected function getFieldLabel(string $field): string
    {
        return Str::title(str_replace('_', ' ', $field));
    }

    protected function getFieldPlaceholder(string $field): string
    {
        $label = $this->getFieldLabel($field);
        $type = $this->getFieldType($field);
        
        return match ($type) {
            'email' => 'example@domain.com',
            'url' => 'https://example.com',
            'tel', 'phone' => '+1 (555) 123-4567',
            'number' => '0',
            'date' => 'YYYY-MM-DD',
            'datetime-local' => 'YYYY-MM-DD HH:MM',
            'time' => 'HH:MM',
            default => "Enter {$label}"
        };
    }

    protected function isRequiredField(string $field): bool
    {
        // This is a simplified check. In a real implementation,
        // you would check validation rules from the FormRequest
        return !in_array($field, ['description', 'notes', 'bio', 'content', 'body']);
    }

    protected function getValidationAttributes(string $field): array
    {
        $attrs = [];
        $type = $this->getFieldType($field);
        
        if ($this->isRequiredField($field)) {
            $attrs[] = 'required';
        }
        
        // Add field-specific validation
        switch ($type) {
            case 'email':
                $attrs[] = 'pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,}$"';
                break;
            case 'tel':
                $attrs[] = 'pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}"';
                break;
            case 'number':
                if (str_contains($field, 'price') || str_contains($field, 'amount')) {
                    $attrs[] = 'step="0.01"';
                    $attrs[] = 'min="0"';
                }
                break;
        }
        
        // Add max length for text fields
        if (in_array($type, ['text', 'email', 'url', 'tel'])) {
            $maxLength = $this->getMaxLength($field);
            if ($maxLength > 0) {
                $attrs[] = "maxlength=\"{$maxLength}\"";
            }
        }
        
        return $attrs;
    }

    protected function getMaxLength(string $field): int
    {
        // This would ideally come from database schema
        return match (true) {
            str_contains($field, 'email') => 255,
            str_contains($field, 'phone') => 20,
            str_contains($field, 'url') => 255,
            default => 255
        };
    }

    protected function shouldShowInTable(string $field): bool
    {
        // Skip certain fields in table view
        $skipFields = [
            'password', 
            'remember_token', 
            'email_verified_at',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
        
        return !in_array($field, $skipFields);
    }

    protected function getTableColumns(): array
    {
        $columns = [$this->primaryKey];
        
        foreach ($this->fillableFields as $field) {
            if ($this->shouldShowInTable($field)) {
                $columns[] = $field;
            }
        }
        
        // Add timestamps if they exist
        if (in_array('created_at', $this->columns)) {
            $columns[] = 'created_at';
        }
        
        return array_slice($columns, 0, 6); // Limit to 6 columns for readability
    }

    protected function getModelVariable(): string
    {
        return Str::camel($this->modelBaseName);
    }

    protected function getModelPluralVariable(): string
    {
        return Str::plural($this->getModelVariable());
    }

    protected function getRouteResourceName(): string
    {
        return $this->routeName;
    }
}