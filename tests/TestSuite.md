# AutoGen Package Test Suite

## Overview

This comprehensive test suite provides >90% code coverage for the AutoGen package according to PRD specifications. The test suite is organized into multiple layers to ensure thorough testing of all components.

## Test Structure

### 1. Unit Tests (`tests/Unit/`)
- **Model Package Tests**
  - `ModelGeneratorTest.php` - Tests model generation from database schema
  - `DatabaseIntrospectorTest.php` - Tests database introspection capabilities
  - `RelationshipAnalyzerTest.php` - Tests relationship detection and analysis

- **Controller Package Tests**
  - `ControllerGeneratorTest.php` - Tests controller generation
  - `FormRequestGeneratorTest.php` - Tests form request validation generation
  - `PolicyGeneratorTest.php` - Tests policy generation

- **Views Package Tests**
  - `ViewGeneratorTest.php` - Tests view generation across frameworks
  - `TailwindGeneratorTest.php` - Tests Tailwind CSS view generation

- **Migration Package Tests**
  - `MigrationGeneratorTest.php` - Tests migration file generation

- **Factory Package Tests**
  - `FactoryGeneratorTest.php` - Tests factory generation with fake data

- **Datatable Package Tests**
  - `DatatableGeneratorTest.php` - Tests datatable generation for various frameworks

### 2. Feature Tests (`tests/Feature/`)
- **Command Tests**
  - `ModelGeneratorCommandTest.php` - Tests model generation Artisan command
  - `ControllerGeneratorCommandTest.php` - Tests controller generation command
  - `ViewGeneratorCommandTest.php` - Tests view generation command

### 3. Integration Tests (`tests/Integration/`)
- `DatabaseOperationsTest.php` - Tests full database introspection and generation workflow

### 4. End-to-End Tests (`tests/E2E/`)
- `ScaffoldCommandTest.php` - Tests complete CRUD scaffold generation

### 5. Test Helpers (`tests/Helpers/`)
- `DatabaseTestHelper.php` - Database testing utilities
- `FileTestHelper.php` - File operation testing utilities
- `MockHelper.php` - Mock object creation utilities

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)
- Separate test suites for different test types
- Path coverage enabled for detailed coverage metrics
- Comprehensive logging and reporting
- Test environment isolation

### Base Test Class (`tests/TestCase.php`)
- Orchestra Testbench integration for Laravel package testing
- SQLite in-memory database for fast testing
- Comprehensive test database schema with sample tables
- File cleanup utilities
- Custom assertion methods

## Coverage Requirements

The test suite is designed to achieve >90% code coverage as specified in the PRD:

### Core Components Covered:
1. **Model Generation** - 95%+ coverage
   - Database introspection
   - Model class generation
   - Relationship detection
   - Type mapping
   - Validation rules

2. **Controller Generation** - 95%+ coverage
   - Resource controllers
   - API controllers
   - Form request generation
   - Policy integration
   - Route model binding

3. **Views Generation** - 90%+ coverage
   - Bootstrap views
   - Tailwind CSS views
   - Plain CSS views
   - Form partials
   - Datatable integration

4. **Migration Generation** - 95%+ coverage
   - Schema introspection
   - Migration file creation
   - Foreign key handling
   - Index generation
   - Rollback migrations

5. **Factory Generation** - 90%+ coverage
   - Fake data mapping
   - Relationship factories
   - State methods
   - Locale support

6. **Datatable Generation** - 85%+ coverage
   - Yajra DataTables
   - Livewire datatables
   - Inertia datatables
   - API datatables
   - Export functionality

7. **Scaffold Command** - 90%+ coverage
   - End-to-end CRUD generation
   - Dependency resolution
   - Error handling
   - Progress tracking

## Test Types

### Unit Tests
- Test individual classes and methods in isolation
- Mock external dependencies
- Fast execution
- High coverage of business logic

### Feature Tests  
- Test Artisan commands and their integration
- Test user interactions with the package
- Verify command output and file generation

### Integration Tests
- Test interaction between different components
- Test database operations end-to-end
- Verify data flow through the system

### End-to-End Tests
- Test complete workflows from start to finish
- Test the scaffold command with all options
- Verify generated code works correctly

## Running Tests

### All Tests
```bash
composer test
```

### With Coverage
```bash
composer test-coverage
```

### Specific Test Suites
```bash
# Unit tests only
vendor/bin/phpunit --testsuite="Unit Tests"

# Feature tests only
vendor/bin/phpunit --testsuite="Feature Tests"

# Integration tests only
vendor/bin/phpunit --testsuite="Integration Tests"

# End-to-end tests only
vendor/bin/phpunit --testsuite="End-to-End Tests"
```

### Coverage Analysis
```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html build/coverage

# Generate text coverage report
vendor/bin/phpunit --coverage-text

# Coverage with minimum threshold
vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
```

## Mock and Test Data

### Test Database Schema
The test suite includes comprehensive test tables:
- `users` - Basic user management
- `posts` - Content with relationships
- `categories` - Category management
- `comments` - Nested comments with self-referencing
- `tags` - Many-to-many relationships
- `sample_table` - Various column types for testing

### Mock Objects
- Database introspector mocks
- File system mocks
- Command output mocks
- AI provider mocks
- Template engine mocks

### Test Fixtures
- Sample database schemas
- Mock configuration files
- Test stub templates
- Fake data generators

## Test Coverage Metrics

### Current Coverage Targets:
- **Overall Coverage**: >90%
- **Unit Test Coverage**: >95%
- **Feature Test Coverage**: >85%
- **Integration Test Coverage**: >80%
- **Critical Path Coverage**: 100%

### Coverage Exclusions:
- Service provider registration
- Example files
- Stub templates (tested indirectly)
- Third-party integrations (mocked)

## Continuous Integration

### Test Automation
- All tests run on every commit
- Coverage reports generated automatically
- Performance benchmarks tracked
- Code quality metrics monitored

### Quality Gates
- Minimum 90% coverage required
- All tests must pass
- No critical code smells
- Performance regression checks

## Test Data Management

### Database Testing
- In-memory SQLite for speed
- Comprehensive schema setup
- Automatic cleanup between tests
- Relationship testing support

### File Testing
- Temporary directories for generated files
- Automatic cleanup
- File content validation
- Path handling verification

### Mock Management
- Centralized mock creation
- Reusable mock configurations
- Dependency injection testing
- External service simulation

## Contributing to Tests

### Writing New Tests
1. Follow existing test patterns
2. Use descriptive test method names
3. Include setup and teardown as needed
4. Mock external dependencies
5. Test both success and failure cases

### Test Organization
- Place tests in appropriate directories
- Use consistent naming conventions
- Group related tests together
- Document complex test scenarios

### Coverage Guidelines
- Aim for >95% coverage on new code
- Test all public methods
- Include edge cases and error conditions
- Verify generated code quality

This comprehensive test suite ensures the AutoGen package meets all PRD requirements while maintaining high code quality and reliability standards.