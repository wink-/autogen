# Laravel AutoGen Package Suite

[![Latest Version on Packagist](https://img.shields.io/packagist/v/autogen/laravel-autogen.svg?style=flat-square)](https://packagist.org/packages/autogen/laravel-autogen)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/autogen/laravel-autogen/run-tests?label=tests)](https://github.com/autogen/laravel-autogen/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/autogen/laravel-autogen/Check%20&%20fix%20styling?label=code%20style)](https://github.com/autogen/laravel-autogen/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/autogen/laravel-autogen.svg?style=flat-square)](https://packagist.org/packages/autogen/laravel-autogen)

**The Laravel AutoGen Package Suite** is a comprehensive collection of intelligent code generation tools designed to accelerate development by creating high-quality CRUD interfaces directly from existing database schemas. Perfect for modernizing legacy applications, rapid prototyping, and building admin panels with enterprise-grade features.

## Why AutoGen?

- 🚀 **10x Faster Development**: Generate complete CRUD interfaces in seconds, not hours
- 🎯 **Legacy System Friendly**: Seamlessly work with existing databases and schemas
- 🤖 **AI-Powered**: Optional AI assistance for intelligent code generation
- 🏗️ **Enterprise Ready**: Built-in security, performance optimizations, and best practices
- 🎨 **Multi-Framework**: Support for Tailwind CSS, Bootstrap, and custom styling
- 📱 **Modern Standards**: PHP 8.3+, Laravel 12+, and latest web standards

## Complete Package Suite

### Core Packages

#### 🏗️ **AutoGen:Model** - Intelligent Model Generation
Generate Eloquent models with advanced features:
- **Smart Introspection**: Automatically detects relationships, indexes, and constraints
- **PHP 8.3+ Features**: Uses readonly properties, typed constants, and property hooks
- **Relationship Detection**: Automatically maps belongsTo, hasMany, and many-to-many relationships
- **Advanced Casting**: Intelligent type casting based on database column types
- **Trait Integration**: Optional soft deletes, UUID, and custom trait support

```bash
php artisan autogen:model --connection=legacy --tables=users,posts,categories
```

#### 🎮 **AutoGen:Controller** - Resource Controller Generation
Create feature-complete controllers:
- **Multiple Types**: Resource, API, and web controllers
- **Form Requests**: Automatic validation rule generation
- **Policy Integration**: Authorization support out of the box
- **Query Optimization**: Built-in eager loading and performance optimizations
- **Route Model Binding**: Proper type-hinting and dependency injection

```bash
php artisan autogen:controller User --type=resource --with-requests --with-policies
```

#### 🎨 **AutoGen:Views** - Multi-Framework View Generation
Generate beautiful, accessible Blade templates:
- **3 CSS Frameworks**: Tailwind CSS, Bootstrap 5, or custom CSS
- **Responsive Design**: Mobile-first with adaptive layouts
- **Accessibility**: WCAG 2.1 AA compliant
- **Interactive Features**: Search, filters, modals, and datatables
- **Customizable**: Use your own layouts and styling

```bash
php artisan autogen:views User --framework=tailwind --with-datatable --with-search
```

#### 🔄 **AutoGen:Migration** - Reverse Migration Generation
Convert existing schemas to Laravel migrations:
- **Schema Preservation**: Maintains indexes, constraints, and relationships
- **Version Control**: Perfect for legacy system documentation
- **Deployment Ready**: Generate migrations for team collaboration

```bash
php artisan autogen:migration --connection=legacy --with-foreign-keys
```

#### 🏭 **AutoGen:Factory** - Intelligent Factory Generation
Create realistic test data factories:
- **Smart Fake Data**: Context-aware fake data based on column names
- **Relationship Support**: Automatically handles model relationships
- **Custom States**: Generate common model states (published, draft, etc.)
- **Localization**: Multi-language fake data support

```bash
php artisan autogen:factory User --with-states --with-relationships
```

#### 📊 **AutoGen:Datatable** - High-Performance Data Tables
Generate enterprise-grade datatables for large datasets:
- **Multiple Implementations**: Yajra DataTables, Livewire, Inertia.js
- **Performance Optimized**: Cursor pagination, caching, query optimization
- **Export Features**: Excel, CSV, PDF export support
- **Real-time Updates**: Server-sent events for live data
- **Advanced Search**: Full-text search, filters, and sorting

```bash
php artisan autogen:datatable User --type=yajra --searchable=name,email --exportable
```

### Advanced Packages

#### 🧪 **AutoGen:Test** - Comprehensive Test Generation
Generate complete test suites:
- **Multiple Frameworks**: PHPUnit and Pest support
- **Feature & Unit Tests**: Complete test coverage
- **Database Testing**: Factory integration and transactions
- **API Testing**: JSON response validation

#### 📚 **AutoGen:Documentation** - API Documentation Generation
Auto-generate API documentation:
- **OpenAPI/Swagger**: Industry-standard API docs
- **Interactive**: Testable API endpoints
- **Version Control**: Track API changes over time

#### ⚡ **AutoGen:Optimization** - Performance Analysis
Analyze and optimize generated code:
- **Query Analysis**: N+1 detection and optimization
- **Cache Recommendations**: Intelligent caching strategies
- **Performance Metrics**: Benchmark generated code

#### 🎼 **AutoGen:Scaffold** - Complete Application Scaffolding
Generate entire application sections:
- **End-to-End**: Models, controllers, views, tests, and routes
- **Dependency Resolution**: Automatic package orchestration
- **Progress Tracking**: Visual feedback during generation
- **Rollback Support**: Undo generation if needed

```bash
php artisan autogen:scaffold Blog --includes=posts,categories,tags --framework=tailwind
```

## Installation

### Requirements

- **PHP**: 8.3 or higher
- **Laravel**: 12.0 or higher
- **Database**: MySQL 8.0+, PostgreSQL 13+, or SQLite 3.35+
- **Memory**: Minimum 512MB (1GB+ recommended for large schemas)

### Quick Installation

```bash
# Install the package
composer require autogen/laravel-autogen

# Publish configuration files
php artisan vendor:publish --provider="AutoGen\AutoGenServiceProvider"

# Optional: Publish stubs for customization
php artisan vendor:publish --tag="autogen-stubs"
```

### Configuration

#### 1. Basic Configuration

Edit `config/autogen.php` to configure the package:

```php
<?php

return [
    // Default database connection for introspection
    'database' => [
        'default_connection' => env('DB_CONNECTION', 'mysql'),
    ],
    
    // AI provider configuration (optional)
    'ai' => [
        'default_provider' => env('AUTOGEN_AI_PROVIDER', 'openai'),
        'providers' => [
            'openai' => [
                'api_key' => env('OPENAI_API_KEY'),
                'model' => env('OPENAI_MODEL', 'gpt-4'),
            ],
        ],
    ],
    
    // Security and performance settings
    'security' => [
        'enable_authorization' => true,
        'generate_policies' => true,
    ],
];
```

#### 2. Environment Configuration

Add to your `.env` file:

```env
# Optional: AI Provider Configuration
AUTOGEN_AI_PROVIDER=openai
OPENAI_API_KEY=your_openai_api_key

# Optional: Legacy Database Connections
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_DATABASE=legacy_database
LEGACY_DB_USERNAME=legacy_user
LEGACY_DB_PASSWORD=legacy_password
```

#### 3. Multiple Database Support

For legacy systems with multiple databases, add connections to `config/database.php`:

```php
'connections' => [
    // Your existing connections...
    
    'legacy_mysql' => [
        'driver' => 'mysql',
        'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
        'port' => env('LEGACY_DB_PORT', '3306'),
        'database' => env('LEGACY_DB_DATABASE'),
        'username' => env('LEGACY_DB_USERNAME'),
        'password' => env('LEGACY_DB_PASSWORD'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ],
],
```

## Quick Start Guide

### 1. Generate a Complete CRUD Interface

For the fastest setup, use the scaffold command to generate everything at once:

```bash
# Generate complete CRUD for a User model from existing table
php artisan autogen:scaffold User --framework=tailwind --with-datatable

# This creates:
# - App/Models/User.php (Eloquent model)
# - App/Http/Controllers/UserController.php (Resource controller)
# - App/Http/Requests/StoreUserRequest.php & UpdateUserRequest.php
# - resources/views/users/ (Complete view templates)
# - database/factories/UserFactory.php (Test factory)
# - tests/Feature/UserControllerTest.php (Feature tests)
```

### 2. Individual Package Usage

#### Generate Models from Existing Database

```bash
# Generate models for all tables
php artisan autogen:model --connection=mysql

# Generate specific models
php artisan autogen:model --tables=users,posts,categories --connection=legacy

# Generate in subdirectory with custom namespace
php artisan autogen:model --dir=Admin --namespace="App\Models\Admin" --connection=legacy
```

#### Generate Controllers with Form Requests

```bash
# Basic resource controller
php artisan autogen:controller User

# API controller with policies
php artisan autogen:controller User --type=api --with-policies

# Skip form request generation
php artisan autogen:controller User --no-requests
```

#### Generate Views with Different Frameworks

```bash
# Tailwind CSS views (default)
php artisan autogen:views User --with-search --with-modals

# Bootstrap 5 views
php artisan autogen:views User --framework=bootstrap --with-datatable

# Custom CSS views
php artisan autogen:views User --framework=css --layout=admin.layout
```

#### Generate High-Performance Datatables

```bash
# Yajra DataTable implementation
php artisan autogen:datatable User --searchable=name,email,role --exportable

# Livewire datatable
php artisan autogen:datatable User --type=livewire --with-filters

# Inertia.js datatable
php artisan autogen:datatable User --type=inertia --with-export
```

#### Reverse Engineer Migrations

```bash
# Generate migrations from existing schema
php artisan autogen:migration --connection=legacy --with-foreign-keys

# Specific tables only
php artisan autogen:migration --tables=users,posts --with-indexes
```

#### Generate Factories with Realistic Data

```bash
# Basic factory
php artisan autogen:factory User

# Factory with states and relationships
php artisan autogen:factory User --with-states --with-relationships
```

### 3. Working with Legacy Databases

#### Multiple Database Connections

```bash
# Add legacy connection to config/database.php
'legacy_system' => [
    'driver' => 'mysql',
    'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
    'database' => env('LEGACY_DB_DATABASE'),
    'username' => env('LEGACY_DB_USERNAME'),
    'password' => env('LEGACY_DB_PASSWORD'),
],

# Generate from legacy connection
php artisan autogen:model --connection=legacy_system --dir=Legacy
php artisan autogen:migration --connection=legacy_system
```

#### Complex Schema Handling

```bash
# Handle tables with complex relationships
php artisan autogen:scaffold BlogPost --connection=cms_db --includes=categories,tags,authors

# Ignore system tables
php artisan autogen:model --connection=legacy --ignore=cache,sessions,migrations
```

## Real-World Examples

### Example 1: E-commerce Product Management

```bash
# Generate complete product management system
php artisan autogen:scaffold Product --connection=ecommerce --framework=bootstrap

# Add related models
php artisan autogen:model --tables=categories,brands,reviews --connection=ecommerce

# Generate specialized datatables for large product catalogs
php artisan autogen:datatable Product --type=yajra --searchable=name,sku,category --exportable
```

### Example 2: Legacy CRM Migration

```bash
# Connect to legacy CRM database
php artisan autogen:model --connection=legacy_crm --dir=CRM --namespace="App\Models\CRM"

# Generate admin interface
php artisan autogen:views Customer --framework=tailwind --layout=admin.layout --with-search

# Create API endpoints for mobile app
php artisan autogen:controller Customer --type=api --with-policies
```

### Example 3: Multi-tenant Application

```bash
# Generate tenant-aware models
php artisan autogen:model --connection=tenant_db --namespace="App\Models\Tenant"

# Generate controllers with proper middleware
php artisan autogen:controller TenantUser --middleware=tenant.scope

# Create tenant-specific views
php artisan autogen:views TenantUser --layout=tenant.layout
```

## Advanced Features

### AI-Powered Generation

Enable AI assistance for smarter code generation:

```bash
# Set up AI provider
AUTOGEN_AI_PROVIDER=openai
OPENAI_API_KEY=your_api_key

# Generate with AI assistance
php artisan autogen:model User --with-ai-suggestions
php artisan autogen:controller User --ai-optimize-queries
```

### Custom Templates

Create your own templates for organization-specific standards:

```bash
# Publish default stubs
php artisan vendor:publish --tag="autogen-stubs"

# Customize stubs in resources/stubs/autogen/
# Use your stubs
php artisan autogen:model User --use-custom-stubs
```

### Performance Optimization

Generate optimized code for large-scale applications:

```bash
# Enable query optimization
php artisan autogen:controller User --optimize-queries --enable-caching

# Generate with performance analysis
php artisan autogen:datatable Product --analyze-performance --optimize-indexes
```

## Testing

The package includes comprehensive test suites:

```bash
# Run all tests
composer test

# Run with coverage report
composer test-coverage

# Run specific package tests
vendor/bin/phpunit tests/Unit/Packages/Model/
vendor/bin/phpunit tests/Feature/ViewGeneratorCommandTest.php

# Run static analysis
composer analyse

# Run code style checks
composer format-check

# Fix code style issues
composer format
```

### Testing Your Generated Code

AutoGen can also generate tests for your generated code:

```bash
# Generate tests for your models and controllers
php artisan autogen:test User --feature --unit

# Generate API tests
php artisan autogen:test User --api-tests

# Generate with factories
php artisan autogen:test User --with-factories
```

## Performance Benchmarks

AutoGen is designed for performance with large schemas:

- **Small Projects** (1-10 tables): < 1 second generation time
- **Medium Projects** (10-50 tables): < 10 seconds generation time  
- **Large Projects** (50+ tables): < 30 seconds generation time
- **Enterprise Projects** (100+ tables): < 60 seconds with caching

Performance optimizations:
- Schema caching for repeated operations
- Lazy loading of relationships
- Chunked processing for large datasets
- Database query optimization
- Parallel processing for multiple files

## Troubleshooting

### Common Issues

#### Database Connection Issues
```bash
# Test your database connection
php artisan autogen:test-connection --connection=legacy

# Check available tables
php artisan autogen:list-tables --connection=legacy
```

#### Memory Issues with Large Schemas
```php
// config/autogen.php
'database' => [
    'max_table_scan' => 50, // Reduce for memory constraints
    'cache_schema' => true,  // Enable caching
],
```

#### Permission Issues
```bash
# Check directory permissions
chmod -R 755 app/Models
chmod -R 755 resources/views

# Fix ownership (if needed)
chown -R www-data:www-data storage/
```

### Getting Help

1. **Documentation**: Check the [wiki](https://github.com/autogen/laravel-autogen/wiki)
2. **Issues**: Search [existing issues](https://github.com/autogen/laravel-autogen/issues)
3. **Discussions**: Join [GitHub Discussions](https://github.com/autogen/laravel-autogen/discussions)
4. **Stack Overflow**: Tag questions with `laravel-autogen`

## Roadmap

### Upcoming Features

- **v1.1**: Vue.js and React component generation
- **v1.2**: GraphQL API generation
- **v1.3**: Microservice architecture support  
- **v1.4**: Advanced AI code optimization
- **v1.5**: Visual schema designer
- **v2.0**: Multi-framework support (Symfony, CodeIgniter)

### Community Contributions

We welcome contributions! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

Priority areas for contribution:
- Additional CSS framework support
- More AI provider integrations
- Database driver support (Oracle, SQL Server)
- Localization and internationalization
- Performance optimizations

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details on how to contribute.

## Security Vulnerabilities

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities responsibly.

## Credits

- **Lead Developer**: [AutoGen Team](https://github.com/autogen)
- **Contributors**: [All Contributors](https://github.com/autogen/laravel-autogen/contributors)
- **Inspired by**: Laravel Generators, Blueprint, and the Laravel community

Special thanks to:
- Laravel team for the amazing framework
- Yajra DataTables for datatable integration
- Spatie for package development tools
- The PHP community for continuous innovation

## Support

### Commercial Support

Professional support and custom development available:
- **Priority Support**: Guaranteed response times
- **Custom Development**: Tailored solutions for your organization
- **Training**: Team training and workshops
- **Consulting**: Architecture and best practices guidance

Contact: [support@autogen.dev](mailto:support@autogen.dev)

### Sponsors

AutoGen is supported by:
- [Your Company Name](https://yourcompany.com) - Platinum Sponsor
- [Another Company](https://anothercompany.com) - Gold Sponsor

[Become a sponsor](https://github.com/sponsors/autogen) to support development.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

<div align="center">

**Made with ❤️ for the Laravel community**

[Website](https://autogen.dev) • [Documentation](https://docs.autogen.dev) • [Twitter](https://twitter.com/autogenphp) • [Discord](https://discord.gg/autogen)

</div>