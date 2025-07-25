# AutoGen Factory Package

The AutoGen Factory package provides intelligent model factory generation for Laravel applications with smart fake data mapping, relationship handling, and state management.

## Features

- **Smart Fake Data Generation**: Automatically maps database fields to appropriate faker methods based on field names and types
- **Relationship Support**: Generates factory methods for all Eloquent relationship types
- **State Methods**: Creates convenient state methods for common scenarios (active/inactive, verified/unverified, etc.)
- **Multiple Templates**: Choose from minimal, default, or advanced factory templates
- **Locale Support**: Generate locale-aware fake data
- **File Upload Handling**: Special handling for image, document, and other file fields
- **Sequence Support**: Generate unique values using sequences
- **Custom Patterns**: Add your own field patterns and faker mappings

## Installation

The Factory package is included with AutoGen. Make sure the service provider is registered in your Laravel application.

## Usage

### Basic Factory Generation

Generate a factory for a model:

```bash
php artisan autogen:factory User
```

### Advanced Options

```bash
# Generate with states and relationships
php artisan autogen:factory Post --with-states --with-relationships

# Use a specific template and locale
php artisan autogen:factory Product --template=advanced --locale=de_DE

# Set default count and force overwrite
php artisan autogen:factory Order --count=5 --force
```

### Command Options

- `--with-states`: Generate state methods for common scenarios
- `--with-relationships`: Include factory methods for relationships
- `--count=N`: Set default count for factory generation (default: 10)
- `--force`: Overwrite existing factory files
- `--locale=LOCALE`: Set locale for fake data generation (default: en_US)
- `--template=TYPE`: Choose template complexity (minimal, default, advanced)

## Generated Factory Examples

### Basic Factory

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'bio' => $this->faker->paragraph(2),
            'is_active' => $this->faker->boolean(80),
            'age' => $this->faker->numberBetween(18, 80),
        ];
    }
}
```

### Factory with States

```php
/**
 * Indicate that the user is active.
 */
public function active(): static
{
    return $this->state(fn (array $attributes) => [
        'is_active' => true,
    ]);
}

/**
 * Indicate that the user is verified.
 */
public function verified(): static
{
    return $this->state(fn (array $attributes) => [
        'email_verified_at' => now(),
    ]);
}
```

### Factory with Relationships

```php
/**
 * Create with posts relationship.
 */
public function withPosts($count = 3, $attributes = []): static
{
    return $this->afterCreating(function ($model) use ($count, $attributes) {
        Post::factory()->count($count)->create(array_merge([
            $model->getForeignKey() => $model->id,
        ], $attributes));
    });
}

/**
 * Create with all relationships populated.
 */
public function withRelationships(): static
{
    return $this
        ->withPosts(3)
        ->withProfile()
        ->withRoles(2);
}
```

## Smart Field Mapping

The package intelligently maps database fields to appropriate faker methods:

### Field Name Patterns

- `email`, `email_address` → `$this->faker->unique()->safeEmail()`
- `first_name` → `$this->faker->firstName()`
- `last_name` → `$this->faker->lastName()`
- `phone`, `phone_number` → `$this->faker->phoneNumber()`
- `address` → `$this->faker->address()`
- `city` → `$this->faker->city()`
- `description`, `bio` → `$this->faker->paragraph()`
- `title` → `$this->faker->sentence(4, false)`
- `price`, `amount` → `$this->faker->randomFloat(2, 10, 1000)`
- `image`, `photo` → `$this->faker->imageUrl(640, 480)`
- `is_*`, `has_*`, `can_*` → `$this->faker->boolean()`
- `status` → `$this->faker->randomElement(['active', 'inactive', 'pending'])`

### Data Type Mapping

- `string` → `$this->faker->word()`
- `text` → `$this->faker->paragraph()`
- `integer` → `$this->faker->numberBetween(1, 1000)`
- `boolean` → `$this->faker->boolean()`
- `decimal` → `$this->faker->randomFloat(2, 1, 1000)`
- `date` → `$this->faker->date()`
- `datetime` → `$this->faker->dateTime()`
- `json` → `[]`

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=autogen-factory-config
```

The configuration file allows you to customize:

- Default template and locale
- Custom field patterns and data type mappings
- Relationship default counts
- File upload settings
- State generation options
- Nullable field probability

### Custom Field Patterns

Add custom patterns in the configuration:

```php
'custom_field_patterns' => [
    '/^sku$/i' => '$this->faker->unique()->regexify(\'[A-Z]{3}[0-9]{6}\')',
    '/^isbn$/i' => '$this->faker->isbn13()',
    '/^color$/i' => '$this->faker->hexColor()',
],
```

### Custom Data Types

Map custom database types:

```php
'custom_data_types' => [
    'uuid' => '$this->faker->uuid()',
    'money' => '$this->faker->randomFloat(2, 0.01, 999999.99)',
],
```

## Usage Examples

### Creating Models with Factories

```php
// Create a single user
$user = User::factory()->create();

// Create multiple users
$users = User::factory()->count(10)->create();

// Create with specific attributes
$user = User::factory()->create(['email' => 'test@example.com']);

// Create with states
$activeUser = User::factory()->active()->create();
$verifiedUser = User::factory()->verified()->create();

// Create with relationships
$userWithPosts = User::factory()->withPosts(5)->create();
$userWithEverything = User::factory()->withRelationships()->create();

// Chain multiple states
$user = User::factory()
    ->active()
    ->verified()
    ->withPosts(3)
    ->create();
```

### Testing Examples

```php
public function test_user_can_be_created()
{
    $user = User::factory()->create();
    
    $this->assertDatabaseHas('users', [
        'email' => $user->email,
    ]);
}

public function test_active_user_can_login()
{
    $user = User::factory()->active()->create();
    
    $this->assertTrue($user->is_active);
}

public function test_user_has_posts()
{
    $user = User::factory()->withPosts(3)->create();
    
    $this->assertCount(3, $user->posts);
}
```

## Templates

### Minimal Template
- Basic factory structure
- Field definitions only
- No states or relationships

### Default Template (recommended)
- Field definitions
- State methods for common scenarios
- Relationship methods
- Good balance of features

### Advanced Template
- All default features
- Configure callbacks
- Sequence methods
- Random state methods
- Testing-specific methods

## Supported Relationships

The package generates factory methods for all Eloquent relationship types:

- **BelongsTo**: Creates the related model and sets foreign key
- **HasOne**: Creates related model after parent creation
- **HasMany**: Creates multiple related models
- **BelongsToMany**: Creates and attaches related models
- **HasManyThrough**: Generates template (requires manual adjustment)
- **MorphTo**: Sets morphable type and ID
- **MorphOne**: Creates morphable relationship
- **MorphMany**: Creates multiple morphable relationships

## Troubleshooting

### Common Issues

1. **Model not found**: Ensure your model exists and follows Laravel naming conventions
2. **Database connection error**: Make sure your database is configured and accessible
3. **Factory already exists**: Use `--force` to overwrite existing factories
4. **Relationship methods not working**: Ensure related model factories exist

### Debug Mode

Run with verbose output to see detailed error information:

```bash
php artisan autogen:factory User --verbose
```

## Contributing

When contributing to the Factory package:

1. Add tests for new features
2. Update documentation
3. Follow PSR-12 coding standards
4. Add configuration options for new features
5. Ensure backward compatibility