# AutoGen Performance Optimization Guide

This document provides comprehensive information about the performance optimization features available in AutoGen. These optimizations are designed to significantly improve code generation speed, reduce memory usage, and enhance overall system efficiency.

## Table of Contents

1. [Overview](#overview)
2. [Configuration](#configuration)
3. [Schema Caching](#schema-caching)
4. [Template Caching](#template-caching)
5. [Database Query Optimization](#database-query-optimization)
6. [Lazy Loading](#lazy-loading)
7. [Performance Monitoring](#performance-monitoring)
8. [Commands](#commands)
9. [Best Practices](#best-practices)
10. [Troubleshooting](#troubleshooting)

## Overview

AutoGen includes several performance optimization features:

- **Schema Caching**: Intelligent database schema introspection caching
- **Template Compilation Caching**: Pre-compiled template storage and reuse
- **Query Optimization**: Batch processing and connection pooling for database operations
- **Lazy Loading**: Deferred expensive operations until needed
- **Performance Monitoring**: Real-time tracking of generation times and resource usage
- **Benchmarking Tools**: Compare and track performance improvements

## Configuration

All performance features are configured in `config/autogen.php` under the `performance` section:

```php
'performance' => [
    'monitoring' => [
        'enabled' => env('AUTOGEN_PERFORMANCE_MONITORING', true),
        'track_generation_time' => true,
        'track_memory_usage' => true,
        'track_query_count' => true,
        'slow_query_threshold' => 1.0, // seconds
        'memory_usage_threshold' => 128 * 1024 * 1024, // 128MB
    ],
    'profiling' => [
        'enabled' => env('AUTOGEN_PROFILING_ENABLED', false),
        'sample_rate' => 0.1, // Profile 10% of operations
        'store_traces' => true,
        'max_trace_size' => 1000,
    ],
    'benchmarking' => [
        'enabled' => env('AUTOGEN_BENCHMARKING_ENABLED', false),
        'iterations' => 10,
        'warmup_iterations' => 3,
        'store_results' => true,
        'compare_baseline' => true,
    ],
],
```

### Database Introspection Configuration

```php
'database' => [
    'introspection' => [
        'cache_schema' => true,
        'cache_ttl' => 3600, // 1 hour
        'batch_size' => 50,
        'lazy_loading' => true,
        'use_connection_pooling' => true,
        'query_timeout' => 30, // seconds
    ],
],
```

### Template Caching Configuration

```php
'templates' => [
    'caching' => [
        'enabled' => true,
        'ttl' => 7200, // 2 hours
        'compile_cache' => true,
        'precompile_stubs' => true,
        'lazy_compilation' => true,
    ],
    'optimization' => [
        'reduce_file_operations' => true,
        'batch_template_loading' => true,
        'use_memory_cache' => true,
        'compress_cached_templates' => true,
    ],
],
```

## Schema Caching

The schema cache system intelligently caches database introspection results to avoid repeated expensive database queries.

### Features

- **Intelligent Invalidation**: Automatically detects schema changes
- **Multi-Connection Support**: Separate caches for different database connections
- **Compression**: Optional data compression to reduce memory usage
- **Metadata Tracking**: Tracks cache hit ratios and usage statistics

### Usage

```php
use AutoGen\Common\Performance\SchemaCache;

$schemaCache = app(SchemaCache::class);

// Get table schema (cached automatically)
$schema = $schemaCache->getTableSchema('mysql', 'users');

// Get all tables for a connection
$schemas = $schemaCache->getAllTablesSchema('mysql');

// Get relationships
$relationships = $schemaCache->getRelationships('mysql');

// Invalidate cache for specific table
$schemaCache->invalidateTable('mysql', 'users');

// Warm cache for connection
$schemaCache->warmCache('mysql', ['users', 'posts']);
```

### Cache Statistics

```php
$stats = $schemaCache->getCacheStats();
// Returns:
// [
//     'memory_cache_size' => 150,
//     'connections' => [
//         'mysql' => [
//             'tables_cached' => 25,
//             'last_updated' => '2023-...',
//             'cache_hits' => 1250,
//             'cache_misses' => 45,
//         ]
//     ]
// ]
```

## Template Caching

The template system includes sophisticated caching and precompilation features.

### Features

- **Precompilation**: Templates are compiled and cached at startup
- **Lazy Compilation**: Templates are only compiled when needed
- **Variable Substitution Caching**: Different variable combinations are cached separately
- **File-based Persistence**: Caches survive application restarts

### Usage

```php
use AutoGen\Common\Performance\TemplateCache;

$templateCache = app(TemplateCache::class);

// Get compiled template (cached automatically)
$compiled = $templateCache->getCompiledTemplate($stubPath, [
    'model' => 'User',
    'namespace' => 'App\\Models'
]);

// Precompile all stubs
$stubPaths = [...]; // Array of stub file paths
$compiled = $templateCache->precompileStubs($stubPaths);

// Warm cache with common patterns
$patterns = [
    'model' => ['model' => 'User', 'namespace' => 'App\\Models'],
    'controller' => ['model' => 'User', 'namespace' => 'App\\Http\\Controllers'],
];
$warmed = $templateCache->warmCache($patterns);
```

## Database Query Optimization

Query optimization includes batch processing, connection pooling, and intelligent query planning.

### Features

- **Batch Processing**: Process multiple tables in batches
- **Connection Pooling**: Reuse database connections
- **Query Timeout Management**: Prevent hanging queries
- **Driver-Specific Optimizations**: Optimized queries for MySQL, PostgreSQL, SQLite

### Usage

```php
use AutoGen\Common\Performance\QueryOptimizer;

$optimizer = app(QueryOptimizer::class);

// Batch execute queries
$queries = ['SELECT * FROM users', 'SELECT * FROM posts'];
$results = $optimizer->batchExecute('mysql', $queries, 50);

// Get optimized table info
$tables = ['users', 'posts', 'comments'];
$info = $optimizer->getOptimizedTableInfo('mysql', $tables);

// Get optimized relationships
$relationships = $optimizer->getOptimizedRelationships('mysql');
```

## Lazy Loading

Lazy loading defers expensive operations until they're actually needed.

### Features

- **Memory Management**: Automatic cleanup when memory limits are reached
- **Dependency Tracking**: Invalidate related lazy values when dependencies change
- **Memoization**: Cache function results for repeated calls
- **Statistics Tracking**: Monitor lazy loading effectiveness

### Usage

```php
use AutoGen\Common\Performance\LazyLoader;

$lazyLoader = app(LazyLoader::class);

// Create a lazy value
$lazyValue = $lazyLoader->lazy('expensive_operation', function () {
    // Expensive computation here
    return performExpensiveOperation();
}, ['dependency1', 'dependency2']);

// Get the value (computed on first access)
$result = $lazyValue->getValue();

// Preload multiple values
$results = $lazyLoader->preload(['key1', 'key2', 'key3']);

// Invalidate by dependency
$invalidated = $lazyLoader->invalidateByDependency('dependency1');

// Create memoized function
$memoizedFunction = $lazyLoader->memoize(function ($a, $b) {
    return expensiveCalculation($a, $b);
});
```

## Performance Monitoring

Comprehensive performance monitoring tracks generation times, memory usage, and query counts.

### Features

- **Real-time Monitoring**: Track operations as they happen
- **Statistical Analysis**: Aggregated statistics with averages, minimums, maximums
- **Slow Operation Detection**: Automatically log operations exceeding thresholds
- **Memory Usage Tracking**: Monitor memory consumption patterns

### Usage

```php
use AutoGen\Common\Performance\PerformanceMonitor;

$monitor = app(PerformanceMonitor::class);

// Monitor an operation
$operationId = $monitor->startOperation('model_generation', [
    'model' => 'User',
    'connection' => 'mysql'
]);

// ... perform operation ...

$metrics = $monitor->endOperation($operationId);
// Returns:
// [
//     'operation' => 'model_generation',
//     'execution_time' => 0.245,
//     'memory_usage' => 1048576,
//     'query_count' => 3,
//     'context' => ['model' => 'User', 'connection' => 'mysql']
// ]

// Get aggregated statistics
$stats = $monitor->getStatistics();
$modelStats = $monitor->getStatistics('model_generation');
```

## Commands

Several Artisan commands are available for performance management:

### Benchmarking

```bash
# Run performance benchmarks
php artisan autogen:benchmark

# Benchmark specific operations
php artisan autogen:benchmark --operation=schema_introspection --operation=template_compilation

# Run with custom settings
php artisan autogen:benchmark --iterations=20 --warmup=5 --output=json

# Compare with baseline
php artisan autogen:benchmark --baseline=storage/app/autogen/benchmarks/baseline.json

# Save results as new baseline
php artisan autogen:benchmark --save-baseline
```

### Cache Management

```bash
# Clear all AutoGen caches
php artisan autogen:cache:clear

# Clear specific cache types
php artisan autogen:cache:clear --type=schema --type=templates

# Clear cache for specific connections
php artisan autogen:cache:clear --type=schema --connection=mysql

# Warm up caches
php artisan autogen:cache:warm

# Warm specific cache types
php artisan autogen:cache:warm --type=templates --type=schema
```

### Optimization

```bash
# Run comprehensive optimization
php artisan autogen:optimize

# Clear caches and run optimization
php artisan autogen:optimize --clear-cache --warm-cache

# Analyze current performance
php artisan autogen:optimize --analyze

# Get optimization suggestions
php artisan autogen:optimize --suggestions
```

## Best Practices

### 1. Enable Appropriate Caching

- Enable schema caching for production environments
- Use template precompilation for frequently generated code
- Configure appropriate TTL values based on your schema change frequency

### 2. Monitor Performance

- Enable performance monitoring in development
- Set appropriate thresholds for slow operations
- Regularly review performance statistics

### 3. Optimize Database Connections

- Use connection pooling for high-volume generation
- Configure appropriate batch sizes for your dataset
- Set reasonable query timeouts

### 4. Use Lazy Loading Strategically

- Lazy load expensive operations that may not be needed
- Set appropriate memory limits to prevent excessive usage
- Use dependency tracking for related operations

### 5. Regular Benchmarking

- Establish performance baselines
- Run benchmarks after significant changes
- Compare performance across different environments

## Troubleshooting

### Common Issues

#### Cache Not Working

```bash
# Check cache configuration
php artisan config:show cache

# Clear Laravel's config cache
php artisan config:clear

# Check AutoGen cache status
php artisan autogen:optimize --analyze
```

#### High Memory Usage

```php
// Adjust memory limits in config/autogen.php
'performance' => [
    'memory_usage_threshold' => 64 * 1024 * 1024, // 64MB
],

'templates' => [
    'caching' => [
        'compress_cached_templates' => true,
    ],
],
```

#### Slow Database Operations

```php
// Enable query optimization
'database' => [
    'introspection' => [
        'batch_size' => 25, // Smaller batches
        'use_connection_pooling' => true,
        'query_timeout' => 15, // Shorter timeout
    ],
],
```

#### Performance Monitoring Overhead

```php
// Reduce monitoring in production
'performance' => [
    'monitoring' => [
        'enabled' => env('APP_ENV') !== 'production',
        'track_query_count' => false, // Disable expensive tracking
    ],
],
```

### Debug Mode

Enable debug logging to troubleshoot performance issues:

```php
// In config/autogen.php
'logging' => [
    'enabled' => true,
    'level' => 'debug',
    'log_generation_time' => true,
    'log_file_changes' => true,
],
```

### Performance Analysis

Use the built-in analysis tools:

```bash
# Analyze current performance
php artisan autogen:optimize --analyze

# Get detailed suggestions
php artisan autogen:optimize --suggestions

# Run comprehensive benchmark
php artisan autogen:benchmark --iterations=50 --output=json > performance_report.json
```

## Environment-Specific Recommendations

### Development

- Enable all monitoring and profiling features
- Use aggressive caching to speed up development
- Enable debug logging for troubleshooting

### Testing

- Disable caching to ensure fresh data
- Enable performance monitoring for test optimization
- Use smaller batch sizes for faster test execution

### Production

- Enable all caching features with appropriate TTLs
- Disable detailed profiling to reduce overhead
- Monitor performance metrics for optimization opportunities
- Use connection pooling and batch processing for high volume

## Performance Metrics

Expected performance improvements with optimizations enabled:

- **Schema Introspection**: 70-90% reduction in database query time
- **Template Compilation**: 60-80% reduction in file I/O operations
- **Memory Usage**: 30-50% reduction through lazy loading and caching
- **Overall Generation Time**: 40-60% improvement for typical operations

Results may vary based on:
- Database size and complexity
- Template complexity and quantity
- Available system resources
- Cache configuration and effectiveness