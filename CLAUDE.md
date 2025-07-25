# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AutoGen is a Laravel package suite that automatically generates boilerplate code from existing database schemas. It consists of six modular packages that can be used independently or together via the master `autogen:scaffold` command.

## Key Commands

### Generation Commands
```bash
# Generate model from database table
php artisan autogen:model --connection=<name> --table=<table>

# Generate controller for a model
php artisan autogen:controller <ModelName>

# Generate views for a model
php artisan autogen:views <ModelName> --framework=<tailwind|bootstrap|css>

# Generate migration from existing database
php artisan autogen:migration --connection=<name> --table=<table>

# Generate factory for a model
php artisan autogen:factory <ModelName>

# Generate datatable for a model
php artisan autogen:datatable <ModelName> --type=<yajra|livewire|inertia>

# Master scaffold command (generates all components)
php artisan autogen:scaffold <table> --connection=<name>
```

### Development Commands
```bash
# Run Laravel development server
php artisan serve

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Run migrations
php artisan migrate

# Install dependencies
composer install
```

## Architecture Overview

### Package Structure
The project is organized as six interconnected Laravel packages:

1. **Model Package** (`autogen:model`)
   - Introspects database schema
   - Generates Eloquent models with relationships, casts, and validation
   - Supports multiple database connections

2. **Controller Package** (`autogen:controller`)
   - Generates CRUD controllers with form requests
   - Includes authorization via policies
   - Supports both web and API controllers

3. **Views Package** (`autogen:views`)
   - Generates Blade templates
   - Supports Tailwind CSS, Bootstrap, and plain CSS
   - Creates index, create, edit, and show views

4. **Migration Package** (`autogen:migration`)
   - Reverse engineers database schemas
   - Preserves indexes, foreign keys, and constraints
   - Handles multi-database scenarios

5. **Factory Package** (`autogen:factory`)
   - Generates model factories for testing
   - Smart fake data generation based on column names

6. **Datatable Package** (`autogen:datatable`)
   - High-performance datatable implementations
   - Supports Yajra DataTables, Livewire, and Inertia.js
   - Optimized for large datasets (millions of rows)

### Key Design Patterns

- **Plugin Architecture**: Each package is independent but integrates seamlessly
- **Stub-based Generation**: Customizable templates for all generated files
- **Configuration-driven**: Extensive configuration options in `config/autogen.php`
- **Database-first Design**: All generation starts from existing database schemas

### Performance Considerations

When working with large datasets:
- Use cursor-based pagination for tables with >100k rows
- Implement caching strategies with Redis/Memcached
- Consider background job processing for heavy operations
- Leverage database-specific optimizations (partitioning, indexes)

### Technical Requirements

- PHP 8.3+ (uses readonly properties, typed constants)
- Laravel 12.0+
- Composer 2.0+
- Supported databases: MySQL 8.0+, PostgreSQL 14+, MariaDB 10.6+, SQLite 3.35+

### Important Files and Locations

- Configuration: `config/autogen.php` (publish with `php artisan vendor:publish --tag=autogen-config`)
- Custom stubs: `resources/stubs/autogen/`
- Generated models: `app/Models/`
- Generated controllers: `app/Http/Controllers/`
- Generated views: `resources/views/`
- PRD documentation: `autogen-prd.md`