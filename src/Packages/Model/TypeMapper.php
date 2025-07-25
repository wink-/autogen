<?php

declare(strict_types=1);

namespace AutoGen\Packages\Model;

class TypeMapper
{
    /**
     * Map database types to PHP types.
     */
    public static function getDatabaseToPhpTypeMap(): array
    {
        return [
            // String types
            'varchar' => 'string',
            'char' => 'string',
            'text' => 'string',
            'tinytext' => 'string',
            'mediumtext' => 'string',
            'longtext' => 'string',
            'string' => 'string',
            
            // Integer types
            'tinyint' => 'int',
            'smallint' => 'int',
            'mediumint' => 'int',
            'int' => 'int',
            'integer' => 'int',
            'bigint' => 'int',
            
            // Float types
            'decimal' => 'float',
            'numeric' => 'float',
            'float' => 'float',
            'double' => 'float',
            'real' => 'float',
            
            // Boolean types
            'boolean' => 'bool',
            'bool' => 'bool',
            
            // Date/Time types
            'date' => '\\Carbon\\Carbon',
            'datetime' => '\\Carbon\\Carbon',
            'timestamp' => '\\Carbon\\Carbon',
            'time' => 'string',
            'year' => 'int',
            
            // JSON types
            'json' => 'array',
            'jsonb' => 'array',
            
            // Binary types
            'binary' => 'string',
            'varbinary' => 'string',
            'blob' => 'string',
            'tinyblob' => 'string',
            'mediumblob' => 'string',
            'longblob' => 'string',
            
            // Other types
            'enum' => 'string',
            'set' => 'array',
            'uuid' => 'string',
        ];
    }

    /**
     * Map database types to Eloquent cast types.
     */
    public static function getDatabaseToCastTypeMap(): array
    {
        return [
            // Boolean types
            'boolean' => 'boolean',
            'bool' => 'boolean',
            'tinyint' => 'boolean', // Will be overridden for non-(1) tinyints
            
            // Integer types
            'smallint' => 'integer',
            'mediumint' => 'integer',
            'int' => 'integer',
            'integer' => 'integer',
            'bigint' => 'integer',
            
            // Float types
            'decimal' => 'decimal:2',
            'numeric' => 'decimal:2',
            'float' => 'float',
            'double' => 'double',
            'real' => 'float',
            
            // Date/Time types
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'datetime',
            
            // JSON types
            'json' => 'array',
            'jsonb' => 'array',
            
            // Collection types
            'set' => 'array',
        ];
    }

    /**
     * Get PHP type for a database column.
     */
    public static function getPhpType(array $column): string
    {
        $type = strtolower($column['type']);
        $fullType = strtolower($column['full_type'] ?? $type);
        $typeMap = self::getDatabaseToPhpTypeMap();
        
        // Special handling for tinyint(1) as boolean
        if ($type === 'tinyint') {
            if (isset($column['length']) && $column['length'] == 1) {
                return 'bool';
            }
            if (str_contains($fullType, '(1)')) {
                return 'bool';
            }
            return 'int';
        }
        
        return $typeMap[$type] ?? 'string';
    }

    /**
     * Get Eloquent cast type for a database column.
     */
    public static function getCastType(array $column): ?string
    {
        $type = strtolower($column['type']);
        $fullType = strtolower($column['full_type'] ?? $type);
        $castMap = self::getDatabaseToCastTypeMap();
        
        // Special handling for tinyint(1) as boolean
        if ($type === 'tinyint') {
            if (isset($column['length']) && $column['length'] == 1) {
                return 'boolean';
            }
            if (str_contains($fullType, '(1)')) {
                return 'boolean';
            }
            return 'integer';
        }
        
        // Special handling for decimal with custom precision
        if (in_array($type, ['decimal', 'numeric'])) {
            $scale = $column['scale'] ?? 2;
            return "decimal:{$scale}";
        }
        
        return $castMap[$type] ?? null;
    }

    /**
     * Determine if a column should be nullable in PHP.
     */
    public static function isNullable(array $column): bool
    {
        return $column['nullable'] ?? false;
    }

