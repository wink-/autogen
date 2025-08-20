<?php

declare(strict_types=1);

namespace AutoGen\Common\Enums;

/**
 * Datatable Type Enum
 * 
 * Represents different datatable implementations supported by AutoGen.
 */
enum DatatableType: string
{
    case Yajra = 'yajra';
    case Livewire = 'livewire';
    case Inertia = 'inertia';
    case API = 'api';

    /**
     * Get the display label for the datatable type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Yajra => 'Yajra DataTables',
            self::Livewire => 'Livewire DataTables',
            self::Inertia => 'Inertia.js DataTables',
            self::API => 'API DataTables',
        };
    }

    /**
     * Get the description for the datatable type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Yajra => 'Server-side jQuery DataTables with Yajra package integration',
            self::Livewire => 'Full-stack PHP components with real-time data tables',
            self::Inertia => 'Modern SPA-style data tables using Inertia.js',
            self::API => 'RESTful API endpoints for custom frontend implementations',
        };
    }

    /**
     * Get required packages for this datatable type.
     */
    public function requiredPackages(): array
    {
        return match ($this) {
            self::Yajra => ['yajra/laravel-datatables-oracle'],
            self::Livewire => ['livewire/livewire'],
            self::Inertia => ['inertiajs/inertia-laravel'],
            self::API => [],
        };
    }

    /**
     * Get frontend technology used.
     */
    public function frontendTech(): string
    {
        return match ($this) {
            self::Yajra => 'jQuery + DataTables',
            self::Livewire => 'Alpine.js',
            self::Inertia => 'Vue.js / React / Svelte',
            self::API => 'Custom',
        };
    }

    /**
     * Get the generator class for this datatable type.
     */
    public function generatorClass(): string
    {
        return match ($this) {
            self::Yajra => 'AutoGen\\Packages\\Datatable\\YajraDatatableGenerator',
            self::Livewire => 'AutoGen\\Packages\\Datatable\\LivewireDatatableGenerator',
            self::Inertia => 'AutoGen\\Packages\\Datatable\\InertiaDatatableGenerator',
            self::API => 'AutoGen\\Packages\\Datatable\\ApiDatatableGenerator',
        };
    }

    /**
     * Check if this type supports server-side processing.
     */
    public function supportsServerSide(): bool
    {
        return true; // All types support server-side processing
    }

    /**
     * Check if this type supports real-time updates.
     */
    public function supportsRealTime(): bool
    {
        return match ($this) {
            self::Livewire => true,
            default => false,
        };
    }

    /**
     * Check if this type supports export functionality.
     */
    public function supportsExport(): bool
    {
        return match ($this) {
            self::Yajra => true,
            self::Livewire => true,
            default => false,
        };
    }

    /**
     * Check if this type requires JavaScript.
     */
    public function requiresJavaScript(): bool
    {
        return match ($this) {
            self::Livewire => false,
            default => true,
        };
    }

    /**
     * Get the complexity level of implementation.
     */
    public function complexityLevel(): string
    {
        return match ($this) {
            self::Yajra => 'medium',
            self::Livewire => 'low',
            self::Inertia => 'high',
            self::API => 'high',
        };
    }

    /**
     * Get all available datatable types as an array.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all datatable types as label => value pairs.
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
     * Get datatable types that support real-time updates.
     */
    public static function withRealTime(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsRealTime());
    }

    /**
     * Get datatable types that support export.
     */
    public static function withExport(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsExport());
    }

    /**
     * Get datatable types by complexity level.
     */
    public static function byComplexity(string $level): array
    {
        return array_filter(self::cases(), fn($case) => $case->complexityLevel() === $level);
    }

    /**
     * Get datatable types that don't require JavaScript.
     */
    public static function serverSideOnly(): array
    {
        return array_filter(self::cases(), fn($case) => !$case->requiresJavaScript());
    }
}