# AutoGen Enum System Documentation

## Overview

The AutoGen package now uses PHP 8.3+ enums to replace magic strings throughout the codebase, providing better type safety, IDE support, and developer experience.

## Available Enums

### 1. DatabaseProvider

Represents supported database providers.

```php
use AutoGen\Common\Enums\DatabaseProvider;

// Basic usage
$provider = DatabaseProvider::MySQL;
echo $provider->label(); // "MySQL"
echo $provider->driver(); // "mysql"
echo $provider->defaultPort(); // 3306

// Available providers
DatabaseProvider::MySQL
DatabaseProvider::PostgreSQL  
DatabaseProvider::SQLite

// Utility methods
DatabaseProvider::values(); // ['mysql', 'postgresql', 'sqlite']
DatabaseProvider::options(); // ['MySQL' => 'mysql', ...]
```

### 2. CssFramework

Represents supported CSS frameworks for view generation.

```php
use AutoGen\Common\Enums\CssFramework;

$framework = CssFramework::Tailwind;
echo $framework->label(); // "Tailwind CSS"
echo $framework->generatorClass(); // "AutoGen\\Packages\\Views\\Generators\\TailwindGenerator"

// Check capabilities
$framework->supportsDarkMode(); // true
$framework->supportsResponsive(); // true

// Get CSS classes
$classes = $framework->cssClasses();
echo $classes['button']; // "bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"

// Available frameworks
CssFramework::Tailwind
CssFramework::Bootstrap
CssFramework::PlainCSS
```

### 3. RelationshipType

Represents Eloquent relationship types.

```php
use AutoGen\Common\Enums\RelationshipType;

$relationship = RelationshipType::BelongsTo;
echo $relationship->label(); // "Belongs To"
echo $relationship->cardinality(); // "one"

// Check characteristics
$relationship->isPolymorphic(); // false
$relationship->usesPivot(); // false
$relationship->isReverse(); // true

// Get method signature parameters
$params = $relationship->methodParameters();
// ['$related', '$foreignKey = null', '$ownerKey = null', '$relation = null']

// Available relationships
RelationshipType::BelongsTo
RelationshipType::HasOne
RelationshipType::HasMany
RelationshipType::BelongsToMany
RelationshipType::MorphTo
// ... and more

// Filter relationships
RelationshipType::polymorphic(); // Get all polymorphic relationships
RelationshipType::withPivot(); // Get relationships that use pivot tables
```

### 4. AIProvider

Represents supported AI providers for code generation.

```php
use AutoGen\Common\Enums\AIProvider;

$provider = AIProvider::OpenAI;
echo $provider->label(); // "OpenAI GPT"
echo $provider->defaultModel(); // "gpt-4"

// Check capabilities
$provider->supportsStreaming(); // true
$provider->supportsFunctionCalling(); // true
$provider->requiresInternet(); // true

// Get configuration
$config = $provider->defaultConfig();
$envVars = $provider->requiredEnvVars(); // ['OPENAI_API_KEY']

// Available providers
AIProvider::OpenAI
AIProvider::Anthropic
AIProvider::Gemini
AIProvider::Local
```

### 5. DatatableType

Represents different datatable implementations.

```php
use AutoGen\Common\Enums\DatatableType;

$type = DatatableType::Yajra;
echo $type->label(); // "Yajra DataTables"
echo $type->description(); // "Server-side jQuery DataTables with Yajra package integration"

// Check capabilities
$type->supportsRealTime(); // false
$type->supportsExport(); // true
$type->requiresJavaScript(); // true

// Get implementation details
echo $type->complexityLevel(); // "medium"
echo $type->frontendTech(); // "jQuery + DataTables"
$packages = $type->requiredPackages(); // ['yajra/laravel-datatables-oracle']

// Available types
DatatableType::Yajra
DatatableType::Livewire
DatatableType::Inertia
DatatableType::API
```

### 6. ControllerType

Represents different controller types.

```php
use AutoGen\Common\Enums\ControllerType;

$type = ControllerType::Resource;
echo $type->label(); // "Resource Controller"

// Get methods and configuration
$methods = $type->methods(); // ['index', 'create', 'store', ...]
$middleware = $type->defaultMiddleware(); // ['web', 'auth']
$returnTypes = $type->methodReturnTypes(); // ['index' => '\\Illuminate\\Contracts\\View\\View', ...]

// Check capabilities
$type->supportsViews(); // true
$type->requiresFormRequests(); // true

// Available types
ControllerType::Resource
ControllerType::API
ControllerType::Web
ControllerType::Invokable
```

### 7. ColumnType

Represents database column types with mappings.

```php
use AutoGen\Common\Enums\ColumnType;

$type = ColumnType::VarChar;
echo $type->label(); // "Variable Character"
echo $type->phpType(); // "string"

// Get validation and faker methods
echo $type->validationRule(255); // "string|max:255"
echo $type->fakerMethod(50); // "text(50)"

// Check capabilities
$type->supportsLength(); // true
$type->supportsIndexing(); // true
$type->supportsFullTextSearch(); // true

// Get by category
ColumnType::numeric(); // All numeric types
ColumnType::string(); // All string types
ColumnType::datetime(); // All datetime types
```

### 8. GeneratorType

Represents different generator types available in AutoGen.

```php
use AutoGen\Common\Enums\GeneratorType;

$type = GeneratorType::Model;
echo $type->label(); // "Eloquent Model"
echo $type->description(); // "Generate Eloquent model with relationships and attributes"

// Get paths and configuration
echo $type->outputDirectory(); // "app/Models"
echo $type->namespace(); // "App\\Models"
echo $type->fileExtension(); // ".php"

// Check capabilities
$type->supportsRelationships(); // true
$type->requiresDatabase(); // true

// Get by priority (for generation order)
GeneratorType::byPriority(); // Sorted by generation priority
```

