<?php

declare(strict_types=1);

namespace AutoGen\Common\Enums;

/**
 * Database Provider Enum
 * 
 * Represents supported database providers for AutoGen package.
 */
enum DatabaseProvider: string
{
    case MySQL = 'mysql';
    case PostgreSQL = 'postgresql';
    case SQLite = 'sqlite';

    /**
     * Get the display label for the database provider.
     */
    public function label(): string
    {
        return match ($this) {
            self::MySQL => 'MySQL',
            self::PostgreSQL => 'PostgreSQL',
            self::SQLite => 'SQLite',
        };
    }

    /**
     * Get the driver name for Laravel database configuration.
     */
    public function driver(): string
    {
        return match ($this) {
            self::MySQL => 'mysql',
            self::PostgreSQL => 'pgsql',
            self::SQLite => 'sqlite',
        };
    }

    /**
     * Get the default port for the database provider.
     */
    public function defaultPort(): int
    {
        return match ($this) {
            self::MySQL => 3306,
            self::PostgreSQL => 5432,
            self::SQLite => 0, // SQLite doesn't use ports
        };
    }

    /**
     * Check if the provider supports JSON columns.
     */
    public function supportsJson(): bool
    {
        return match ($this) {
            self::MySQL => true,
            self::PostgreSQL => true,
            self::SQLite => true,
        };
    }

    /**
     * Check if the provider supports full-text search.
     */
    public function supportsFullTextSearch(): bool
    {
        return match ($this) {
            self::MySQL => true,
            self::PostgreSQL => true,
            self::SQLite => true,
        };
    }

    /**
     * Get configuration file path for this provider.
     */
    public function configPath(): string
    {
        return "config/database-providers/{$this->value}.php";
    }

    /**
     * Get all available providers as an array.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all providers as label => value pairs.
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
     * Create from string with validation.
     */
    public static function fromString(string $provider): self
    {
        return self::from(strtolower($provider));
    }

    /**
     * Try to create from string, return null if invalid.
     */
    public static function tryFromString(?string $provider): ?self
    {
        if ($provider === null) {
            return null;
        }

        return self::tryFrom(strtolower($provider));
    }
}