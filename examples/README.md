# AutoGen Examples

This directory contains comprehensive examples demonstrating how to use the Laravel AutoGen package suite effectively.

## Directory Structure

- **`configurations/`** - Sample configuration files for different scenarios
- **`generated-code/`** - Examples of generated models, controllers, views, and tests
- **`database-schemas/`** - Sample database schemas for testing and learning
- **`tutorials/`** - Step-by-step tutorials for common use cases

## Quick Examples

### Basic E-commerce Setup

```bash
# 1. Generate product model from existing database
php artisan autogen:model Product --connection=ecommerce

# 2. Generate resource controller with form requests
php artisan autogen:controller Product --with-requests

# 3. Generate Tailwind CSS views with search functionality
php artisan autogen:views Product --framework=tailwind --with-search

# 4. Generate high-performance datatable
php artisan autogen:datatable Product --type=yajra --searchable=name,sku --exportable
```

### Legacy System Migration

```bash
# 1. Connect to legacy database and generate all models
php artisan autogen:model --connection=legacy_system --dir=Legacy

# 2. Generate migrations from existing schema for version control
php artisan autogen:migration --connection=legacy_system --with-foreign-keys

# 3. Generate API controllers for mobile app integration
php artisan autogen:controller Legacy/Customer --type=api --with-policies
```

### Complete Blog System

```bash
# Generate complete blog system in one command
php artisan autogen:scaffold BlogPost --includes=categories,tags,comments --framework=bootstrap
```

## Configuration Examples

See the `configurations/` directory for:
- Multi-database setup examples
- AI provider configurations
- Performance optimization settings
- Security hardening configurations
- Custom template configurations

## Generated Code Examples

The `generated-code/` directory contains examples of:
- Eloquent models with relationships
- Resource and API controllers
- Form request classes
- Blade view templates (Tailwind, Bootstrap, CSS)
- Model factories with realistic data
- DataTable implementations
- Test classes (PHPUnit and Pest)

## Database Schema Examples

The `database-schemas/` directory includes:
- E-commerce database schema
- Blog/CMS schema
- CRM system schema
- Multi-tenant application schema
- Legacy system examples

## Tutorials

Step-by-step tutorials are available in the `tutorials/` directory:
1. Getting Started with AutoGen
2. Working with Legacy Databases
3. Building an Admin Panel
4. API Development with AutoGen
5. Performance Optimization
6. Custom Template Development
7. AI-Powered Code Generation
8. Testing Generated Code

## Running Examples

To use these examples:

1. Copy the relevant configuration to your Laravel project
2. Set up the database connections as shown
3. Run the AutoGen commands as demonstrated
4. Review the generated code and customize as needed

## Need Help?

- Check the main [README.md](../README.md) for detailed documentation
- Review [CONTRIBUTING.md](../CONTRIBUTING.md) for development guidelines
- Visit our [GitHub Discussions](https://github.com/autogen/laravel-autogen/discussions) for community support