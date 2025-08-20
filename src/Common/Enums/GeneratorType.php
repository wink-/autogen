<?php

declare(strict_types=1);

namespace AutoGen\Common\Enums;

/**
 * Generator Type Enum
 * 
 * Represents different types of generators available in AutoGen.
 */
enum GeneratorType: string
{
    case Model = 'model';
    case Controller = 'controller';
    case View = 'view';
    case Migration = 'migration';
    case Factory = 'factory';
    case Datatable = 'datatable';
    case Seeder = 'seeder';
    case Policy = 'policy';
    case Request = 'request';
    case Resource = 'resource';
    case Test = 'test';

    /**
     * Get the display label for the generator type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Model => 'Eloquent Model',
            self::Controller => 'Controller',
            self::View => 'Blade Views',
            self::Migration => 'Database Migration',
            self::Factory => 'Model Factory',
            self::Datatable => 'DataTable',
            self::Seeder => 'Database Seeder',
            self::Policy => 'Authorization Policy',
            self::Request => 'Form Request',
            self::Resource => 'API Resource',
            self::Test => 'PHPUnit/Pest Tests',
        };
    }

    /**
     * Get the description for the generator type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Model => 'Generate Eloquent model with relationships and attributes',
            self::Controller => 'Generate controller with CRUD operations',
            self::View => 'Generate Blade view templates with forms and tables',
            self::Migration => 'Generate database migration files',
            self::Factory => 'Generate model factory for testing and seeding',
            self::Datatable => 'Generate DataTable for data display and management',
            self::Seeder => 'Generate database seeder classes',
            self::Policy => 'Generate authorization policy classes',
            self::Request => 'Generate form request classes with validation',
            self::Resource => 'Generate API resource classes for JSON responses',
            self::Test => 'Generate unit and feature test classes',
        };
    }

    /**
     * Get the output directory for this generator type.
     */
    public function outputDirectory(): string
    {
        return match ($this) {
            self::Model => 'app/Models',
            self::Controller => 'app/Http/Controllers',
            self::View => 'resources/views',
            self::Migration => 'database/migrations',
            self::Factory => 'database/factories',
            self::Datatable => 'app/DataTables',
            self::Seeder => 'database/seeders',
            self::Policy => 'app/Policies',
            self::Request => 'app/Http/Requests',
            self::Resource => 'app/Http/Resources',
            self::Test => 'tests',
        };
    }

    /**
     * Get the namespace for this generator type.
     */
    public function namespace(): string
    {
        return match ($this) {
            self::Model => 'App\\Models',
            self::Controller => 'App\\Http\\Controllers',
            self::View => '', // Views don't have namespaces
            self::Migration => '', // Migrations don't have namespaces
            self::Factory => 'Database\\Factories',
            self::Datatable => 'App\\DataTables',
            self::Seeder => 'Database\\Seeders',
            self::Policy => 'App\\Policies',
            self::Request => 'App\\Http\\Requests',
            self::Resource => 'App\\Http\\Resources',
            self::Test => 'Tests',
        };
    }

    /**
     * Get file extension for this generator type.
     */
    public function fileExtension(): string
    {
        return match ($this) {
            self::View => '.blade.php',
            default => '.php',
        };
    }

    /**
     * Check if this generator type supports relationships.
     */
    public function supportsRelationships(): bool
    {
        return match ($this) {
            self::Model, self::Factory, self::Datatable, self::Test => true,
            default => false,
        };
    }

    /**
     * Check if this generator type requires database introspection.
     */
    public function requiresDatabase(): bool
    {
        return match ($this) {
            self::Model, self::Migration, self::Factory, self::Datatable => true,
            default => false,
        };
    }

    /**
     * Get priority order for generation (lower numbers generated first).
     */
    public function priority(): int
    {
        return match ($this) {
            self::Migration => 1,
            self::Model => 2,
            self::Policy => 3,
            self::Request => 4,
            self::Controller => 5,
            self::Resource => 6,
            self::View => 7,
            self::Datatable => 8,
            self::Factory => 9,
            self::Seeder => 10,
            self::Test => 11,
        };
    }

    /**
     * Get all available generator types as an array.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all generator types as label => value pairs.
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
     * Get generator types sorted by priority.
     */
    public static function byPriority(): array
    {
        $cases = self::cases();
        usort($cases, fn($a, $b) => $a->priority() <=> $b->priority());
        
        return $cases;
    }

    /**
     * Get generator types that support relationships.
     */
    public static function withRelationships(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsRelationships());
    }

    /**
     * Get generator types that require database.
     */
    public static function requiresDatabase(): array
    {
        return array_filter(self::cases(), fn($case) => $case->requiresDatabase());
    }
}