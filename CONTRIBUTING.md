# Contributing to Laravel AutoGen Package Suite

Thank you for considering contributing to the Laravel AutoGen Package Suite! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Guidelines](#contributing-guidelines)
- [Code Style](#code-style)
- [Testing](#testing)
- [Submitting Changes](#submitting-changes)
- [Package Structure](#package-structure)
- [Reporting Issues](#reporting-issues)

## Code of Conduct

This project adheres to a code of conduct that we expect all contributors to follow. Please be respectful and constructive in all interactions.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Set up the development environment
4. Create a feature branch for your changes
5. Make your changes and test them
6. Submit a pull request

## Development Setup

### Prerequisites

- PHP 8.3 or higher
- Composer
- Laravel 12.0+
- Git

### Installation

```bash
# Clone your fork
git clone https://github.com/your-username/laravel-autogen.git
cd laravel-autogen

# Install dependencies
composer install

# Install development dependencies
composer install --dev

# Set up pre-commit hooks (optional but recommended)
composer run pre-commit
```

### Environment Setup

Create a `.env` file for testing:

```bash
cp .env.example .env
```

Configure your database connections for testing:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autogen_test
DB_USERNAME=root
DB_PASSWORD=

# Optional: Configure AI providers for testing
OPENAI_API_KEY=your_openai_key
ANTHROPIC_API_KEY=your_anthropic_key
```

## Contributing Guidelines

### Types of Contributions

We welcome several types of contributions:

1. **Bug Fixes**: Fix issues reported in GitHub issues
2. **Feature Enhancements**: Improve existing features
3. **New Features**: Add new functionality (discuss first in issues)
4. **Documentation**: Improve or add documentation
5. **Tests**: Add or improve test coverage
6. **Performance**: Optimize existing code

### Before Contributing

1. **Check existing issues**: Look for existing issues or feature requests
2. **Discuss major changes**: For significant features, open an issue first
3. **Follow the roadmap**: Align contributions with project goals
4. **Review the codebase**: Understand the existing architecture

## Code Style

This project follows Laravel coding standards and PSR-12.

### PHP Code Style

```php
<?php

declare(strict_types=1);

namespace AutoGen\Example;

use Illuminate\Support\Collection;

/**
 * Example class demonstrating code style.
 */
class ExampleClass
{
    public function __construct(
        private readonly string $property,
        private readonly Collection $items,
    ) {
    }

    public function exampleMethod(string $parameter): array
    {
        return [
            'property' => $this->property,
            'parameter' => $parameter,
            'items' => $this->items->toArray(),
        ];
    }
}
```

### Code Style Tools

Run code style checks and fixes:

```bash
# Check code style
composer run format-check

# Fix code style automatically
composer run format

# Run CS Fixer
composer run cs-fix

# Check with CS Fixer (dry run)
composer run cs-check
```

### PHP Standards

- Use strict types: `declare(strict_types=1);`
- Use typed properties and return types
- Use PHP 8.3+ features where appropriate
- Follow PSR-12 coding standard
- Use meaningful variable and method names
- Add PHPDoc blocks for complex methods
- Use readonly properties when possible

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run Pest tests
composer test-pest

# Run specific test file
vendor/bin/phpunit tests/Unit/Packages/Model/ModelGeneratorTest.php

# Run PHPStan analysis
composer analyse
```

### Writing Tests

#### Test Structure

```php
<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Model;

use AutoGen\Tests\TestCase;
use AutoGen\Packages\Model\ModelGenerator;

class ModelGeneratorTest extends TestCase
{
    private ModelGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->generator = new ModelGenerator();
    }

    public function test_it_can_generate_basic_model(): void
    {
        // Arrange
        $tableName = 'users';
        $expectedContent = '// expected model content';

        // Act
        $result = $this->generator->generate($tableName);

        // Assert
        $this->assertStringContainsString($expectedContent, $result);
    }
}
```

#### Test Guidelines

- Write descriptive test method names
- Use AAA pattern (Arrange, Act, Assert)
- Test both success and failure scenarios
- Mock external dependencies
- Use database transactions for database tests
- Clean up after tests

### Database Testing

For packages that interact with databases:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_interaction(): void
    {
        // Your test implementation
    }
}
```

## Submitting Changes

### Pull Request Process

1. **Create a feature branch**: `git checkout -b feature/new-feature`
2. **Make your changes**: Implement your feature or fix
3. **Write/update tests**: Ensure good test coverage
4. **Run the test suite**: Make sure all tests pass
5. **Update documentation**: Update relevant documentation
6. **Commit your changes**: Use descriptive commit messages
7. **Push to your fork**: `git push origin feature/new-feature`
8. **Submit a pull request**: Include a clear description

### Commit Message Format

Use conventional commit format:

```
type(scope): description

[optional body]

[optional footer]
```

Examples:

```
feat(model): add support for UUID primary keys
fix(controller): resolve issue with relationship loading
docs(readme): update installation instructions
test(factory): add tests for relationship factories
```

### Pull Request Template

When submitting a PR, include:

- **Description**: What does this change do?
- **Motivation**: Why is this change needed?
- **Testing**: How was this tested?
- **Documentation**: Any documentation updates needed?
- **Breaking Changes**: Are there any breaking changes?

## Package Structure

### Directory Organization

```
src/
├── Common/                 # Shared utilities
│   ├── AI/                # AI provider integrations
│   ├── Analysis/          # Code analysis tools
│   ├── Contracts/         # Interfaces
│   ├── Exceptions/        # Custom exceptions
│   ├── Formatting/        # Code formatting
│   ├── Templates/         # Template engine
│   └── Traits/           # Shared traits
└── Packages/              # Individual packages
    ├── Model/             # Model generator
    ├── Controller/        # Controller generator
    ├── Views/            # View generator
    └── ...               # Other packages
```

### Adding New Packages

When creating a new package:

1. Create package directory in `src/Packages/`
2. Implement service provider
3. Add configuration file
4. Create command classes
5. Add tests
6. Update main service provider
7. Document the package

### Package Naming Conventions

- Use PascalCase for class names
- Use snake_case for configuration keys
- Use kebab-case for command names
- Use camelCase for method names

## Reporting Issues

### Bug Reports

Include in bug reports:

- Laravel version
- PHP version
- Package version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages/stack traces
- Environment details

### Feature Requests

Include in feature requests:

- Use case description
- Proposed solution
- Alternative solutions considered
- Additional context

### Security Issues

For security issues, please follow responsible disclosure:

1. Do not open public issues for security vulnerabilities
2. Email security issues to: security@autogen.dev
3. Provide detailed information about the vulnerability
4. Allow time for the issue to be resolved before disclosure

## Development Tips

### Working with Stubs

- Stubs are located in each package's `Stubs/` directory
- Use double curly braces for variable replacement: `{{variable}}`
- Test stub generation with different inputs
- Keep stubs maintainable and well-formatted

### AI Integration

- Test AI features with different providers
- Handle API failures gracefully
- Use appropriate temperature settings for consistency
- Cache AI responses when possible for testing

### Database Introspection

- Support multiple database drivers
- Handle edge cases (empty tables, complex relationships)
- Cache schema information for performance
- Test with different database schemas

## Questions?

If you have questions about contributing:

1. Check existing issues and discussions
2. Review the documentation
3. Ask in GitHub Discussions
4. Contact the maintainers

Thank you for contributing to the Laravel AutoGen Package Suite!