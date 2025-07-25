# AutoGen Controller Package

The AutoGen Controller package generates Laravel controllers with comprehensive CRUD operations, form request validation, and policy authorization for existing models.

## Features

- **Smart CRUD Controllers**: Generate resource or API controllers with full CRUD operations
- **Form Request Validation**: Auto-generate validation classes with rules inferred from database schema
- **Policy Authorization**: Generate policy classes with standard authorization methods
- **Transaction Handling**: Built-in database transaction support for data integrity
- **File Upload Support**: Automatic handling of file uploads and storage
- **Search & Filtering**: Built-in search, filtering, and sorting capabilities
- **Bulk Operations**: Support for bulk actions (delete, activate, deactivate)
- **Error Handling**: Comprehensive error handling with user-friendly messages
- **API & Web Support**: Generate controllers for both web and API routes

## Installation

Install via Composer:

```bash
composer require autogen/laravel-autogen
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=autogen-config
```

## Usage

### Basic Usage

Generate a resource controller for a User model:

```bash
php artisan autogen:controller User
```

### Command Options

```bash
php artisan autogen:controller <model> [options]
```

**Arguments:**
- `model` - The model name (e.g., User or Admin/User)

**Options:**
- `--api` - Generate an API controller
- `--resource` - Generate a resource controller (default)
- `--with-validation` - Generate form request validation classes
- `--with-policy` - Generate a policy class
- `--paginate=N` - Number of items per page (default: 15)
- `--force` - Overwrite existing files

### Examples

#### Generate API Controller with Validation

```bash
php artisan autogen:controller User --api --with-validation
```

This generates:
- `app/Http/Controllers/Api/UserController.php`
- `app/Http/Requests/StoreUserRequest.php`
- `app/Http/Requests/UpdateUserRequest.php`

#### Generate Resource Controller with Policy

```bash
php artisan autogen:controller Admin/Product --resource --with-policy --paginate=25
```

This generates:
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Policies/Admin/ProductPolicy.php`

#### Generate Complete Controller Suite

```bash
php artisan autogen:controller User --with-validation --with-policy --force
```

This generates:
- Controller with full CRUD operations
- Store and Update form request classes
- Policy class with authorization methods

## Generated Controller Features

### Resource Controller (Web)

Generated resource controllers include:

- **Index Method**: Paginated listing with search, filters, and sorting
- **Create Method**: Display creation form
- **Store Method**: Handle form submission with validation
- **Show Method**: Display single resource
- **Edit Method**: Display edit form
- **Update Method**: Handle update with validation
- **Destroy Method**: Delete resource with confirmation
- **Bulk Actions**: Handle multiple record operations

### API Controller

Generated API controllers include:

- **Index Method**: Paginated JSON listing with search/filter
- **Store Method**: Create resource, return JSON response
- **Show Method**: Return single resource as JSON
- **Update Method**: Update resource, return JSON response
- **Destroy Method**: Delete resource, return JSON response
- **Bulk Actions**: Handle bulk operations via API
- **Stats Method**: Resource statistics endpoint

### Form Request Validation

Generated form requests include:

- **Smart Validation Rules**: Inferred from database schema
- **Unique Field Handling**: Proper unique validation for updates
- **File Upload Validation**: Automatic file validation rules
- **Custom Messages**: Placeholder for custom validation messages
- **Data Transformation**: Pre-validation data cleaning
- **Password Hashing**: Automatic password encryption

### Policy Authorization

Generated policies include:

- **viewAny**: List resources
- **view**: View single resource
- **create**: Create new resources
- **update**: Update existing resources
- **delete**: Delete resources
- **restore**: Restore soft-deleted resources
- **forceDelete**: Permanently delete resources
- **bulkAction**: Perform bulk operations

## Configuration

The package uses the main AutoGen configuration file (`config/autogen.php`):

```php
return [
    'defaults' => [
        'controller_type' => 'resource',
        'pagination' => 15,
    ],
    
    'controller' => [
        'include_search' => true,
        'include_filters' => true,
        'include_sorting' => true,
        'include_bulk_actions' => true,
        'include_file_uploads' => true,
        'transaction_handling' => true,
        'error_handling' => true,
    ],
    
    'form_requests' => [
        'auto_detect_unique_fields' => true,
        'auto_detect_nullable_fields' => true,
        'include_file_validation' => true,
        'hash_passwords' => true,
    ],
    
    'policies' => [
        'include_bulk_action_check' => true,
        'include_restore_check' => true,
        'include_force_delete_check' => true,
    ],
];
```

## Custom Stubs

You can customize the generated code by creating custom stub files:

```
resources/stubs/autogen/
├── controller/
│   ├── resource.stub
│   └── api.stub
├── request/
│   ├── store.stub
│   └── update.stub
└── policy.stub
```

Set the custom stubs path in your configuration:

```php
'custom_stubs_path' => resource_path('stubs/autogen'),
```

## Advanced Features

### Transaction Handling

All generated controllers include database transaction support:

```php
try {
    DB::beginTransaction();
    
    // Perform operations
    $user = User::create($validated);
    
    DB::commit();
    return redirect()->route('users.show', $user);
} catch (\Exception $e) {
    DB::rollBack();
    return redirect()->back()->withInput()->with('error', $e->getMessage());
}
```

### File Upload Handling

Controllers automatically handle file uploads:

```php
// Handle file uploads
if ($request->hasFile('image')) {
    $validated['image'] = $request->file('image')->store('users/images', 'public');
}
```

### Search and Filtering

Generated controllers include comprehensive search capabilities:

```php
// Handle search
if ($request->filled('search')) {
    $query->where(function ($q) use ($search) {
        $q->where('name', 'LIKE', "%{$search}%")
          ->orWhere('email', 'LIKE', "%{$search}%");
    });
}

// Handle filters
if ($request->filled('status')) {
    $query->where('status', $request->input('status'));
}
```

### Bulk Operations

Support for bulk actions on multiple records:

```php
public function bulkAction(Request $request): RedirectResponse
{
    $request->validate([
        'action' => 'required|in:delete,activate,deactivate',
        'ids' => 'required|array',
        'ids.*' => 'exists:users,id',
    ]);

    // Perform bulk operation with transaction support
}
```

## Integration with Other Packages

The Controller package integrates seamlessly with other AutoGen packages:

- **autogen:model** - Use generated models as the foundation
- **autogen:views** - Generate corresponding view files
- **autogen:datatable** - Create high-performance data tables

## Best Practices

1. **Always use validation**: Include `--with-validation` for production controllers
2. **Implement policies**: Use `--with-policy` for proper authorization
3. **Customize generated code**: Review and modify generated controllers for your specific needs
4. **Handle file uploads**: Configure proper storage disks for file uploads
5. **Test thoroughly**: Generate test cases for your controllers

## Troubleshooting

### Common Issues

1. **Model not found**: Ensure the model class exists before generating controllers
2. **Permission denied**: Check file permissions for generated directories
3. **Validation rules**: Review generated validation rules and customize as needed
4. **Policy registration**: Remember to register policies in `AuthServiceProvider`

### Debug Mode

Enable debug mode to see detailed generation information:

```bash
php artisan autogen:controller User --with-validation -v
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](../../../CONTRIBUTING.md) for details.

## License

The AutoGen Controller package is open-sourced software licensed under the [MIT license](../../../LICENSE).