    /**
     * Get the default value for a column in PHP format.
     */
    public static function getDefaultValue(array $column): mixed
    {
        $defaultValue = $column['default'] ?? null;
        
        if ($defaultValue === null) {
            return null;
        }
        
        $type = strtolower($column['type']);
        
        // Handle special default values
        if (in_array(strtoupper($defaultValue), ['CURRENT_TIMESTAMP', 'NOW()'])) {
            return null; // Let Laravel handle these
        }
        
        // Type-specific default value handling
        switch ($type) {
            case 'boolean':
            case 'bool':
            case 'tinyint':
                if ($column['length'] == 1) {
                    return (bool) $defaultValue;
                }
                return (int) $defaultValue;
                
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
                return (int) $defaultValue;
                
            case 'decimal':
            case 'numeric':
            case 'float':
            case 'double':
            case 'real':
                return (float) $defaultValue;
                
            case 'json':
            case 'jsonb':
                return json_decode($defaultValue, true);
                
            default:
                // Remove quotes from string defaults
                return trim($defaultValue, "'\"");
        }
    }

    /**
     * Determine if a column is auto-incrementing.
     */
    public static function isAutoIncrement(array $column): bool
    {
        return $column['auto_increment'] ?? false;
    }

    /**
     * Determine if a column is unsigned.
     */
    public static function isUnsigned(array $column): bool
    {
        return $column['unsigned'] ?? false;
    }

    /**
     * Get appropriate Factory faker method for a column.
     */
    public static function getFactoryFakerMethod(array $column): string
    {
        $columnName = strtolower($column['name']);
        $type = strtolower($column['type']);
        
        // Column name-based mappings
        $nameBasedMethods = [
            'email' => 'safeEmail()',
            'password' => 'bcrypt("password")',
            'first_name' => 'firstName()',
            'last_name' => 'lastName()',
            'name' => 'name()',
            'title' => 'sentence(3)',
            'description' => 'paragraph()',
            'content' => 'paragraphs(3, true)',
            'phone' => 'phoneNumber()',
            'address' => 'address()',
            'city' => 'city()',
            'state' => 'state()',
            'country' => 'country()',
            'zip' => 'postcode()',
            'postal_code' => 'postcode()',
            'website' => 'url()',
            'url' => 'url()',
            'image' => 'imageUrl()',
            'avatar' => 'imageUrl(100, 100)',
            'slug' => 'slug()',
            'uuid' => 'uuid()',
            'ip' => 'ipv4()',
            'mac_address' => 'macAddress()',
            'company' => 'company()',
            'job_title' => 'jobTitle()',
            'department' => 'word()',
            'color' => 'hexColor()',
            'price' => 'randomFloat(2, 0, 1000)',
            'amount' => 'randomFloat(2, 0, 1000)',
            'quantity' => 'numberBetween(1, 100)',
            'score' => 'numberBetween(0, 100)',
            'rating' => 'numberBetween(1, 5)',
            'latitude' => 'latitude()',
            'longitude' => 'longitude()',
            'birthday' => 'date()',
            'birth_date' => 'date()',
        ];
        
        // Check for name-based matches first
        foreach ($nameBasedMethods as $pattern => $method) {
            if (str_contains($columnName, $pattern)) {
                return $method;
            }
        }
        
        // Type-based mappings
        return match ($type) {
            'boolean', 'bool' => 'boolean()',
            'tinyint' => $column['length'] == 1 ? 'boolean()' : 'numberBetween(0, 255)',
            'smallint' => 'numberBetween(-32768, 32767)',
            'mediumint' => 'numberBetween(-8388608, 8388607)',
            'int', 'integer' => 'numberBetween(1, 1000000)',
            'bigint' => 'numberBetween(1, 9223372036854775807)',
            'decimal', 'numeric', 'float', 'double', 'real' => 'randomFloat(2, 0, 1000)',
            'date' => 'date()',
            'datetime', 'timestamp' => 'dateTime()',
            'time' => 'time()',
            'year' => 'year()',
            'json', 'jsonb' => 'json([])',
            'enum' => 'randomElement(["option1", "option2", "option3"])',
            'set' => 'randomElements(["tag1", "tag2", "tag3"], 2)',
            'uuid' => 'uuid()',
            'text', 'tinytext' => 'text(200)',
            'mediumtext' => 'text(1000)',
            'longtext' => 'text(5000)',
            default => $column['length'] ? "text({$column['length']})" : 'word()',
        };
    }
}