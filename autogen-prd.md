# Product Requirements Document: Laravel "AutoGen" Package Suite

## 1. Introduction

Developers tasked with modernizing legacy systems often face the repetitive and error-prone task of creating boilerplate code to interface with an existing database. This project, the "AutoGen" suite, aims to solve this by providing a set of intelligent Laravel Artisan commands that scaffold high-quality Models, Controllers, Views, and supporting files directly from a pre-existing database schema.

The primary goal is to drastically accelerate the initial development phase of admin panels, internal tools, and modern interfaces for legacy applications, allowing developers to focus on business logic rather than boilerplate. The suite will be composed of multiple distinct but interconnected packages, leveraging the latest Laravel 12 and PHP 8.3+ features.

## 2. Goals and Objectives

- **Accelerate Development**: Drastically reduce the time required to create CRUD (Create, Read, Update, Delete) interfaces for existing database tables.
- **Enforce Consistency**: Ensure that all generated code follows Laravel best practices and a consistent architectural pattern.
- **Support Legacy Databases**: Provide a seamless workflow for working with multiple, pre-existing database connections within a single Laravel application.
- **Generate Quality Code**: Produce clean, readable, and easily extendable code that feels hand-written.
- **Offer Flexibility**: Allow developers to configure and override default behaviors to suit specific project needs.
- **Enable Customization**: Support custom stubs and templates for organization-specific standards.

## 3. Core Packages & Features

The suite will consist of six primary PHP packages for Laravel, with potential for future expansion.

### Package 1: autogen:model

This package is responsible for introspecting the database and generating eloquent model classes.

#### Command Signature:
```bash
php artisan autogen:model --connection=<name> [--dir=<path>] [--namespace=<namespace>] [--tables=<list>] [--force]
```

#### Arguments & Options:
- `--connection=<name>`: (Required) The name of the database connection as defined in config/database.php.
- `--dir=<path>`: (Optional) The subdirectory within app/Models/ where the models will be placed. E.g., Admin/Conn1.
- `--namespace=<namespace>`: (Optional) Custom namespace for the models. Defaults to App\Models\{dir}.
- `--tables=<list>`: (Optional) A comma-separated string of specific table names to generate models for. If omitted, all tables (respecting the configuration file) will be used.
- `--force`: (Optional) If present, will overwrite existing model files without prompting.

#### Generated Model Features:

The generator will intelligently populate the model class with the following properties, utilizing PHP 8.3+ features:

- `protected $connection = '...';`: Set to the specified connection name.
- `protected $table = '...';`: The name of the database table.
- `protected $primaryKey = '...';`: Automatically detects the primary key column name.
- `public $incrementing = (true|false);`: Detects if the primary key is auto-incrementing.
- `protected $keyType = '...';`: Detects if the primary key is an integer or string.
- `public $timestamps = (true|false);`: Detects the presence of created_at and updated_at columns.
- `protected $fillable = [...];`: Populated with all table columns, excluding the primary key and timestamp columns.
- `protected $hidden = ['password', 'remember_token'];`: Pre-filled with common sensitive fields.
- `protected $casts = [...];`: Intelligently infers appropriate casts based on database column types, using Laravel 12's improved casting system.
- `protected $dates = ['deleted_at'];`: Automatically added if soft deletes are detected.
- **Index Documentation**: Database indexes will be documented as PHPDoc comments above the class.
- **PHP 8.3 Features**: Uses readonly properties, typed class constants, and property hooks where appropriate.

