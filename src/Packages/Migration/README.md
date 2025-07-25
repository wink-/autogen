# AutoGen Migration Package

The AutoGen Migration package reverse engineers existing database schemas to create Laravel migration files. This is particularly useful when working with legacy databases or when you need to version control your existing database structure.

## Features

- **Multi-Database Support**: Works with MySQL, PostgreSQL, SQLite, and SQL Server
- **Complete Schema Analysis**: Captures columns, indexes, foreign keys, and constraints
- **Dependency Ordering**: Automatically orders migrations based on foreign key dependencies
- **Customizable Output**: Flexible configuration options for different use cases
- **Migration Validation**: Validates generated migrations before writing to disk
- **Rollback Support**: Generates proper rollback methods for all migrations

## Installation

The Migration package is part of the AutoGen suite and is automatically available when AutoGen is installed.

## Basic Usage

### Generate Migration for Single Table

```bash
php artisan autogen:migration --connection=mysql --table=users
```

### Generate Migrations for All Tables

```bash
php artisan autogen:migration --connection=mysql --all-tables
```

### Interactive Table Selection

```bash
php artisan autogen:migration --connection=mysql
```

This will prompt you to select which tables to generate migrations for.

## Command Options

### Required Options

- `--connection=<name>`: The database connection to use (required)

### Table Selection Options

- `--table=<table>`: Generate migration for a specific table
- `--all-tables`: Generate migrations for all tables

### Output Options

- `--output-path=<path>`: Custom output path for migrations (default: database/migrations)
- `--force`: Overwrite existing migration files
- `--timestamp-prefix`: Add timestamp prefix to migrations (default: true)

### Schema Options

- `--preserve-order`: Preserve table creation order based on foreign keys (default: true)
- `--with-foreign-keys`: Include foreign key constraints (default: true)
- `--with-indexes`: Include all indexes (default: true)
- `--skip-views`: Skip database views (default: true)
- `--rollback-support`: Generate rollback methods (default: true)

## Examples

### Basic Migration Generation

Generate migrations for all tables in a MySQL database:

```bash
php artisan autogen:migration --connection=legacy_db --all-tables
```

### Custom Output Directory

Generate migrations to a custom directory:

```bash
php artisan autogen:migration --connection=mysql --all-tables --output-path=/custom/migrations
```

### Without Foreign Keys

Generate table structures without foreign key constraints:

```bash
php artisan autogen:migration --connection=mysql --all-tables --with-foreign-keys=false
```

### Force Overwrite

Overwrite existing migration files:

```bash
php artisan autogen:migration --connection=mysql --table=users --force
```

## Configuration

The migration package can be configured by publishing the configuration file:

```bash
php artisan vendor:publish --tag=autogen-migration-config
```

This creates `config/autogen/migration.php` with the following key options:

### Table Processing

```php
'tables' => [
    'ignore' => [
        'migrations',
        'failed_jobs',
        // ... other system tables
    ],
    'preserve_order' => true,
    'include_views' => false,
],
```

### Column Processing

```php
'columns' => [
    'include_comments' => true,
    'include_defaults' => true,
    'include_unsigned' => true,
    'type_mappings' => [
        // Custom column type mappings
    ],
],
```

### Foreign Key Handling

```php
'foreign_keys' => [
    'include_foreign_keys' => true,
    'separate_migrations' => false,
    'handle_circular_dependencies' => true,
],
```

### Database-Specific Options

```php
'database_specific' => [
    'mysql' => [
        'include_engine' => true,
        'include_charset' => true,
        'include_collation' => true,
    ],
    'postgresql' => [
        'include_sequences' => true,
        'schema_name' => 'public',
    ],
],
```

## Generated Migration Structure

The package generates clean, readable Laravel migrations:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

## Column Type Mapping

The package automatically maps database column types to Laravel migration methods:

| Database Type | Laravel Method | Notes |
|---------------|----------------|-------|
| `int` | `integer()` | Auto-increment becomes `increments()` |
| `bigint` | `bigInteger()` | Auto-increment becomes `bigIncrements()` |
| `varchar` | `string()` | Length preserved |
| `text` | `text()` | |
| `decimal` | `decimal()` | Precision and scale preserved |
| `datetime` | `dateTime()` | |
| `json` | `json()` | MySQL/PostgreSQL |
| `jsonb` | `jsonb()` | PostgreSQL only |
| `uuid` | `uuid()` | PostgreSQL |
| `enum` | `enum()` | Values preserved |

## Foreign Key Dependencies

The package automatically analyzes foreign key relationships and orders migrations to prevent dependency issues:

1. **Dependency Analysis**: Identifies which tables depend on others
2. **Topological Sorting**: Orders tables to ensure dependencies are created first
3. **Circular Dependencies**: Detects and handles circular references by separating foreign keys

## Advanced Features

### Custom Stubs

You can customize the generated migration templates by publishing the stubs:

```bash
php artisan vendor:publish --tag=autogen-migration-stubs
```

This publishes stub files to `resources/stubs/autogen/migration/` which you can modify.

### Migration Validation

The package validates generated migrations for:
- PHP syntax errors
- Required migration methods
- Proper Laravel structure

### Backup and Recovery

When using `--force`, consider backing up existing migrations:

```php
// The generator provides methods for backup
$generator = app(MigrationGenerator::class);
$backupDir = $generator->backupExistingMigrations($config);
```

## Database-Specific Features

### MySQL/MariaDB

- Table engines (InnoDB, MyISAM, etc.)
- Character sets and collations
- Unsigned integer columns
- Auto-increment values
- Partition information (optional)

### PostgreSQL

- Sequences and serial columns
- JSONB columns
- UUID columns
- Custom data types
- Schema specification

### SQLite

- Simple column types
- Basic constraints
- Primary key handling

### SQL Server

- Identity columns
- NVARCHAR columns
- Unique identifiers
- Schema specification

## Troubleshooting

### Common Issues

1. **Connection Errors**: Ensure your database connection is properly configured in `config/database.php`

2. **Permission Errors**: Make sure the migration output directory is writable

3. **Memory Issues**: For large databases, increase PHP memory limit or process tables in chunks

4. **Circular Dependencies**: The package handles these automatically, but complex scenarios might require manual intervention

### Debug Mode

Enable verbose output to see detailed analysis:

```bash
php artisan autogen:migration --connection=mysql --all-tables -v
```

### Log Files

Migration generation activity is logged to Laravel's log files when enabled in configuration.

## Integration with Other AutoGen Packages

The Migration package works seamlessly with other AutoGen packages:

- **Model Package**: Generate models after creating migrations
- **Controller Package**: Create controllers for migrated tables
- **View Package**: Generate views for the complete CRUD workflow

Example workflow:

```bash
# 1. Generate migrations
php artisan autogen:migration --connection=legacy --all-tables

# 2. Run migrations
php artisan migrate

# 3. Generate models
php artisan autogen:model --connection=legacy --all-tables

# 4. Generate controllers
php artisan autogen:controller --connection=legacy --all-tables
```

## Contributing

When contributing to the Migration package:

1. Add tests for new database-specific features
2. Update column type mappings for new Laravel versions
3. Test with different database versions
4. Maintain backward compatibility

## Support

For issues specific to the Migration package:

1. Check the configuration options
2. Enable debug mode for detailed output
3. Verify database connection and permissions
4. Review generated migration syntax