<?php

declare(strict_types=1);

namespace AutoGen\Common\Traits;

use AutoGen\Common\Enums\AIProvider;
use AutoGen\Common\Enums\CssFramework;
use AutoGen\Common\Enums\DatatableType;
use AutoGen\Common\Enums\DatabaseProvider;
use AutoGen\Common\Enums\GeneratorType;
use AutoGen\Common\Enums\RelationshipType;
use BackedEnum;

/**
 * Trait for backward compatibility with magic strings.
 * 
 * This trait provides helper methods to convert between magic strings
 * and enums, allowing for gradual migration.
 */
trait EnumBackwardCompatibility
{
    /**
     * Convert string to CssFramework enum with backward compatibility.
     */
    protected function toCssFramework(string|CssFramework $framework): CssFramework
    {
        if ($framework instanceof CssFramework) {
            return $framework;
        }

        return match ($framework) {
            'tailwind', 'tw' => CssFramework::Tailwind,
            'bootstrap', 'bs' => CssFramework::Bootstrap,
            'css', 'plain', 'plain-css' => CssFramework::PlainCSS,
            default => CssFramework::tryFrom($framework) ?? CssFramework::Tailwind,
        };
    }

    /**
     * Convert string to DatatableType enum with backward compatibility.
     */
    protected function toDatatableType(string|DatatableType $type): DatatableType
    {
        if ($type instanceof DatatableType) {
            return $type;
        }

        return match ($type) {
            'yajra', 'jquery', 'datatables' => DatatableType::Yajra,
            'livewire', 'lw' => DatatableType::Livewire,
            'inertia', 'vue', 'react' => DatatableType::Inertia,
            'api', 'rest' => DatatableType::API,
            default => DatatableType::tryFrom($type) ?? DatatableType::Yajra,
        };
    }

    /**
     * Convert string to DatabaseProvider enum with backward compatibility.
     */
    protected function toDatabaseProvider(string|DatabaseProvider $provider): DatabaseProvider
    {
        if ($provider instanceof DatabaseProvider) {
            return $provider;
        }

        return match ($provider) {
            'mysql', 'mariadb' => DatabaseProvider::MySQL,
            'postgresql', 'pgsql', 'postgres' => DatabaseProvider::PostgreSQL,
            'sqlite', 'sqlite3' => DatabaseProvider::SQLite,
            default => DatabaseProvider::tryFrom($provider) ?? DatabaseProvider::MySQL,
        };
    }

    /**
     * Convert string to AIProvider enum with backward compatibility.
     */
    protected function toAIProvider(string|AIProvider $provider): AIProvider
    {
        if ($provider instanceof AIProvider) {
            return $provider;
        }

        return match ($provider) {
            'openai', 'gpt', 'chatgpt' => AIProvider::OpenAI,
            'anthropic', 'claude' => AIProvider::Anthropic,
            'gemini', 'google', 'bard' => AIProvider::Gemini,
            'local', 'ollama', 'llama' => AIProvider::Local,
            default => AIProvider::tryFrom($provider) ?? AIProvider::OpenAI,
        };
    }

    /**
     * Convert string to GeneratorType enum with backward compatibility.
     */
    protected function toGeneratorType(string|GeneratorType $type): GeneratorType
    {
        if ($type instanceof GeneratorType) {
            return $type;
        }

        return match ($type) {
            'model', 'models', 'eloquent' => GeneratorType::Model,
            'controller', 'controllers' => GeneratorType::Controller,
            'view', 'views', 'blade' => GeneratorType::View,
            'migration', 'migrations' => GeneratorType::Migration,
            'factory', 'factories' => GeneratorType::Factory,
            'datatable', 'datatables' => GeneratorType::Datatable,
            'seeder', 'seeders' => GeneratorType::Seeder,
            'policy', 'policies' => GeneratorType::Policy,
            'request', 'requests' => GeneratorType::Request,
            'resource', 'resources' => GeneratorType::Resource,
            'test', 'tests' => GeneratorType::Test,
            default => GeneratorType::tryFrom($type) ?? GeneratorType::Model,
        };
    }

