<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ApiDatatableGenerator
{
    protected string $modelName;
    protected array $options;
    protected array $generatedFiles = [];

    public function __construct(string $modelName, array $options = [])
    {
        $this->modelName = $modelName;
        $this->options = $options;
    }

    /**
     * Generate API DataTable files.
     */
    public function generate(): array
    {
        $this->generateApiController();
        $this->generateApiResource();
        $this->generateApiDocumentation();
        
        return $this->generatedFiles;
    }

    /**
     * Generate the API controller.
     */
    protected function generateApiController(): void
    {
        $path = $this->getApiControllerPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getControllerStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("API controller already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Generate the API resource.
     */
    protected function generateApiResource(): void
    {
        $path = $this->getApiResourcePath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getResourceStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("API resource already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Generate API documentation.
     */
    protected function generateApiDocumentation(): void
    {
        $path = $this->getApiDocumentationPath();
        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getDocumentationStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($path) && !($this->options['force'] ?? false)) {
            throw new \Exception("API documentation already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Get the controller stub file.
     */
    protected function getControllerStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/api.controller.stub')) {
            return File::get($customStubPath . '/datatable/api.controller.stub');
        }

        return File::get(__DIR__ . '/Stubs/api.controller.stub');
    }

    /**
     * Get the resource stub file.
     */
    protected function getResourceStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/api.resource.stub')) {
            return File::get($customStubPath . '/datatable/api.resource.stub');
        }

        return File::get(__DIR__ . '/Stubs/api.resource.stub');
    }

    /**
     * Get the documentation stub file.
     */
    protected function getDocumentationStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        
        if ($customStubPath && File::exists($customStubPath . '/datatable/api.documentation.stub')) {
            return File::get($customStubPath . '/datatable/api.documentation.stub');
        }

        return File::get(__DIR__ . '/Stubs/api.documentation.stub');
    }

    /**
     * Replace variables in the stub.
     */
    protected function replaceStubVariables(string $stub): string
    {
        $replacements = $this->getReplacements();

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Get all replacement variables.
     */
    protected function getReplacements(): array
    {
        $modelClass = $this->getModelClass();
        $modelNamespace = $this->getModelNamespace();
        $controllerNamespace = $this->getControllerNamespace();
        $resourceNamespace = $this->getResourceNamespace();
        $controllerName = $this->getControllerName();
        $resourceName = $this->getResourceName();
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);

        $replacements = [
            'namespace' => $controllerNamespace,
            'modelNamespace' => $modelNamespace,
            'resourceNamespace' => $resourceNamespace,
            'modelClass' => $modelClass,
            'controllerName' => $controllerName,
            'resourceName' => $resourceName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'apiMethods' => $this->getApiMethods(),
            'queryOptimizations' => $this->getQueryOptimizations(),
            'cacheImplementation' => $this->getCacheImplementation(),
            'rateLimit' => $this->getRateLimit(),
            'authentication' => $this->getAuthentication(),
            'validation' => $this->getValidation(),
            'exportMethods' => $this->getExportMethods(),
            'bulkMethods' => $this->getBulkMethods(),
            'resourceFields' => $this->getResourceFields(),
            'apiDocumentation' => $this->getApiDocumentation(),
        ];

        return $replacements;
    }

    /**
     * Get API methods implementation.
     */
    protected function getApiMethods(): string
    {
        $modelClass = $this->getModelClass();
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);
        $resourceName = $this->getResourceName();

        return "
    /**
     * Get paginated data with filters.
     * 
     * @param Request \$request
     * @return JsonResponse
     */
    public function index(Request \$request): JsonResponse
    {
        \$validator = Validator::make(\$request->all(), [
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:id,name,email,created_at,updated_at',
            'direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            " . ($this->options['withSearch'] ? "
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'," : "") . "
        ]);

        if (\$validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => \$validator->errors()
            ], 422);
        }

        try {
            \$query = {$modelClass}::query();

            {$this->getSearchImplementation()}

            {$this->getSortingImplementation()}

            {$this->getQueryOptimizations()}

            {$this->getCacheQuery()}

            \$perPage = \$request->get('per_page', 15);
            \${$modelVariablePlural} = \$query->paginate(\$perPage);

            return response()->json([
                'data' => {$resourceName}::collection(\${$modelVariablePlural}->items()),
                'meta' => [
                    'current_page' => \${$modelVariablePlural}->currentPage(),
                    'last_page' => \${$modelVariablePlural}->lastPage(),
                    'per_page' => \${$modelVariablePlural}->perPage(),
                    'total' => \${$modelVariablePlural}->total(),
                    'from' => \${$modelVariablePlural}->firstItem(),
                    'to' => \${$modelVariablePlural}->lastItem(),
                ],
                'links' => [
                    'first' => \${$modelVariablePlural}->url(1),
                    'last' => \${$modelVariablePlural}->url(\${$modelVariablePlural}->lastPage()),
                    'prev' => \${$modelVariablePlural}->previousPageUrl(),
                    'next' => \${$modelVariablePlural}->nextPageUrl(),
                ]
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'message' => 'Failed to retrieve data',
                'error' => config('app.debug') ? \$e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a single record.
     * 
     * @param int \$id
     * @return JsonResponse
     */
    public function show(int \$id): JsonResponse
    {
        try {
            \${$modelVariable} = {$modelClass}::findOrFail(\$id);
            
            return response()->json([
                'data' => new {$resourceName}(\${$modelVariable})
            ]);
        } catch (ModelNotFoundException \$e) {
            return response()->json([
                'message' => '{$modelClass} not found'
            ], 404);
        }
    }

    /**
     * Get aggregated statistics.
     * 
     * @param Request \$request
     * @return JsonResponse
     */
    public function stats(Request \$request): JsonResponse
    {
        try {
            \$query = {$modelClass}::query();

            {$this->getSearchImplementation()}

            {$this->getCacheStatsQuery()}

            \$stats = [
                'total' => \$query->count(),
                'active' => \$query->where('status', 'active')->count(),
                'inactive' => \$query->where('status', 'inactive')->count(),
                'created_today' => \$query->whereDate('created_at', today())->count(),
                'created_this_week' => \$query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'created_this_month' => \$query->whereMonth('created_at', now()->month)->count(),
            ];

            return response()->json([
                'data' => \$stats
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'message' => 'Failed to retrieve statistics',
                'error' => config('app.debug') ? \$e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    {$this->getExportMethodsImplementation()}

    {$this->getBulkMethodsImplementation()}";
    }

    /**
     * Get search implementation.
     */
    protected function getSearchImplementation(): string
    {
        $implementation = "
            // Global search
            if (\$request->filled('search')) {
                \$search = \$request->get('search');
                \$query->where(function (\$q) use (\$search) {
                    \$q->where('name', 'like', \"%{\$search}%\")
                      ->orWhere('email', 'like', \"%{\$search}%\");
                });
            }";

        if ($this->options['withSearch'] ?? false) {
            $implementation .= "

            // Advanced search filters
            if (\$request->filled('name')) {
                \$query->where('name', 'like', '%' . \$request->get('name') . '%');
            }

            if (\$request->filled('email')) {
                \$query->where('email', 'like', '%' . \$request->get('email') . '%');
            }

            if (\$request->filled('date_from')) {
                \$query->whereDate('created_at', '>=', \$request->get('date_from'));
            }

            if (\$request->filled('date_to')) {
                \$query->whereDate('created_at', '<=', \$request->get('date_to'));
            }";
        }

        return $implementation;
    }

    /**
     * Get sorting implementation.
     */
    protected function getSortingImplementation(): string
    {
        return "
            // Sorting
            \$sortField = \$request->get('sort', 'id');
            \$sortDirection = \$request->get('direction', 'asc');
            
            \$allowedSorts = ['id', 'name', 'email', 'created_at', 'updated_at'];
            if (in_array(\$sortField, \$allowedSorts)) {
                \$query->orderBy(\$sortField, \$sortDirection);
            }";
    }

    /**
     * Get query optimizations.
     */
    protected function getQueryOptimizations(): string
    {
        $optimizations = [];

        // Select specific columns
        $optimizations[] = "\$query->select(['id', 'name', 'email', 'created_at', 'updated_at', 'status']);";

        // Cursor pagination if enabled
        if ($this->options['cursorPagination'] ?? false) {
            $optimizations[] = "
            // Use cursor pagination for better performance on large datasets
            if (\$request->get('use_cursor', false) && \$sortField === 'id') {
                return \$query->cursorPaginate(\$perPage);
            }";
        }

        return $optimizations ? '            ' . implode("\n            ", $optimizations) : '';
    }

    /**
     * Get cache query implementation.
     */
    protected function getCacheQuery(): string
    {
        if (!($this->options['cache'] ?? false)) {
            return '';
        }

        return "
            // Cache implementation
            \$cacheKey = 'api-datatable:' . md5(serialize(\$request->all()));
            \$cacheDuration = config('datatables.cache.duration', 300);
            
            return Cache::remember(\$cacheKey, \$cacheDuration, function () use (\$query, \$perPage) {
                return \$query->paginate(\$perPage);
            });";
    }

    /**
     * Get cache stats query implementation.
     */
    protected function getCacheStatsQuery(): string
    {
        if (!($this->options['cache'] ?? false)) {
            return '';
        }

        return "
            \$cacheKey = 'api-datatable-stats:' . md5(serialize(\$request->all()));
            \$cacheDuration = config('datatables.cache.duration', 300);
            
            return Cache::remember(\$cacheKey, \$cacheDuration, function () use (\$query) {";
    }

    /**
     * Get rate limiting configuration.
     */
    protected function getRateLimit(): string
    {
        return "
    /**
     * Configure rate limiting for API endpoints.
     */
    public function __construct()
    {
        \$this->middleware('throttle:api');
        
        // Higher rate limits for authenticated users
        \$this->middleware('throttle:60,1')->only(['index', 'show', 'stats']);
        \$this->middleware('throttle:30,1')->only(['export', 'bulk']);
    }";
    }

    /**
     * Get authentication configuration.
     */
    protected function getAuthentication(): string
    {
        return "
        // Apply authentication middleware
        \$this->middleware('auth:sanctum');
        
        // Optional: Apply specific permissions
        // \$this->middleware('can:view,{$this->getModelClass()}')->only(['index', 'show']);
        // \$this->middleware('can:export,{$this->getModelClass()}')->only(['export']);
        // \$this->middleware('can:bulk-action,{$this->getModelClass()}')->only(['bulk']);";
    }

    /**
     * Get validation rules.
     */
    protected function getValidation(): string
    {
        return [
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:id,name,email,created_at,updated_at',
            'direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get export methods implementation.
     */
    protected function getExportMethodsImplementation(): string
    {
        if (!($this->options['withExports'] ?? false)) {
            return '';
        }

        $modelClass = $this->getModelClass();

        return "
    /**
     * Export data to specified format.
     * 
     * @param Request \$request
     * @param string \$format
     * @return JsonResponse|mixed
     */
    public function export(Request \$request, string \$format)
    {
        \$validator = Validator::make(array_merge(\$request->all(), ['format' => \$format]), [
            'format' => 'required|string|in:excel,csv,pdf',
            'search' => 'nullable|string|max:255',
            " . ($this->options['withSearch'] ? "
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'," : "") . "
        ]);

        if (\$validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => \$validator->errors()
            ], 422);
        }

        try {
            \$query = {$modelClass}::query();
            
            {$this->getSearchImplementation()}
            
            \$data = \$query->get();
            
            " . ($this->options['backgroundJobs'] ? "
            // Process export in background job for large datasets
            if (\$data->count() > 1000) {
                \$job = dispatch(new Export{$modelClass}Job(\$data, \$format, auth()->user()));
                
                return response()->json([
                    'message' => 'Export job started. You will receive an email when ready.',
                    'job_id' => \$job->getJobId(),
                    'estimated_time' => ceil(\$data->count() / 1000) * 60 // seconds
                ]);
            }" : "") . "

            \$export = new {$modelClass}Export(\$data);
            
            switch (\$format) {
                case 'excel':
                    return \$export->download('{$this->getModelVariable()}s.xlsx');
                case 'csv':
                    return \$export->download('{$this->getModelVariable()}s.csv');
                case 'pdf':
                    return \$export->download('{$this->getModelVariable()}s.pdf');
            }
        } catch (\Exception \$e) {
            return response()->json([
                'message' => 'Export failed',
                'error' => config('app.debug') ? \$e->getMessage() : 'Internal server error'
            ], 500);
        }
    }";
    }

    /**
     * Get bulk methods implementation.
     */
    protected function getBulkMethodsImplementation(): string
    {
        if (!($this->options['withBulk'] ?? false)) {
            return '';
        }

        $modelClass = $this->getModelClass();

        return "
    /**
     * Handle bulk actions.
     * 
     * @param Request \$request
     * @return JsonResponse
     */
    public function bulk(Request \$request): JsonResponse
    {
        \$validator = Validator::make(\$request->all(), [
            'action' => 'required|string|in:delete,activate,deactivate,export',
            'ids' => 'required|array|min:1|max:1000',
            'ids.*' => 'required|integer|exists:{$this->getTableName()},id'
        ]);

        if (\$validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => \$validator->errors()
            ], 422);
        }

        try {
            \$action = \$request->get('action');
            \$ids = \$request->get('ids');
            \$affectedRows = 0;

            switch (\$action) {
                case 'delete':
                    \$affectedRows = {$modelClass}::whereIn('id', \$ids)->delete();
                    \$message = \"{\$affectedRows} items deleted successfully\";
                    break;
                    
                case 'activate':
                    \$affectedRows = {$modelClass}::whereIn('id', \$ids)->update(['status' => 'active']);
                    \$message = \"{\$affectedRows} items activated successfully\";
                    break;
                    
                case 'deactivate':
                    \$affectedRows = {$modelClass}::whereIn('id', \$ids)->update(['status' => 'inactive']);
                    \$message = \"{\$affectedRows} items deactivated successfully\";
                    break;
                    
                case 'export':
                    \$data = {$modelClass}::whereIn('id', \$ids)->get();
                    return (new {$modelClass}Export(\$data))->download('selected_{$this->getModelVariable()}s.xlsx');
                    
                default:
                    return response()->json(['message' => 'Invalid action'], 400);
            }

            {$this->getClearCacheCall()}

            return response()->json([
                'message' => \$message,
                'affected_rows' => \$affectedRows
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'message' => 'Bulk action failed',
                'error' => config('app.debug') ? \$e->getMessage() : 'Internal server error'
            ], 500);
        }
    }";
    }

    /**
     * Get resource fields definition.
     */
    protected function getResourceFields(): string
    {
        return "
        return [
            'id' => \$this->id,
            'name' => \$this->name,
            'email' => \$this->email,
            'status' => \$this->status,
            'created_at' => \$this->created_at?->toISOString(),
            'updated_at' => \$this->updated_at?->toISOString(),
            'formatted_created_at' => \$this->created_at?->format('Y-m-d H:i:s'),
            'formatted_updated_at' => \$this->updated_at?->format('Y-m-d H:i:s'),
            'links' => [
                'self' => route('{$this->getRouteName()}.show', \$this->id),
                'edit' => route('{$this->getRouteName()}.edit', \$this->id),
                'delete' => route('{$this->getRouteName()}.destroy', \$this->id),
            ]
        ];";
    }

    /**
     * Get API documentation content.
     */
    protected function getApiDocumentation(): string
    {
        $modelClass = $this->getModelClass();
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);
        $routeName = $this->getRouteName();

        return "# {$modelClass} API Documentation

## Overview
This API provides high-performance datatable functionality for {$modelClass} resources with advanced filtering, sorting, pagination, and export capabilities.

## Base URL
`/api/{$modelVariablePlural}`

## Authentication
All endpoints require authentication using Laravel Sanctum tokens.

```
Authorization: Bearer YOUR_API_TOKEN
```

## Rate Limiting
- General endpoints: 60 requests per minute
- Export/Bulk endpoints: 30 requests per minute

## Endpoints

### GET /{$modelVariablePlural}
Get paginated list of {$modelVariablePlural} with filtering and sorting.

**Parameters:**
- `search` (string, optional): Global search term
- `sort` (string, optional): Sort field (id|name|email|created_at|updated_at)
- `direction` (string, optional): Sort direction (asc|desc)
- `per_page` (integer, optional): Items per page (1-100, default: 15)
- `page` (integer, optional): Page number" .

($this->options['withSearch'] ? "
- `name` (string, optional): Filter by name
- `email` (string, optional): Filter by email
- `date_from` (date, optional): Filter by creation date from
- `date_to` (date, optional): Filter by creation date to" : "") . "

**Response:**
```json
{
  \"data\": [
    {
      \"id\": 1,
      \"name\": \"John Doe\",
      \"email\": \"john@example.com\",
      \"status\": \"active\",
      \"created_at\": \"2023-01-01T00:00:00.000000Z\",
      \"updated_at\": \"2023-01-01T00:00:00.000000Z\",
      \"links\": {
        \"self\": \"/api/{$modelVariablePlural}/1\",
        \"edit\": \"/api/{$modelVariablePlural}/1/edit\",
        \"delete\": \"/api/{$modelVariablePlural}/1\"
      }
    }
  ],
  \"meta\": {
    \"current_page\": 1,
    \"last_page\": 10,
    \"per_page\": 15,
    \"total\": 150,
    \"from\": 1,
    \"to\": 15
  },
  \"links\": {
    \"first\": \"/api/{$modelVariablePlural}?page=1\",
    \"last\": \"/api/{$modelVariablePlural}?page=10\",
    \"prev\": null,
    \"next\": \"/api/{$modelVariablePlural}?page=2\"
  }
}
```

### GET /{$modelVariablePlural}/{id}
Get a single {$modelVariable} by ID.

**Response:**
```json
{
  \"data\": {
    \"id\": 1,
    \"name\": \"John Doe\",
    \"email\": \"john@example.com\",
    \"status\": \"active\",
    \"created_at\": \"2023-01-01T00:00:00.000000Z\",
    \"updated_at\": \"2023-01-01T00:00:00.000000Z\"
  }
}
```

### GET /{$modelVariablePlural}/stats
Get aggregated statistics.

**Response:**
```json
{
  \"data\": {
    \"total\": 1000,
    \"active\": 800,
    \"inactive\": 200,
    \"created_today\": 5,
    \"created_this_week\": 25,
    \"created_this_month\": 100
  }
}
```" .

($this->options['withExports'] ? "

### GET /{$modelVariablePlural}/export/{format}
Export data in specified format (excel|csv|pdf).

**Parameters:**
- `format` (string, required): Export format
- All filtering parameters from the main endpoint

**Response:**
- File download for small datasets
- JSON response with job information for large datasets

```json
{
  \"message\": \"Export job started. You will receive an email when ready.\",
  \"job_id\": \"12345\",
  \"estimated_time\": 180
}
```" : "") .

($this->options['withBulk'] ? "

### POST /{$modelVariablePlural}/bulk
Perform bulk actions on multiple {$modelVariablePlural}.

**Parameters:**
```json
{
  \"action\": \"delete|activate|deactivate|export\",
  \"ids\": [1, 2, 3, 4, 5]
}
```

**Response:**
```json
{
  \"message\": \"5 items deleted successfully\",
  \"affected_rows\": 5
}
```" : "") . "

## Error Responses

### 422 Validation Error
```json
{
  \"message\": \"Validation failed\",
  \"errors\": {
    \"per_page\": [\"The per page must be between 1 and 100.\"]
  }
}
```

### 404 Not Found
```json
{
  \"message\": \"{$modelClass} not found\"
}
```

### 500 Server Error
```json
{
  \"message\": \"Internal server error\"
}
```

## Performance Features

- **Server-side processing**: All filtering and sorting performed on the server
- **Query optimization**: Efficient database queries with proper indexing" .
($this->options['cache'] ? "
- **Redis caching**: Query results cached for improved performance" : "") .
($this->options['cursorPagination'] ? "
- **Cursor pagination**: Available for large datasets with better performance" : "") .
($this->options['virtualScroll'] ? "
- **Virtual scrolling**: Frontend optimization for large result sets" : "") . "
- **Rate limiting**: Prevents API abuse and ensures fair usage

## Best Practices

1. Use appropriate `per_page` values (recommended: 15-50)
2. Implement client-side caching for repeated requests
3. Use cursor pagination for large datasets when sorting by ID
4. Combine multiple filters to reduce result set size
5. Monitor rate limits and implement proper error handling";
    }

    /**
     * Get clear cache method call.
     */
    protected function getClearCacheCall(): string
    {
        return ($this->options['cache'] ?? false) ? 'Cache::tags([\'api-datatable\'])->flush();' : '';
    }

    /**
     * Get table name for validation.
     */
    protected function getTableName(): string
    {
        return Str::snake(Str::plural($this->getModelClass()));
    }

    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return class_basename($this->modelName);
    }

    /**
     * Get the model namespace.
     */
    protected function getModelNamespace(): string
    {
        $modelPath = str_replace('/', '\\', $this->modelName);
        $namespace = 'App\\Models';

        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the controller namespace.
     */
    protected function getControllerNamespace(): string
    {
        $namespace = 'App\\Http\\Controllers\\Api';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the resource namespace.
     */
    protected function getResourceNamespace(): string
    {
        $namespace = 'App\\Http\\Resources';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the controller name.
     */
    protected function getControllerName(): string
    {
        return $this->getModelClass() . 'Controller';
    }

    /**
     * Get the resource name.
     */
    protected function getResourceName(): string
    {
        return $this->getModelClass() . 'Resource';
    }

    /**
     * Get the model variable name.
     */
    protected function getModelVariable(): string
    {
        return Str::camel($this->getModelClass());
    }

    /**
     * Get the route name prefix.
     */
    protected function getRouteName(): string
    {
        $parts = explode('/', $this->modelName);
        $names = array_map(fn($part) => Str::kebab($part), $parts);
        return implode('.', $names);
    }

    /**
     * Get the API controller file path.
     */
    protected function getApiControllerPath(): string
    {
        $path = app_path('Http/Controllers/Api');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getControllerName() . '.php';
    }

    /**
     * Get the API resource file path.
     */
    protected function getApiResourcePath(): string
    {
        $path = app_path('Http/Resources');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getResourceName() . '.php';
    }

    /**
     * Get the API documentation file path.
     */
    protected function getApiDocumentationPath(): string
    {
        $parts = explode('/', $this->modelName);
        $paths = array_map(fn($part) => Str::kebab($part), $parts);
        
        $docPath = base_path('docs/api/' . implode('/', $paths));
        
        return $docPath . '.md';
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}