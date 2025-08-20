<?php

declare(strict_types=1);

namespace AutoGen\Common\Enums;

/**
 * Controller Type Enum
 * 
 * Represents different controller types supported by AutoGen.
 */
enum ControllerType: string
{
    case Resource = 'resource';
    case API = 'api';
    case Web = 'web';
    case Invokable = 'invokable';

    /**
     * Get the display label for the controller type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Resource => 'Resource Controller',
            self::API => 'API Controller',
            self::Web => 'Web Controller',
            self::Invokable => 'Invokable Controller',
        };
    }

    /**
     * Get the description for the controller type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Resource => 'Full CRUD controller with standard resource methods',
            self::API => 'API controller with JSON responses and API middleware',
            self::Web => 'Web controller with view responses and web middleware',
            self::Invokable => 'Single-action controller with __invoke method',
        };
    }

    /**
     * Get the methods that should be generated for this controller type.
     */
    public function methods(): array
    {
        return match ($this) {
            self::Resource => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            self::API => ['index', 'store', 'show', 'update', 'destroy'],
            self::Web => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],
            self::Invokable => ['__invoke'],
        };
    }

    /**
     * Get the default middleware for this controller type.
     */
    public function defaultMiddleware(): array
    {
        return match ($this) {
            self::Resource => ['web', 'auth'],
            self::API => ['api', 'auth:sanctum'],
            self::Web => ['web', 'auth'],
            self::Invokable => ['web', 'auth'],
        };
    }

    /**
     * Get the response type for this controller.
     */
    public function responseType(): string
    {
        return match ($this) {
            self::Resource => 'mixed',
            self::API => 'json',
            self::Web => 'view',
            self::Invokable => 'mixed',
        };
    }

    /**
     * Check if this controller type supports views.
     */
    public function supportsViews(): bool
    {
        return match ($this) {
            self::Resource => true,
            self::API => false,
            self::Web => true,
            self::Invokable => true,
        };
    }

    /**
     * Check if this controller type supports API responses.
     */
    public function supportsApiResponses(): bool
    {
        return match ($this) {
            self::Resource => false,
            self::API => true,
            self::Web => false,
            self::Invokable => true,
        };
    }

    /**
     * Check if this controller type requires form requests.
     */
    public function requiresFormRequests(): bool
    {
        return match ($this) {
            self::Resource => true,
            self::API => true,
            self::Web => true,
            self::Invokable => false,
        };
    }

    /**
     * Check if this controller type requires API resources.
     */
    public function requiresApiResources(): bool
    {
        return match ($this) {
            self::Resource => false,
            self::API => true,
            self::Web => false,
            self::Invokable => false,
        };
    }

    /**
     * Check if this controller type supports authorization.
     */
    public function supportsAuthorization(): bool
    {
        return match ($this) {
            self::Resource => true,
            self::API => true,
            self::Web => true,
            self::Invokable => true,
        };
    }

    /**
     * Get the artisan command options for this controller type.
     */
    public function artisanOptions(): array
    {
        return match ($this) {
            self::Resource => ['--resource'],
            self::API => ['--api'],
            self::Web => [],
            self::Invokable => ['--invokable'],
        };
    }

    /**
     * Get the route definition type.
     */
    public function routeType(): string
    {
        return match ($this) {
            self::Resource => 'resource',
            self::API => 'apiResource',
            self::Web => 'resource',
            self::Invokable => 'single',
        };
    }

    /**
     * Get the stub file name for this controller type.
     */
    public function stubFile(): string
    {
        return match ($this) {
            self::Resource => 'controller.resource.stub',
            self::API => 'controller.api.stub',
            self::Web => 'controller.resource.stub',
            self::Invokable => 'controller.invokable.stub',
        };
    }

    /**
     * Get HTTP methods mapped to controller methods.
     */
    public function httpMethodMapping(): array
    {
        return match ($this) {
            self::Resource => [
                'GET' => ['index', 'create', 'show', 'edit'],
                'POST' => ['store'],
                'PUT' => ['update'],
                'PATCH' => ['update'],
                'DELETE' => ['destroy'],
            ],
            self::API => [
                'GET' => ['index', 'show'],
                'POST' => ['store'],
                'PUT' => ['update'],
                'PATCH' => ['update'],
                'DELETE' => ['destroy'],
            ],
            self::Web => [
                'GET' => ['index', 'create', 'show', 'edit'],
                'POST' => ['store'],
                'PUT' => ['update'],
                'PATCH' => ['update'],
                'DELETE' => ['destroy'],
            ],
            self::Invokable => [
                'GET' => ['__invoke'],
                'POST' => ['__invoke'],
                'PUT' => ['__invoke'],
                'PATCH' => ['__invoke'],
                'DELETE' => ['__invoke'],
            ],
        ];
    }

    /**
     * Get the return type for each method.
     */
    public function methodReturnTypes(): array
    {
        return match ($this) {
            self::Resource => [
                'index' => '\\Illuminate\\Contracts\\View\\View',
                'create' => '\\Illuminate\\Contracts\\View\\View',
                'store' => '\\Illuminate\\Http\\RedirectResponse',
                'show' => '\\Illuminate\\Contracts\\View\\View',
                'edit' => '\\Illuminate\\Contracts\\View\\View',
                'update' => '\\Illuminate\\Http\\RedirectResponse',
                'destroy' => '\\Illuminate\\Http\\RedirectResponse',
            ],
            self::API => [
                'index' => '\\Illuminate\\Http\\JsonResponse',
                'store' => '\\Illuminate\\Http\\JsonResponse',
                'show' => '\\Illuminate\\Http\\JsonResponse',
                'update' => '\\Illuminate\\Http\\JsonResponse',
                'destroy' => '\\Illuminate\\Http\\JsonResponse',
            ],
            self::Web => [
                'index' => '\\Illuminate\\Contracts\\View\\View',
                'create' => '\\Illuminate\\Contracts\\View\\View',
                'store' => '\\Illuminate\\Http\\RedirectResponse',
                'show' => '\\Illuminate\\Contracts\\View\\View',
                'edit' => '\\Illuminate\\Contracts\\View\\View',
                'update' => '\\Illuminate\\Http\\RedirectResponse',
                'destroy' => '\\Illuminate\\Http\\RedirectResponse',
            ],
            self::Invokable => [
                '__invoke' => 'mixed',
            ],
        ];
    }

    /**
     * Get all available controller types as an array.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all controller types as label => value pairs.
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
     * Get controller types that support views.
     */
    public static function withViews(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsViews());
    }

    /**
     * Get controller types that support API responses.
     */
    public static function withApiResponses(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsApiResponses());
    }

    /**
     * Get controller types that require form requests.
     */
    public static function withFormRequests(): array
    {
        return array_filter(self::cases(), fn($case) => $case->requiresFormRequests());
    }

    /**
     * Get controller types that require API resources.
     */
    public static function withApiResources(): array
    {
        return array_filter(self::cases(), fn($case) => $case->requiresApiResources());
    }
}