## Backward Compatibility

The package includes a backward compatibility trait for gradual migration:

```php
use AutoGen\Common\Traits\EnumBackwardCompatibility;

class MyClass
{
    use EnumBackwardCompatibility;
    
    public function processFramework(string|CssFramework $framework)
    {
        $framework = $this->toCssFramework($framework);
        // Now guaranteed to be a CssFramework enum
        
        // Works with both:
        $this->processFramework('tailwind'); // String
        $this->processFramework(CssFramework::Tailwind); // Enum
    }
}
```

### Conversion Methods

```php
// Convert strings to enums with fallbacks
$framework = $this->toCssFramework('tw'); // CssFramework::Tailwind
$provider = $this->toDatabaseProvider('pgsql'); // DatabaseProvider::PostgreSQL
$relationship = $this->toRelationshipType('belongs_to'); // RelationshipType::BelongsTo

// Convert enums back to strings
$string = $this->enumToString($framework); // 'tailwind'

// Convert arrays of enums to strings
$strings = $this->enumsToStrings([$framework, $provider]); // ['tailwind', 'postgresql']

// Safe conversion (returns null on failure)
$enum = $this->safeToEnum(CssFramework::class, 'invalid'); // null

// Check valid values
$isValid = $this->isValidEnumValue(CssFramework::class, 'tailwind'); // true
```

## Usage in Configuration

### Before (Magic Strings)

```php
// config/autogen.php
return [
    'defaults' => [
        'views_framework' => 'tailwind',
        'controller_type' => 'resource',
    ],
    'database' => [
        'default_connection' => 'mysql',
    ],
    'ai' => [
        'default_provider' => 'openai',
    ],
];
```

### After (Enums)

```php
// config/autogen.php
use AutoGen\Common\Enums\CssFramework;
use AutoGen\Common\Enums\ControllerType;
use AutoGen\Common\Enums\DatabaseProvider;
use AutoGen\Common\Enums\AIProvider;

return [
    'defaults' => [
        'views_framework' => CssFramework::Tailwind->value,
        'controller_type' => ControllerType::Resource->value,
    ],
    'database' => [
        'default_connection' => DatabaseProvider::MySQL->value,
    ],
    'ai' => [
        'default_provider' => AIProvider::OpenAI->value,
    ],
];
```

## Usage in Code

### Generator Classes

```php
class ViewGenerator
{
    public function __construct(
        private CssFramework $framework, // Type-safe enum parameter
        // ... other parameters
    ) {}
    
    private function initializeGenerator(): void
    {
        $generatorClass = match ($this->framework) {
            CssFramework::Tailwind => TailwindGenerator::class,
            CssFramework::Bootstrap => BootstrapGenerator::class,
            CssFramework::PlainCSS => PlainCssGenerator::class,
        };
        
        // No more magic strings or invalid values possible
    }
}
```

### Relationship Analysis

```php
class RelationshipAnalyzer
{
    private function analyzeBelongsToRelationships(): array
    {
        // ...
        $relationships[] = [
            'type' => RelationshipType::BelongsTo, // Type-safe enum
            'method_name' => $methodName,
            'related_model' => $modelName,
            // ...
        ];
        
        return $relationships;
    }
}
```

### Configuration Management

```php
class ConfigurationManager
{
    public function getCssFramework(CssFramework|string|null $framework = null): CssFramework
    {
        if ($framework === null) {
            $framework = $this->get('packages.views.default_framework', 'tailwind');
        }
        
        return $this->toCssFramework($framework); // Always returns valid enum
    }
}
```

## Benefits

### 1. Type Safety
- Eliminates typos in magic strings
- Compile-time validation
- Better IDE support with autocompletion

### 2. Documentation
- Self-documenting code with enum methods
- Rich metadata available through enum methods
- Clear API contracts

### 3. Extensibility
- Easy to add new providers/frameworks
- Centralized configuration
- Consistent behavior across the package

### 4. Maintainability
- Refactoring is safer
- Changes are localized to enum definitions
- Backward compatibility is maintained

## Migration Guide

### Step 1: Update Imports
```php
// Add enum imports
use AutoGen\Common\Enums\CssFramework;
use AutoGen\Common\Enums\DatabaseProvider;
// ... other enums
```

### Step 2: Update Type Hints
```php
// Before
public function setFramework(string $framework): void

// After
public function setFramework(CssFramework|string $framework): void
```

### Step 3: Use Conversion Methods
```php
// Use backward compatibility trait
use EnumBackwardCompatibility;

// Convert in methods
$framework = $this->toCssFramework($framework);
```

### Step 4: Update Tests
```php
// Before
$this->assertEquals('tailwind', $config['framework']);

// After  
$this->assertEquals(CssFramework::Tailwind, $config['framework']);
```

## IDE Integration

Most modern IDEs will provide:
- Autocompletion for enum cases
- Type checking and validation
- Go-to-definition for enum methods
- Refactoring support

## Performance

Enums in PHP 8.3+ are highly optimized:
- Backed enums use the same memory as their backing values
- Method calls are optimized by the engine
- No performance penalty compared to string constants
- Better performance than class constants in many cases

## Best Practices

1. **Always use type unions** for backward compatibility: `CssFramework|string`
2. **Validate inputs** using `tryFrom()` for user input
3. **Use match expressions** instead of switch for better type inference
4. **Leverage enum methods** for rich behavior instead of external mapping arrays
5. **Group related functionality** in the enum itself rather than external classes
6. **Document enum cases** with clear labels and descriptions
7. **Use static methods** for filtering and grouping enum cases