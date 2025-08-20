<?php

declare(strict_types=1);

namespace AutoGen\Common\Enums;

/**
 * Column Type Enum
 * 
 * Represents database column types with their PHP and validation mappings.
 */
enum ColumnType: string
{
    // Numeric types
    case TinyInt = 'tinyint';
    case SmallInt = 'smallint';
    case MediumInt = 'mediumint';
    case Int = 'int';
    case Integer = 'integer';
    case BigInt = 'bigint';
    case Decimal = 'decimal';
    case Numeric = 'numeric';
    case Float = 'float';
    case Double = 'double';
    case Real = 'real';
    case Bit = 'bit';
    
    // String types
    case Char = 'char';
    case VarChar = 'varchar';
    case TinyText = 'tinytext';
    case Text = 'text';
    case MediumText = 'mediumtext';
    case LongText = 'longtext';
    case Binary = 'binary';
    case VarBinary = 'varbinary';
    case TinyBlob = 'tinyblob';
    case Blob = 'blob';
    case MediumBlob = 'mediumblob';
    case LongBlob = 'longblob';
    
    // Date and time types
    case Date = 'date';
    case DateTime = 'datetime';
    case Timestamp = 'timestamp';
    case Time = 'time';
    case Year = 'year';
    
    // Special types
    case Enum = 'enum';
    case Set = 'set';
    case Json = 'json';
    case JsonB = 'jsonb';
    case Boolean = 'boolean';
    case UUID = 'uuid';
    
    // Spatial types
    case Geometry = 'geometry';
    case Point = 'point';
    case LineString = 'linestring';
    case Polygon = 'polygon';
    case MultiPoint = 'multipoint';
    case MultiLineString = 'multilinestring';
    case MultiPolygon = 'multipolygon';
    case GeometryCollection = 'geometrycollection';

    /**
     * Get the display label for the column type.
     */
    public function label(): string
    {
        return match ($this) {
            self::TinyInt => 'Tiny Integer',
            self::SmallInt => 'Small Integer',
            self::MediumInt => 'Medium Integer',
            self::Int, self::Integer => 'Integer',
            self::BigInt => 'Big Integer',
            self::Decimal, self::Numeric => 'Decimal',
            self::Float => 'Float',
            self::Double, self::Real => 'Double',
            self::Bit => 'Bit',
            self::Char => 'Character',
            self::VarChar => 'Variable Character',
            self::TinyText => 'Tiny Text',
            self::Text => 'Text',
            self::MediumText => 'Medium Text',
            self::LongText => 'Long Text',
            self::Binary => 'Binary',
            self::VarBinary => 'Variable Binary',
            self::TinyBlob => 'Tiny BLOB',
            self::Blob => 'BLOB',
            self::MediumBlob => 'Medium BLOB',
            self::LongBlob => 'Long BLOB',
            self::Date => 'Date',
            self::DateTime => 'Date Time',
            self::Timestamp => 'Timestamp',
            self::Time => 'Time',
            self::Year => 'Year',
            self::Enum => 'Enumeration',
            self::Set => 'Set',
            self::Json, self::JsonB => 'JSON',
            self::Boolean => 'Boolean',
            self::UUID => 'UUID',
            self::Geometry => 'Geometry',
            self::Point => 'Point',
            self::LineString => 'Line String',
            self::Polygon => 'Polygon',
            self::MultiPoint => 'Multi Point',
            self::MultiLineString => 'Multi Line String',
            self::MultiPolygon => 'Multi Polygon',
            self::GeometryCollection => 'Geometry Collection',
        };
    }

    /**
     * Get the PHP type/cast for this column type.
     */
    public function phpType(): string
    {
        return match ($this) {
            self::TinyInt, self::SmallInt, self::MediumInt, self::Int, self::Integer, self::BigInt, self::Year => 'integer',
            self::Decimal, self::Numeric => 'decimal:2',
            self::Float => 'float',
            self::Double, self::Real => 'double',
            self::Bit, self::Boolean => 'boolean',
            self::Char, self::VarChar, self::TinyText, self::Text, self::MediumText, self::LongText => 'string',
            self::Binary, self::VarBinary, self::TinyBlob, self::Blob, self::MediumBlob, self::LongBlob => 'string',
            self::Date => 'date',
            self::DateTime, self::Timestamp => 'datetime',
            self::Time => 'string',
            self::Enum => 'string',
            self::Set, self::Json, self::JsonB => 'array',
            self::UUID => 'string',
            default => 'string', // Spatial types
        };
    }

