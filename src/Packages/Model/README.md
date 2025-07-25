# AutoGen Model Package

The AutoGen Model package provides intelligent Eloquent model generation from existing database tables. It analyzes your database schema and creates feature-rich models with relationships, validation rules, scopes, and more.

## Features

- **Database Introspection**: Supports MySQL, PostgreSQL, SQLite, and SQL Server
- **Intelligent Relationships**: Automatically detects and generates belongsTo, hasMany, belongsToMany, and polymorphic relationships
- **Type Casting**: Smart column type mapping to Laravel casts
- **Validation Rules**: Auto-generates validation rules based on database schema
- **Query Scopes**: Creates common query scopes based on column patterns
- **PHP 8.3+ Features**: Uses readonly properties, typed constants, and modern PHP features
- **Comprehensive Documentation**: Generates PHPDoc comments with property types and index information

## Installation

```bash
# Register the service provider in config/app.php
'providers' => [
    // ...
    AutoGen\Packages\Model\ModelPackageServiceProvider::class,
],
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=autogen-config
php artisan vendor:publish --tag=autogen-stubs
```

## Basic Usage

### Generate Models for All Tables

```bash
php artisan autogen:model --connection=mysql
```

### Generate Models for Specific Tables

```bash
php artisan autogen:model --connection=mysql --tables=users,posts,comments
```

### Generate with Relationships

```bash
php artisan autogen:model --connection=mysql --with-relationships
```

### Advanced Options

```bash
php artisan autogen:model \
    --connection=legacy \
    --dir=Admin/Legacy \
    --namespace="App\Models\Admin\Legacy" \
    --with-relationships \
    --with-validation \
    --with-scopes \
    --force
```

## Command Options

| Option | Description | Example |
|--------|-------------|---------|
| `--connection` | Database connection name (required) | `--connection=mysql` |
| `--dir` | Subdirectory within app/Models/ | `--dir=Admin/Legacy` |
| `--namespace` | Custom namespace | `--namespace="App\Models\Legacy"` |
| `--tables` | Comma-separated table list | `--tables=users,posts` |
| `--all-tables` | Generate for all tables | `--all-tables` |
| `--force` | Overwrite existing files | `--force` |
| `--with-relationships` | Analyze and include relationships | `--with-relationships` |
| `--with-validation` | Include validation rules | `--with-validation` |
| `--with-scopes` | Generate query scopes | `--with-scopes` |
| `--with-traits` | Auto-detect and include traits | `--with-traits` |

## Generated Model Features

### Basic Model Structure

```php
<?php

declare(strict_types=1);

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property-read int $id
 * @property string $name
 * @property string $email
 * @property ?string $phone
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * Indexes:
 * - users_email_unique (email)
 * - users_status_created_at_index (status, created_at)
 */
class User extends Model
{
    use HasFactory, SoftDeletes;

    protected const string CONNECTION = 'legacy';
    
    protected $connection = self::CONNECTION;
    protected $table = 'users';
    protected readonly string $primaryKey = 'id';
    public readonly bool $incrementing = true;
    protected readonly string $keyType = 'int';
    public $timestamps = true;
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
```

### Relationship Detection

The package automatically detects and generates relationships:

#### BelongsTo Relationships
```php
public function category(): BelongsTo
{
    return $this->belongsTo(Category::class, 'category_id', 'id');
}
```

#### HasMany Relationships
```php
public function posts(): HasMany
{
    return $this->hasMany(Post::class, 'user_id', 'id');
}
```

#### BelongsToMany Relationships
```php
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
}
```

#### Polymorphic Relationships
```php
public function commentable(): MorphTo
{
    return $this->morphTo();
}
```

### Query Scopes

Based on column names, the generator creates useful scopes:

```php
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function scopeStatus($query, string $status)
{
    return $query->where('status', $status);
}

public function scopePublished($query)
{
    return $query->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
}
```

### Accessors and Mutators

Common accessors and mutators are generated:

```php
protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => $this->first_name . ' ' . $this->last_name,
    );
}

protected function password(): Attribute
{
    return Attribute::make(
        set: fn (string $value) => bcrypt($value),
    );
}
```

## Configuration

The package is highly configurable via `config/autogen.php`:

### Database Support

```php
'database_drivers' => [
    'mysql' => true,
    'pgsql' => true,
    'sqlite' => true,
    'sqlsrv' => true,
],
```

### Relationship Detection

```php
'relationships' => [
    'detect_belongs_to' => true,
    'detect_has_many' => true,
    'detect_many_to_many' => true,
    'detect_polymorphic' => true,
    'foreign_key_suffix' => '_id',
],
```

### Type Mappings

```php
'column_type_mappings' => [
    'tinyint(1)' => 'boolean',
    'json' => 'array',
    'decimal' => 'decimal:2',
    // ...
],
```

### Hidden Fields

```php
'hidden_fields' => [
    'password',
    'remember_token',
    'api_token',
    // ...
],
```

## Custom Stubs

You can customize the generated model templates by publishing the stubs:

```bash
php artisan vendor:publish --tag=autogen-stubs
```

This creates stub files in `resources/stubs/autogen/model/`:

- `model.stub` - Basic model template
- `model-with-relationships.stub` - Model with relationships
- `model-minimal.stub` - Minimal model template
- `model-enhanced.stub` - Enhanced model with all features

## Database Support

### MySQL/MariaDB
- Full support for all column types
- Index detection and documentation
- Foreign key constraint analysis
- Enum and Set type handling

### PostgreSQL
- Complete column type mapping
- Array and JSON column support
- Constraint and index analysis
- UUID type support

### SQLite
- Comprehensive schema introspection
- PRAGMA-based metadata extraction
- Foreign key relationship detection

### SQL Server
- Full T-SQL schema support
- Identity column detection
- Constraint analysis

## Advanced Features

### Composite Keys

The generator handles composite primary keys:

```php
protected readonly array $primaryKey = ['user_id', 'role_id'];
public readonly bool $incrementing = false;
```

### Polymorphic Relationships

Automatically detects polymorphic patterns:

- `*able_type` and `*able_id` columns
- `*_type` and `*_id` patterns

### Pivot Table Detection

Intelligently identifies pivot tables based on:
- Table naming conventions
- Column structure analysis
- Foreign key relationships

## Best Practices

1. **Use Connections**: Always specify the database connection
2. **Organize Models**: Use the `--dir` option to organize models by domain
3. **Version Control**: Commit generated models to track changes
4. **Customization**: Use custom stubs for organization-specific requirements
5. **Relationships**: Always generate with `--with-relationships` for complete models

## Troubleshooting

### Common Issues

1. **Permission Errors**: Ensure the application has read access to the database schema
2. **Missing Relationships**: Check foreign key constraints are properly defined
3. **Type Mapping**: Customize `column_type_mappings` for specific database types
4. **Large Schemas**: Use `--tables` to generate models incrementally

### Debug Information

Enable verbose output for troubleshooting:

```bash
php artisan autogen:model --connection=mysql --verbose
```

## Performance Considerations

- **Large Databases**: Generate models incrementally using `--tables`
- **Relationship Analysis**: Can be disabled if not needed for better performance
- **Caching**: Model metadata is cached for better performance

## Contributing

When contributing to the Model package:

1. Add tests for new database drivers
2. Update type mappings for new column types
3. Enhance relationship detection algorithms
4. Improve stub templates

## License

This package is part of the AutoGen suite and follows the same licensing terms.