    /**
     * Convert string to RelationshipType enum with backward compatibility.
     */
    protected function toRelationshipType(string|RelationshipType $type): RelationshipType
    {
        if ($type instanceof RelationshipType) {
            return $type;
        }

        return match ($type) {
            'belongsTo', 'belongs_to', 'belongs-to' => RelationshipType::BelongsTo,
            'hasOne', 'has_one', 'has-one' => RelationshipType::HasOne,
            'hasMany', 'has_many', 'has-many' => RelationshipType::HasMany,
            'belongsToMany', 'belongs_to_many', 'belongs-to-many' => RelationshipType::BelongsToMany,
            'hasOneThrough', 'has_one_through', 'has-one-through' => RelationshipType::HasOneThrough,
            'hasManyThrough', 'has_many_through', 'has-many-through' => RelationshipType::HasManyThrough,
            'morphTo', 'morph_to', 'morph-to' => RelationshipType::MorphTo,
            'morphOne', 'morph_one', 'morph-one' => RelationshipType::MorphOne,
            'morphMany', 'morph_many', 'morph-many' => RelationshipType::MorphMany,
            'morphToMany', 'morph_to_many', 'morph-to-many' => RelationshipType::MorphToMany,
            'morphedByMany', 'morphed_by_many', 'morphed-by-many' => RelationshipType::MorphedByMany,
            default => RelationshipType::tryFrom($type) ?? RelationshipType::BelongsTo,
        };
    }

    /**
     * Convert enum to string for backward compatibility.
     */
    protected function enumToString(BackedEnum $enum): string
    {
        return $enum->value;
    }

    /**
     * Convert enum array to string array for backward compatibility.
     */
    protected function enumsToStrings(array $enums): array
    {
        return array_map(fn($enum) => $enum instanceof BackedEnum ? $enum->value : $enum, $enums);
    }

    /**
     * Check if a value is a valid enum or can be converted to one.
     */
    protected function isValidEnumValue(string $enumClass, mixed $value): bool
    {
        if (is_object($value) && $value instanceof $enumClass) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        // Check if it's a valid enum value
        if (method_exists($enumClass, 'tryFrom')) {
            return $enumClass::tryFrom($value) !== null;
        }

        return false;
    }

    /**
     * Safely convert a value to an enum, returning null if conversion fails.
     */
    protected function safeToEnum(string $enumClass, mixed $value): ?BackedEnum
    {
        if (is_object($value) && $value instanceof $enumClass) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        if (method_exists($enumClass, 'tryFrom')) {
            return $enumClass::tryFrom($value);
        }

        return null;
    }

    /**
     * Get all possible string values for an enum (including aliases).
     */
    protected function getAllValidValues(string $enumClass): array
    {
        $values = [];

        if (method_exists($enumClass, 'values')) {
            $values = array_merge($values, $enumClass::values());
        }

        // Add common aliases based on enum type
        switch ($enumClass) {
            case CssFramework::class:
                $values = array_merge($values, ['tw', 'bs', 'plain', 'plain-css']);
                break;
            case DatatableType::class:
                $values = array_merge($values, ['jquery', 'datatables', 'lw', 'vue', 'react', 'rest']);
                break;
            case DatabaseProvider::class:
                $values = array_merge($values, ['mariadb', 'pgsql', 'postgres', 'sqlite3']);
                break;
            case AIProvider::class:
                $values = array_merge($values, ['gpt', 'chatgpt', 'claude', 'google', 'bard', 'ollama', 'llama']);
                break;
            case RelationshipType::class:
                $values = array_merge($values, ['belongs_to', 'belongs-to', 'has_one', 'has-one', 'has_many', 'has-many']);
                break;
        }

        return array_unique($values);
    }
}