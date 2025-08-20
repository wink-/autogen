<?php

declare(strict_types=1);

namespace AutoGen\Common\Enums;

/**
 * Relationship Type Enum
 * 
 * Represents Eloquent relationship types supported by AutoGen.
 */
enum RelationshipType: string
{
    case BelongsTo = 'belongsTo';
    case HasOne = 'hasOne';
    case HasMany = 'hasMany';
    case BelongsToMany = 'belongsToMany';
    case HasOneThrough = 'hasOneThrough';
    case HasManyThrough = 'hasManyThrough';
    case MorphTo = 'morphTo';
    case MorphOne = 'morphOne';
    case MorphMany = 'morphMany';
    case MorphToMany = 'morphToMany';
    case MorphedByMany = 'morphedByMany';

    /**
     * Get the display label for the relationship type.
     */
    public function label(): string
    {
        return match ($this) {
            self::BelongsTo => 'Belongs To',
            self::HasOne => 'Has One',
            self::HasMany => 'Has Many',
            self::BelongsToMany => 'Belongs To Many',
            self::HasOneThrough => 'Has One Through',
            self::HasManyThrough => 'Has Many Through',
            self::MorphTo => 'Morph To',
            self::MorphOne => 'Morph One',
            self::MorphMany => 'Morph Many',
            self::MorphToMany => 'Morph To Many',
            self::MorphedByMany => 'Morphed By Many',
        };
    }

    /**
     * Get the description for the relationship type.
     */
    public function description(): string
    {
        return match ($this) {
            self::BelongsTo => 'One-to-one relationship where this model belongs to another',
            self::HasOne => 'One-to-one relationship where this model has one related model',
            self::HasMany => 'One-to-many relationship where this model has many related models',
            self::BelongsToMany => 'Many-to-many relationship using a pivot table',
            self::HasOneThrough => 'One-to-one relationship through an intermediate model',
            self::HasManyThrough => 'One-to-many relationship through an intermediate model',
            self::MorphTo => 'Polymorphic relationship where this model can belong to multiple types',
            self::MorphOne => 'Polymorphic one-to-one relationship',
            self::MorphMany => 'Polymorphic one-to-many relationship',
            self::MorphToMany => 'Polymorphic many-to-many relationship',
            self::MorphedByMany => 'Reverse polymorphic many-to-many relationship',
        };
    }

    /**
     * Get the cardinality of the relationship.
     */
    public function cardinality(): string
    {
        return match ($this) {
            self::BelongsTo, self::HasOne, self::HasOneThrough, self::MorphTo, self::MorphOne => 'one',
            self::HasMany, self::BelongsToMany, self::HasManyThrough, self::MorphMany, self::MorphToMany, self::MorphedByMany => 'many',
        };
    }

    /**
     * Check if this is a polymorphic relationship.
     */
    public function isPolymorphic(): bool
    {
        return match ($this) {
            self::MorphTo, self::MorphOne, self::MorphMany, self::MorphToMany, self::MorphedByMany => true,
            default => false,
        };
    }

    /**
     * Check if this relationship uses a pivot table.
     */
    public function usesPivot(): bool
    {
        return match ($this) {
            self::BelongsToMany, self::MorphToMany, self::MorphedByMany => true,
            default => false,
        };
    }

    /**
     * Check if this is a reverse relationship.
     */
    public function isReverse(): bool
    {
        return match ($this) {
            self::BelongsTo, self::MorphTo, self::MorphedByMany => true,
            default => false,
        };
    }

    /**
     * Check if this relationship supports eager loading.
     */
    public function supportsEagerLoading(): bool
    {
        return true; // All Eloquent relationships support eager loading
    }

    /**
     * Check if this relationship supports lazy eager loading.
     */
    public function supportsLazyEagerLoading(): bool
    {
        return true; // All Eloquent relationships support lazy eager loading
    }

    /**
     * Get the method signature parameters for this relationship type.
     */
    public function methodParameters(): array
    {
        return match ($this) {
            self::BelongsTo => ['$related', '$foreignKey = null', '$ownerKey = null', '$relation = null'],
            self::HasOne => ['$related', '$foreignKey = null', '$localKey = null'],
            self::HasMany => ['$related', '$foreignKey = null', '$localKey = null'],
            self::BelongsToMany => ['$related', '$table = null', '$foreignPivotKey = null', '$relatedPivotKey = null', '$parentKey = null', '$relatedKey = null', '$relation = null'],
            self::HasOneThrough => ['$related', '$through', '$firstKey = null', '$secondKey = null', '$localKey = null', '$secondLocalKey = null'],
            self::HasManyThrough => ['$related', '$through', '$firstKey = null', '$secondKey = null', '$localKey = null', '$secondLocalKey = null'],
            self::MorphTo => ['$name = null', '$type = null', '$id = null', '$ownerKey = null'],
            self::MorphOne => ['$related', '$name', '$type = null', '$id = null', '$localKey = null'],
            self::MorphMany => ['$related', '$name', '$type = null', '$id = null', '$localKey = null'],
            self::MorphToMany => ['$related', '$name', '$table = null', '$foreignPivotKey = null', '$relatedPivotKey = null', '$parentKey = null', '$relatedKey = null', '$inverse = false'],
            self::MorphedByMany => ['$related', '$name', '$table = null', '$foreignPivotKey = null', '$relatedPivotKey = null', '$parentKey = null', '$relatedKey = null'],
        };
    }

    /**
     * Get the inverse relationship type.
     */
    public function inverse(): ?self
    {
        return match ($this) {
            self::BelongsTo => self::HasOne,
            self::HasOne => self::BelongsTo,
            self::HasMany => self::BelongsTo,
            self::BelongsToMany => self::BelongsToMany,
            self::MorphOne => self::MorphTo,
            self::MorphMany => self::MorphTo,
            self::MorphToMany => self::MorphedByMany,
            self::MorphedByMany => self::MorphToMany,
            default => null,
        };
    }

    /**
     * Get required columns for this relationship type.
     */
    public function requiredColumns(): array
    {
        return match ($this) {
            self::BelongsTo => ['foreign_key'],
            self::HasOne, self::HasMany => [],
            self::BelongsToMany => [],
            self::MorphTo => ['type_column', 'id_column'],
            self::MorphOne, self::MorphMany => [],
            self::MorphToMany, self::MorphedByMany => [],
            default => [],
        };
    }

    /**
     * Get all available relationship types as an array.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all relationship types as label => value pairs.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn($carry, $case) => $carry + [$case->label() => $case->value],
            []
        );
    }

    /**
     * Get polymorphic relationship types.
     */
    public static function polymorphic(): array
    {
        return array_filter(self::cases(), fn($case) => $case->isPolymorphic());
    }

    /**
     * Get relationship types that use pivot tables.
     */
    public static function withPivot(): array
    {
        return array_filter(self::cases(), fn($case) => $case->usesPivot());
    }

    /**
     * Get simple (non-polymorphic, non-through) relationship types.
     */
    public static function simple(): array
    {
        return array_filter(
            self::cases(),
            fn($case) => !$case->isPolymorphic() && !in_array($case, [self::HasOneThrough, self::HasManyThrough])
        );
    }
}