Example generated model with PHP 8.3+ features:
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
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
        'settings',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'settings' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
    
    /**
     * Get the user's full name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name . ' ' . $this->last_name,
        );
    }
}
```

### Package 2: autogen:controller

This package generates controllers that are pre-wired to handle CRUD operations for the generated models.

#### Command Signature:
```bash
php artisan autogen:controller <model> [--type=<type>] [--no-requests] [--paginate=<number>] [--force]
```

#### Arguments & Options:
- `<model>`: (Required) The path to the model class, relative to App/Models/. E.g., Admin/Conn1/User.
- `--type=<type>`: (Optional) The type of controller. Can be resource (default), api, or web.
- `--no-requests`: (Optional) Skip form request generation for simpler controllers.
- `--paginate=<number>`: (Optional) Number of items per page for index methods. Default: 15.
- `--force`: (Optional) Overwrite the existing controller file.

#### Generated Controller Features:

- **Correct Location**: Controllers placed in appropriate directories (e.g., App/Http/Controllers/Api/Admin/Conn1/UserController.php).
- **Dependency Injection**: Methods automatically type-hint relevant models for route-model binding.
- **Query Optimization**: Index methods include eager loading for detected relationships.
- **Resource/Web Controllers**: Generate full CRUD methods with basic logic.
- **API Controllers**: Generate methods returning JSON responses using ApiResource classes.
- **Policy Stubs**: Optionally generate policy classes for authorization.

#### Feature Spotlight: Automatic Form Request Generation

When generating controllers (unless --no-requests is used), the command will:
- Generate StoreModelRequest and UpdateModelRequest classes
- Pre-populate validation rules inferred from database schema
- Include custom validation messages
- Support for unique validation rules with proper ignore clauses for updates

### Package 3: autogen:views

This package generates the Blade view files required for a full CRUD interface.

#### Command Signature:
```bash
php artisan autogen:views <model> [--framework=<name>] [--layout=<layout>] [--with-search] [--with-modals] [--force]
```

#### Arguments & Options:
- `<model>`: (Required) The path to the model class, relative to App/Models/.
- `--framework=<name>`: (Optional) CSS framework: tailwindcss (default), bootstrap, or plain.
- `--layout=<layout>`: (Optional) Master layout file to extend. Default: layouts.app.
- `--with-search`: (Optional) Include search/filter functionality in index views.
- `--with-modals`: (Optional) Use modal dialogs for delete confirmations.
- `--force`: (Optional) Overwrite existing view files.

#### Generated View Features:

- **Directory Structure**: Views created in resources/views/{model_path}/.
- **Standard CRUD Views**:
  - `index.blade.php`: Table view with pagination, sorting, and action links
  - `create.blade.php`: Form for creating new records
  - `edit.blade.php`: Form for editing existing records
  - `show.blade.php`: Detail page for single record
  - `_form.blade.php`: Reusable form partial
  - `_filters.blade.php`: Search/filter partial (if --with-search)
- **Multi-language Support**: Views use Laravel's localization helpers
- **Accessibility**: Proper ARIA labels and semantic HTML

### Package 4: autogen:migration

This package reverse-engineers existing database schemas to create migration files.

#### Command Signature:
```bash
php artisan autogen:migration --connection=<name> [--tables=<list>] [--with-foreign-keys] [--force]
```

#### Arguments & Options:
- `--connection=<name>`: (Required) Database connection name.
- `--tables=<list>`: (Optional) Specific tables to generate migrations for.
- `--with-foreign-keys`: (Optional) Include foreign key constraints.
- `--force`: (Optional) Overwrite existing migration files.

#### Features:
- Generate migration files that recreate existing table structures
- Preserve indexes, constraints, and column modifiers
- Useful for version control and deployment

### Package 5: autogen:factory

Generate model factories based on database schema and column types.

#### Command Signature:
```bash
php artisan autogen:factory <model> [--with-states] [--force]
```

#### Features:
- Generate realistic fake data based on column names and types
- Create common states (e.g., published, draft for posts)
- Integration with relationships

### Package 7: autogen:datatable

Dedicated package for generating high-performance datatable implementations optimized for large datasets.

#### Command Signature:
```bash
php artisan autogen:datatable <model> [--type=<type>] [--searchable=<columns>] [--exportable] [--force]
```

#### Arguments & Options:
- `<model>`: (Required) The model to create datatable for.
- `--type=<type>`: (Optional) Implementation type: yajra (default), livewire, inertia, or custom.
- `--searchable=<columns>`: (Optional) Comma-separated list of searchable columns.
- `--exportable`: (Optional) Include export functionality.
- `--force`: (Optional) Overwrite existing files.

#### Features for Large Datasets:

**1. Database Optimizations:**
- Automatic index recommendations based on searchable columns
- Generated migration for adding composite indexes
- FULLTEXT index support for MySQL/PostgreSQL
- Materialized view generation for complex queries

**2. Query Optimizations:**
- Cursor-based pagination for consistent performance
- Query result caching with Redis
- Automatic N+1 query prevention
- Database-specific query optimizations

**3. Performance Features:**
- Server-sent events (SSE) for real-time updates
- Background job processing for exports
- Chunked data loading
- Virtual scrolling support

**Generated Performance Configuration:**
```php
// config/autogen-datatables.php
return [
    'performance' => [
        'chunk_size' => 1000,
        'max_export_rows' => 50000,
        'cache_ttl' => 300, // 5 minutes
        'use_cursor_pagination' => true,
        'enable_query_log' => false,
    ],
    
    'indexes' => [
        'auto_create' => true,
        'composite_threshold' => 3, // Create composite index if 3+ columns searched together
    ],
    
    'search' => [
        'min_search_length' => 3,
        'use_fulltext' => true,
        'debounce_ms' => 300,
    ],
];
```

**Example Generated Index Migration:**
```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        // Single column indexes for sorting
        $table->index('created_at');
        $table->index('email');
        $table->index('status');
        
        // Composite index for common search patterns
        $table->index(['status', 'created_at']);
        
        // Full-text index for text search (MySQL)
        DB::statement('ALTER TABLE users ADD FULLTEXT fulltext_name_email (name, email)');
    });
}
```

## 4. Configuration System

### Main Configuration File (config/autogen.php):

```php
return [
    // Tables to ignore across all generators
    'tables_to_ignore' => [
        'migrations',
        'failed_jobs',
        'password_resets',
        'password_reset_tokens',
        'personal_access_tokens',
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
    ],
    
    // Base model class
    'base_model' => \Illuminate\Database\Eloquent\Model::class,
    
    // Naming conventions
    'naming_conventions' => [
        'model_suffix' => '',
        'controller_suffix' => 'Controller',
        'request_suffix' => 'Request',
        'resource_suffix' => 'Resource',
        'policy_suffix' => 'Policy',
    ],
    
    // Default options
    'defaults' => [
        'views_framework' => 'tailwindcss',
        'controller_type' => 'resource',
        'pagination' => 15,
        'api_version' => 'v1',
    ],
    
    // Database column type mappings
    'column_type_mappings' => [
        'tinyint(1)' => 'boolean',
        'json' => 'array',
        'jsonb' => 'array',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
        'smallint' => 'integer',
        'bigint' => 'integer',
        'decimal' => 'decimal:2',
        'float' => 'float',
        'double' => 'double',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'time' => 'string',
        'year' => 'integer',
    ],
    
    // Validation rule mappings
    'validation_rules' => [
        'varchar' => 'string|max:{length}',
        'char' => 'string|size:{length}',
        'text' => 'string',
        'integer' => 'integer',
        'tinyint' => 'integer|min:0|max:255',
        'smallint' => 'integer',
        'bigint' => 'integer',
        'decimal' => 'numeric',
        'float' => 'numeric',
        'boolean' => 'boolean',
        'date' => 'date',
        'datetime' => 'date',
        'email' => 'email|max:255',
        'json' => 'json',
    ],
    
    // Custom stubs directory
    'custom_stubs_path' => null, // e.g., resource_path('stubs/autogen')
    
    // Relationship detection
    'relationships' => [
        'detect_belongsto' => true,
        'detect_hasmany' => true,
        'detect_manytomany' => true,
        'foreign_key_suffix' => '_id',
    ],
];
```

## 5. Custom Stub System

Allow users to override default templates by placing custom stubs in a configured directory:

```
resources/stubs/autogen/
├── model.stub
├── controller/
│   ├── resource.stub
│   ├── api.stub
│   └── web.stub
├── request/
│   ├── store.stub
│   └── update.stub
├── views/
│   ├── tailwind/
│   │   ├── index.stub
│   │   ├── create.stub
│   │   └── ...
│   └── bootstrap/
│       └── ...
└── factory.stub
```

## 6. Master Scaffold Command

A convenience command that runs multiple generators in sequence:

```bash
php artisan autogen:scaffold <table> [--connection=<name>] [--only=<list>] [--except=<list>]
```

Options:
- `--only=<list>`: Only run specified generators (e.g., model,controller,views)
- `--except=<list>`: Run all generators except specified ones

## 7. Future Enhancements

### Phase 2 Features:
- **Route Generation**: Automatically append routes to web.php or api.php
- **Test Generation**: Generate feature and unit tests
- **GraphQL Support**: Generate GraphQL types and resolvers
- **Event/Listener Generation**: Generate events for model actions
- **Notification Templates**: Generate notification classes for CRUD operations

### Phase 3 Features:
- **Advanced Relationship Support**: belongsToMany and polymorphic relationships
- **Multi-tenancy Support**: Generate tenant-aware models and controllers
- **Activity Logging**: Integrate with spatie/laravel-activitylog
- **Export/Import Features**: Generate Excel export/import functionality
- **Advanced Search**: Integrate with Laravel Scout or Elasticsearch

### Database Support Expansion:
- PostgreSQL-specific features (arrays, JSON operators)
- SQL Server compatibility
- MongoDB support via Laravel MongoDB package

## 8. Success Metrics

- **Time Savings**: Reduce initial CRUD development time by 80%
- **Code Quality**: Generated code passes Laravel Pint/PHP CS Fixer standards
- **Adoption**: Used in 1000+ Laravel projects within first year
- **Community**: Active contributor base with 50+ contributors

## 9. Technical Requirements

- PHP 8.3+
- Laravel 12.0+
- Composer 2.0+
- Support for MySQL 8.0+, MariaDB 10.6+, PostgreSQL 14+, SQLite 3.35+
- PSR-4 autoloading compliance
- Comprehensive test coverage (>90%)
- Full type declarations and strict types
- Leverages PHP 8.3+ features (readonly properties, typed class constants, etc.)

## 11. Performance Considerations for Large Datasets

### Database-Level Optimizations:
1. **Automatic Index Analysis**: Analyze query patterns and suggest optimal indexes
2. **Partitioning Support**: Generate partitioned tables for time-series data
3. **Read Replicas**: Support for read/write splitting in generated code
4. **Database Views**: Generate database views for complex queries

### Application-Level Optimizations:
1. **Caching Strategy**: 
   - Query result caching with automatic invalidation
   - Full-page caching for read-heavy tables
   - Redis/Memcached integration

2. **Search Implementation**:
   - Elasticsearch integration for full-text search
   - Database-specific search (PostgreSQL tsvector, MySQL FULLTEXT)
   - Algolia/MeiliSearch adapters for hosted search

3. **Export Handling**:
   - Queue-based exports for large datasets
   - Streaming responses for CSV/Excel downloads
   - S3 integration for temporary file storage

### Frontend Optimizations:
1. **Progressive Loading**:
   - Virtual scrolling for infinite datasets
   - Lazy loading with intersection observer
   - Skeleton screens during loading

2. **State Management**:
   - URL-based state for shareable filtered views
   - Local storage for user preferences
   - Optimistic UI updates

### Monitoring and Analytics:
1. **Performance Tracking**:
   - Query performance logging
   - Slow query alerts
   - Usage analytics for optimization

Example usage for a million-record table:
```bash
# Generate optimized datatable for large dataset
php artisan autogen:datatable Order --type=yajra --searchable=order_number,customer_name,status --exportable

# This generates:
# - OrderDataTableController with optimized queries
# - Database migration with proper indexes
# - Redis caching configuration
# - Background job for exports
# - Frontend with virtual scrolling
```