<?php

declare(strict_types=1);

namespace AutoGen\Common\Enums;

/**
 * CSS Framework Enum
 * 
 * Represents supported CSS frameworks for view generation.
 */
enum CssFramework: string
{
    case Tailwind = 'tailwind';
    case Bootstrap = 'bootstrap';
    case PlainCSS = 'css';

    /**
     * Get the display label for the framework.
     */
    public function label(): string
    {
        return match ($this) {
            self::Tailwind => 'Tailwind CSS',
            self::Bootstrap => 'Bootstrap',
            self::PlainCSS => 'Plain CSS',
        };
    }

    /**
     * Get the description for the framework.
     */
    public function description(): string
    {
        return match ($this) {
            self::Tailwind => 'Utility-first CSS framework for rapidly building modern websites',
            self::Bootstrap => 'Popular CSS framework for responsive, mobile-first projects',
            self::PlainCSS => 'Custom CSS without any framework dependencies',
        };
    }

    /**
     * Get the CDN link for the framework.
     */
    public function cdnLink(): ?string
    {
        return match ($this) {
            self::Tailwind => 'https://cdn.tailwindcss.com',
            self::Bootstrap => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            self::PlainCSS => null,
        };
    }

    /**
     * Get the generator class for this framework.
     */
    public function generatorClass(): string
    {
        return match ($this) {
            self::Tailwind => 'AutoGen\\Packages\\Views\\Generators\\TailwindGenerator',
            self::Bootstrap => 'AutoGen\\Packages\\Views\\Generators\\BootstrapGenerator',
            self::PlainCSS => 'AutoGen\\Packages\\Views\\Generators\\PlainCssGenerator',
        };
    }

    /**
     * Get common CSS classes for different components.
     */
    public function cssClasses(): array
    {
        return match ($this) {
            self::Tailwind => [
                'container' => 'container mx-auto px-4',
                'card' => 'bg-white shadow-md rounded-lg',
                'button' => 'bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded',
                'button_secondary' => 'bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded',
                'input' => 'shadow appearance-none border rounded w-full py-2 px-3 text-gray-700',
                'table' => 'min-w-full bg-white border border-gray-300',
                'table_header' => 'bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
                'alert_success' => 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded',
                'alert_error' => 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded',
            ],
            self::Bootstrap => [
                'container' => 'container',
                'card' => 'card',
                'button' => 'btn btn-primary',
                'button_secondary' => 'btn btn-secondary',
                'input' => 'form-control',
                'table' => 'table table-striped',
                'table_header' => 'thead-light',
                'alert_success' => 'alert alert-success',
                'alert_error' => 'alert alert-danger',
            ],
            self::PlainCSS => [
                'container' => 'container',
                'card' => 'card',
                'button' => 'btn btn-primary',
                'button_secondary' => 'btn btn-secondary',
                'input' => 'form-input',
                'table' => 'data-table',
                'table_header' => 'table-header',
                'alert_success' => 'alert success',
                'alert_error' => 'alert error',
            ],
        };
    }

    /**
     * Check if framework supports dark mode.
     */
    public function supportsDarkMode(): bool
    {
        return match ($this) {
            self::Tailwind => true,
            self::Bootstrap => true,
            self::PlainCSS => false,
        };
    }

    /**
     * Check if framework supports responsive design out of the box.
     */
    public function supportsResponsive(): bool
    {
        return match ($this) {
            self::Tailwind => true,
            self::Bootstrap => true,
            self::PlainCSS => false,
        };
    }

    /**
     * Get stub directory name for this framework.
     */
    public function stubDirectory(): string
    {
        return match ($this) {
            self::Tailwind => 'tailwind',
            self::Bootstrap => 'bootstrap',
            self::PlainCSS => 'css',
        };
    }

    /**
     * Get form input wrapper classes.
     */
    public function inputWrapperClasses(): string
    {
        return match ($this) {
            self::Tailwind => 'mb-4',
            self::Bootstrap => 'mb-3',
            self::PlainCSS => 'form-group',
        };
    }

    /**
     * Get label classes.
     */
    public function labelClasses(): string
    {
        return match ($this) {
            self::Tailwind => 'block text-gray-700 text-sm font-bold mb-2',
            self::Bootstrap => 'form-label',
            self::PlainCSS => 'form-label',
        };
    }

    /**
     * Get all available frameworks as an array.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all frameworks as label => value pairs.
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
     * Get frameworks that support dark mode.
     */
    public static function withDarkMode(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsDarkMode());
    }

    /**
     * Get frameworks that support responsive design.
     */
    public static function withResponsive(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsResponsive());
    }
}