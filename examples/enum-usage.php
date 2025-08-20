<?php

declare(strict_types=1);

/**
 * Examples of using AutoGen enums instead of magic strings
 * 
 * This file demonstrates how to use the new enum system
 * in the AutoGen package for type-safe configuration.
 */

require_once __DIR__ . '/../src/Common/Enums/DatabaseProvider.php';
require_once __DIR__ . '/../src/Common/Enums/CssFramework.php';
require_once __DIR__ . '/../src/Common/Enums/RelationshipType.php';
require_once __DIR__ . '/../src/Common/Enums/AIProvider.php';
require_once __DIR__ . '/../src/Common/Enums/DatatableType.php';

use AutoGen\Common\Enums\DatabaseProvider;
use AutoGen\Common\Enums\CssFramework;
use AutoGen\Common\Enums\RelationshipType;
use AutoGen\Common\Enums\AIProvider;
use AutoGen\Common\Enums\DatatableType;

echo "=== AutoGen Enum Usage Examples ===\n\n";

// Database Provider Examples
echo "1. Database Providers:\n";
echo "Available providers: " . implode(', ', DatabaseProvider::values()) . "\n";
echo "MySQL label: " . DatabaseProvider::MySQL->label() . "\n";
echo "MySQL driver: " . DatabaseProvider::MySQL->driver() . "\n";
echo "PostgreSQL default port: " . DatabaseProvider::PostgreSQL->defaultPort() . "\n";
echo "SQLite supports JSON: " . (DatabaseProvider::SQLite->supportsJson() ? 'Yes' : 'No') . "\n\n";

// CSS Framework Examples
echo "2. CSS Frameworks:\n";
echo "Available frameworks: " . implode(', ', CssFramework::values()) . "\n";
echo "Tailwind label: " . CssFramework::Tailwind->label() . "\n";
echo "Bootstrap supports dark mode: " . (CssFramework::Bootstrap->supportsDarkMode() ? 'Yes' : 'No') . "\n";
echo "Tailwind button class: " . CssFramework::Tailwind->cssClasses()['button'] . "\n";
echo "Frameworks with dark mode: " . implode(', ', array_map(fn($f) => $f->label(), CssFramework::withDarkMode())) . "\n\n";

// Relationship Type Examples
echo "3. Relationship Types:\n";
echo "Available relationships: " . implode(', ', RelationshipType::values()) . "\n";
echo "BelongsTo cardinality: " . RelationshipType::BelongsTo->cardinality() . "\n";
echo "MorphTo is polymorphic: " . (RelationshipType::MorphTo->isPolymorphic() ? 'Yes' : 'No') . "\n";
echo "BelongsToMany uses pivot: " . (RelationshipType::BelongsToMany->usesPivot() ? 'Yes' : 'No') . "\n";
echo "Polymorphic relationships: " . implode(', ', array_map(fn($r) => $r->value, RelationshipType::polymorphic())) . "\n\n";

// AI Provider Examples
echo "4. AI Providers:\n";
echo "Available providers: " . implode(', ', AIProvider::values()) . "\n";
echo "OpenAI label: " . AIProvider::OpenAI->label() . "\n";
echo "Claude default model: " . AIProvider::Anthropic->defaultModel() . "\n";
echo "Local provider requires internet: " . (AIProvider::Local->requiresInternet() ? 'Yes' : 'No') . "\n";
echo "Providers with streaming: " . implode(', ', array_map(fn($p) => $p->label(), AIProvider::withStreaming())) . "\n\n";

// Datatable Type Examples
echo "5. Datatable Types:\n";
echo "Available types: " . implode(', ', DatatableType::values()) . "\n";
echo "Yajra label: " . DatatableType::Yajra->label() . "\n";
echo "Livewire supports real-time: " . (DatatableType::Livewire->supportsRealTime() ? 'Yes' : 'No') . "\n";
echo "Yajra complexity: " . DatatableType::Yajra->complexityLevel() . "\n";
echo "Types with export: " . implode(', ', array_map(fn($t) => $t->label(), DatatableType::withExport())) . "\n\n";

// Practical Configuration Example
echo "6. Practical Configuration Example:\n";
$config = [
    'database' => [
        'provider' => DatabaseProvider::MySQL,
        'connection' => DatabaseProvider::MySQL->driver(),
        'port' => DatabaseProvider::MySQL->defaultPort(),
    ],
    'views' => [
        'framework' => CssFramework::Tailwind,
        'classes' => CssFramework::Tailwind->cssClasses(),
        'supports_dark_mode' => CssFramework::Tailwind->supportsDarkMode(),
    ],
    'ai' => [
        'provider' => AIProvider::OpenAI,
        'model' => AIProvider::OpenAI->defaultModel(),
        'streaming' => AIProvider::OpenAI->supportsStreaming(),
    ],
    'datatables' => [
        'type' => DatatableType::Yajra,
        'server_side' => DatatableType::Yajra->supportsServerSide(),
        'export' => DatatableType::Yajra->supportsExport(),
    ],
];

echo "Configuration with enums:\n";
print_r($config);

// Backward Compatibility Example
echo "\n7. Backward Compatibility:\n";
echo "String 'tailwind' can be converted to enum: " . CssFramework::tryFrom('tailwind')?->label() ?? 'Invalid' . "\n";
echo "String 'mysql' can be converted to enum: " . DatabaseProvider::tryFrom('mysql')?->label() ?? 'Invalid' . "\n";
echo "Invalid string 'invalid' returns null: " . (CssFramework::tryFrom('invalid') === null ? 'Yes' : 'No') . "\n";

echo "\n=== End Examples ===\n";