    /**
     * Get the validation rule for this column type.
     */
    public function validationRule(?int $length = null, ?array $options = null): string
    {
        return match ($this) {
            self::TinyInt => 'integer|min:-128|max:127',
            self::SmallInt => 'integer|min:-32768|max:32767',
            self::MediumInt => 'integer|min:-8388608|max:8388607',
            self::Int, self::Integer => 'integer',
            self::BigInt => 'integer',
            self::Decimal, self::Numeric, self::Float, self::Double, self::Real => 'numeric',
            self::Bit, self::Boolean => 'boolean',
            self::Char => 'string'.($length ? "|size:{$length}" : ''),
            self::VarChar => 'string'.($length ? "|max:{$length}" : ''),
            self::TinyText => 'string|max:255',
            self::Text => 'string|max:65535',
            self::MediumText => 'string|max:16777215',
            self::LongText => 'string',
            self::Date => 'date',
            self::DateTime, self::Timestamp => 'date',
            self::Time => 'date_format:H:i:s',
            self::Year => 'integer|min:1901|max:2155',
            self::Enum => 'in:'.($options ? implode(',', $options) : ''),
            self::Set => 'array',
            self::Json, self::JsonB => 'json',
            self::UUID => 'uuid',
            default => 'string',
        };
    }

    /**
     * Get the Faker method for generating fake data.
     */
    public function fakerMethod(?int $length = null, ?array $options = null): string
    {
        return match ($this) {
            self::TinyInt => 'numberBetween(-128, 127)',
            self::SmallInt => 'numberBetween(-32768, 32767)',
            self::MediumInt => 'numberBetween(-8388608, 8388607)',
            self::Int, self::Integer => 'randomNumber()',
            self::BigInt => 'randomNumber()',
            self::Decimal, self::Numeric => 'randomFloat(2, 0, 999999)',
            self::Float, self::Double, self::Real => 'randomFloat()',
            self::Bit, self::Boolean => 'boolean',
            self::Char, self::VarChar => 'text('.($length ?: 50).')',
            self::TinyText => 'text(255)',
            self::Text, self::MediumText, self::LongText => 'paragraph',
            self::Date => 'date',
            self::DateTime, self::Timestamp => 'dateTime',
            self::Time => 'time',
            self::Year => 'year',
            self::Enum => 'randomElement('.($options ? json_encode($options) : '["option1", "option2"]').')',
            self::Set => 'randomElements(["a", "b", "c"], 2)',
            self::Json, self::JsonB => 'json',
            self::UUID => 'uuid',
            default => 'word',
        };
    }

    /**
     * Get the category of this column type.
     */
    public function category(): string
    {
        return match ($this) {
            self::TinyInt, self::SmallInt, self::MediumInt, self::Int, self::Integer, 
            self::BigInt, self::Decimal, self::Numeric, self::Float, self::Double, 
            self::Real, self::Bit => 'numeric',
            
            self::Char, self::VarChar, self::TinyText, self::Text, self::MediumText, 
            self::LongText, self::Binary, self::VarBinary, self::TinyBlob, self::Blob, 
            self::MediumBlob, self::LongBlob => 'string',
            
            self::Date, self::DateTime, self::Timestamp, self::Time, self::Year => 'datetime',
            
            self::Enum, self::Set => 'enumerable',
            
            self::Json, self::JsonB => 'json',
            
            self::Boolean => 'boolean',
            
            self::UUID => 'uuid',
            
            default => 'spatial',
        };
    }

    /**
     * Check if this column type supports length specification.
     */
    public function supportsLength(): bool
    {
        return match ($this) {
            self::Char, self::VarChar, self::Decimal, self::Numeric, 
            self::Float, self::Double => true,
            default => false,
        };
    }

    /**
     * Check if this column type supports options (like ENUM values).
     */
    public function supportsOptions(): bool
    {
        return match ($this) {
            self::Enum, self::Set => true,
            default => false,
        };
    }

    /**
     * Check if this column type is nullable by default.
     */
    public function isNullableByDefault(): bool
    {
        return match ($this) {
            self::Timestamp => false, // Often has default CURRENT_TIMESTAMP
            default => true,
        };
    }

    /**
     * Check if this column type supports indexing.
     */
    public function supportsIndexing(): bool
    {
        return match ($this) {
            self::TinyBlob, self::Blob, self::MediumBlob, self::LongBlob,
            self::TinyText, self::Text, self::MediumText, self::LongText => false,
            default => true,
        };
    }

    /**
     * Check if this column type supports full-text search.
     */
    public function supportsFullTextSearch(): bool
    {
        return match ($this) {
            self::Char, self::VarChar, self::Text, self::MediumText, self::LongText => true,
            default => false,
        };
    }

    /**
     * Get all column types by category.
     */
    public static function byCategory(string $category): array
    {
        return array_filter(self::cases(), fn($case) => $case->category() === $category);
    }

    /**
     * Get all numeric column types.
     */
    public static function numeric(): array
    {
        return self::byCategory('numeric');
    }

    /**
     * Get all string column types.
     */
    public static function string(): array
    {
        return self::byCategory('string');
    }

    /**
     * Get all datetime column types.
     */
    public static function datetime(): array
    {
        return self::byCategory('datetime');
    }

    /**
     * Get column types that support length specification.
     */
    public static function withLength(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsLength());
    }

    /**
     * Get column types that support options.
     */
    public static function withOptions(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsOptions());
    }

    /**
     * Get all available column types as an array.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all column types as label => value pairs.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn($carry, $case) => $carry + [$case->label() => $case->value],
            []
        );
    }
}