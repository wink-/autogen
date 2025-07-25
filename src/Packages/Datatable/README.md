# AutoGen DataTable Package

A comprehensive, high-performance datatable generator for Laravel applications that supports multiple technologies and large datasets.

## Features

### Core Functionality
- **Multiple Technology Support**: Yajra DataTables, Livewire, Inertia.js, and API-driven tables
- **High Performance**: Optimized for handling millions of rows with server-side processing
- **Advanced Search & Filtering**: Global search, column-specific filters, date ranges
- **Flexible Sorting**: Multi-column sorting with database-level optimization
- **Export Capabilities**: Excel, CSV, PDF exports with background job processing
- **Bulk Operations**: Delete, activate, deactivate multiple records
- **Responsive Design**: Mobile-first approach with adaptive layouts

### Performance Optimizations
- **Server-side Processing**: All operations performed on the server
- **Cursor-based Pagination**: Better performance for large datasets
- **Query Result Caching**: Redis-based caching with intelligent invalidation
- **Virtual Scrolling**: Frontend optimization for large result sets
- **Background Job Processing**: Queue-based exports for large datasets
- **Database Optimizations**: Efficient queries with proper indexing

### Security Features
- **Rate Limiting**: Configurable limits for different endpoint types
- **Input Sanitization**: XSS and SQL injection protection
- **CSRF Protection**: Built-in CSRF token validation
- **Authentication**: Support for various authentication methods

## Installation

The DataTable package is included with AutoGen. Ensure you have the main AutoGen package installed:

```bash
composer require autogen/autogen
```

## Usage

### Basic Command

Generate a datatable for a model:

```bash
php artisan autogen:datatable User
```

### Command Options

```bash
php artisan autogen:datatable User \
    --type=yajra \
    --with-exports \
    --with-search \
    --with-bulk \
    --cache \
    --virtual-scroll \
    --cursor-pagination \
    --background-jobs
```

#### Available Options

| Option | Description | Default |
|--------|-------------|---------|
| `--type` | Datatable type (yajra\|livewire\|inertia\|api) | yajra |
| `--with-exports` | Include Excel, CSV, PDF export functionality | false |
| `--with-search` | Include advanced search and filtering | false |
| `--with-bulk` | Include bulk operations | false |
| `--cache` | Enable Redis caching for performance | false |
| `--virtual-scroll` | Enable virtual scrolling for large datasets | false |
| `--cursor-pagination` | Use cursor-based pagination | false |
| `--background-jobs` | Process exports via background jobs | false |
| `--force` | Overwrite existing files | false |

## Technology-Specific Examples

### 1. Yajra DataTables

```bash
php artisan autogen:datatable User --type=yajra --with-exports --cache
```

**Generated Files:**
- `app/DataTables/UserDataTable.php`
- `resources/views/users/index.blade.php`
- `resources/js/datatables/users.js`

**Controller Integration:**
```php
use App\DataTables\UserDataTable;

public function index(UserDataTable $dataTable)
{
    return $dataTable->render('users.index');
}
```

**Routes:**
```php
Route::get('/users', [UserController::class, 'index'])->name('users.index');
```

### 2. Livewire

```bash
php artisan autogen:datatable User --type=livewire --with-search --with-bulk
```

**Generated Files:**
- `app/Http/Livewire/UserDatatable.php`
- `resources/views/livewire/user-datatable.blade.php`

**Usage in Blade:**
```blade
<livewire:user-datatable />
```

### 3. Inertia.js

```bash
php artisan autogen:datatable User --type=inertia --with-exports --virtual-scroll
```

**Generated Files:**
- `app/Http/Controllers/UserController.php`
- `resources/js/Pages/User/Index.vue`
- `resources/js/Composables/useUsersDatatable.js`

**Routes:**
```php
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
```

### 4. API

```bash
php artisan autogen:datatable User --type=api --with-exports --background-jobs
```

**Generated Files:**
- `app/Http/Controllers/Api/UserController.php`
- `app/Http/Resources/UserResource.php`
- `docs/api/user.md`

**API Endpoints:**
```
GET /api/users              # Paginated list with filters
GET /api/users/{id}         # Single user
GET /api/users/stats        # Aggregated statistics
GET /api/users/export/{format} # Export data
POST /api/users/bulk        # Bulk operations
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=autogen-datatable-config
```

Edit `config/autogen/datatable.php`:

```php
return [
    'default_type' => 'yajra',
    'performance' => [
        'default_page_size' => 15,
        'enable_cache' => true,
        'cache_duration' => 300,
        'cursor_pagination' => false,
        'virtual_scroll' => [
            'enabled' => false,
            'row_height' => 50,
        ],
    ],
    'exports' => [
        'formats' => ['excel', 'csv', 'pdf'],
        'max_direct_export' => 1000,
        'use_background_jobs' => true,
    ],
    'bulk_operations' => [
        'enabled' => true,
        'max_items' => 1000,
        'actions' => [
            'delete' => ['enabled' => true, 'soft_delete' => true],
            'activate' => ['enabled' => true],
            'deactivate' => ['enabled' => true],
        ],
    ],
];
```

## Advanced Usage

### Custom Stubs

Publish and customize stub files:

```bash
php artisan vendor:publish --tag=autogen-datatable-stubs
```

Stub files will be available in `resources/stubs/datatable/`:
- `yajra.datatable.stub`
- `livewire.component.stub`
- `livewire.view.stub`
- `inertia.controller.stub`
- `inertia.vue.stub`
- `api.controller.stub`
- `export.stub`

### Background Job Setup

For background exports, ensure queue processing is configured:

1. **Configure Queue Driver** (`.env`):
```env
QUEUE_CONNECTION=database
```

2. **Run Queue Worker**:
```bash
php artisan queue:work --queue=exports
```

3. **Configure Supervisor** (production):
```ini
[program:autogen-exports]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --queue=exports
directory=/path/to/app
autostart=true
autorestart=true
numprocs=2
```

### Redis Caching Setup

For optimal performance with caching:

1. **Install Redis**:
```bash
sudo apt-get install redis-server
```

2. **Configure Laravel** (`.env`):
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

3. **Cache Tags Configuration**:
```php
// config/autogen/datatable.php
'performance' => [
    'enable_cache' => true,
    'cache_duration' => 300, // 5 minutes
    'cache_tags' => ['datatables', 'users'],
],
```

### Database Optimizations

For better performance, add indexes to commonly filtered columns:

```php
// Migration example
Schema::table('users', function (Blueprint $table) {
    $table->index(['name', 'email']); // Composite index for searches
    $table->index('created_at'); // Date filtering
    $table->index('status'); // Status filtering
});
```

## Troubleshooting

### Common Issues

1. **Memory Exhaustion on Large Exports**
   - Enable background jobs: `--background-jobs`
   - Reduce chunk size in config
   - Increase PHP memory limit

2. **Slow Query Performance**
   - Add database indexes
   - Enable query caching: `--cache`
   - Use cursor pagination: `--cursor-pagination`

3. **Rate Limiting Issues**
   - Adjust rate limits in config
   - Implement proper error handling
   - Use exponential backoff

### Performance Monitoring

Monitor datatable performance:

```php
// Enable query logging
'logging' => [
    'log_queries' => true,
    'slow_query_threshold' => 1000, // 1 second
],
```

## Dependencies

The DataTable package requires these Laravel packages:

- **Yajra DataTables**: `yajra/laravel-datatables-oracle`
- **Livewire**: `livewire/livewire`
- **Inertia.js**: `inertiajs/inertia-laravel`
- **Excel Export**: `maatwebsite/excel`
- **PDF Export**: `barryvdh/laravel-dompdf`

Install based on your chosen type:

```bash
# For Yajra DataTables
composer require yajra/laravel-datatables-oracle

# For Livewire
composer require livewire/livewire

# For Inertia.js
composer require inertiajs/inertia-laravel

# For Exports
composer require maatwebsite/excel barryvdh/laravel-dompdf
```

## Examples

### E-commerce Product Catalog

```bash
php artisan autogen:datatable Product \
    --type=yajra \
    --with-exports \
    --with-search \
    --with-bulk \
    --cache \
    --cursor-pagination
```

### User Management Dashboard

```bash
php artisan autogen:datatable User \
    --type=livewire \
    --with-search \
    --with-bulk \
    --cache
```

### Analytics API

```bash
php artisan autogen:datatable Analytics \
    --type=api \
    --with-exports \
    --background-jobs \
    --cache
```

### Real-time Order Management

```bash
php artisan autogen:datatable Order \
    --type=inertia \
    --with-search \
    --with-bulk \
    --virtual-scroll
```

## Contributing

Contributions are welcome! Please see the main AutoGen package for contribution guidelines.

## License

This package is part of AutoGen and follows the same licensing terms.