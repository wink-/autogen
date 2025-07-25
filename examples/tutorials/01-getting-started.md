# Getting Started with Laravel AutoGen

This tutorial will walk you through setting up and using the Laravel AutoGen package suite for the first time.

## Prerequisites

Before starting, ensure you have:
- PHP 8.3 or higher
- Laravel 12.0 or higher  
- Composer installed
- A database with existing tables
- Basic knowledge of Laravel

## Step 1: Installation

Install the AutoGen package via Composer:

```bash
composer require autogen/laravel-autogen
```

The package will automatically register itself thanks to Laravel's package discovery.

## Step 2: Publish Configuration

Publish the configuration files:

```bash
php artisan vendor:publish --provider="AutoGen\AutoGenServiceProvider"
```

This creates:
- `config/autogen.php` - Main configuration file
- `config/autogen-views.php` - Views-specific configuration

## Step 3: Set Up Your Database

For this tutorial, we'll use a simple blog database. Create a database and add these tables:

```sql
-- Users table
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Posts table  
CREATE TABLE posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    user_id BIGINT UNSIGNED NOT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Comments table
CREATE TABLE comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (post_id) REFERENCES posts(id)
);
```

Add some sample data:

```sql
INSERT INTO users (name, email, password, created_at, updated_at) VALUES
('John Doe', 'john@example.com', '$2y$10$hash', NOW(), NOW()),
('Jane Smith', 'jane@example.com', '$2y$10$hash', NOW(), NOW());

INSERT INTO posts (title, slug, content, status, user_id, published_at, created_at, updated_at) VALUES
('My First Post', 'my-first-post', 'This is my first blog post content.', 'published', 1, NOW(), NOW(), NOW()),
('Draft Post', 'draft-post', 'This is a draft post.', 'draft', 2, NULL, NOW(), NOW());
```

## Step 4: Configure Database Connection

Update your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blog_tutorial
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Step 5: Generate Your First Model

Generate the Post model from your database:

```bash
php artisan autogen:model Post
```

This creates `app/Models/Post.php` with:
- Proper table configuration
- Fillable attributes based on columns
- Relationship detection (belongsTo User)
- Intelligent casting based on column types
- PHP 8.3+ features

Let's examine the generated model:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug', 
        'content',
        'status',
        'user_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## Step 6: Generate a Controller

Generate a resource controller for the Post model:

```bash
php artisan autogen:controller Post --with-requests
```

This creates:
- `app/Http/Controllers/PostController.php` - Resource controller
- `app/Http/Requests/StorePostRequest.php` - Store validation
- `app/Http/Requests/UpdatePostRequest.php` - Update validation

The controller includes:
- All CRUD methods (index, create, store, show, edit, update, destroy)
- Proper authorization middleware
- Query optimization with eager loading
- Validation using form requests

## Step 7: Generate Views

Generate Blade views with Tailwind CSS:

```bash
php artisan autogen:views Post --framework=tailwind --with-search
```

This creates in `resources/views/posts/`:
- `index.blade.php` - List all posts with search
- `create.blade.php` - Create new post form
- `edit.blade.php` - Edit existing post form
- `show.blade.php` - Display single post
- `_form.blade.php` - Reusable form partial
- `_filters.blade.php` - Search filters

## Step 8: Add Routes

Add routes to `routes/web.php`:

```php
Route::resource('posts', PostController::class);
```

## Step 9: Generate a Factory (Optional)

Generate a model factory for testing:

```bash
php artisan autogen:factory Post --with-states
```

This creates `database/factories/PostFactory.php` with realistic fake data.

## Step 10: Test Your Generated Code

Start your development server:

```bash
php artisan serve
```

Visit `http://localhost:8000/posts` to see your generated CRUD interface!

## Step 11: Generate for Related Models

Generate for the remaining models:

```bash
# Generate User model (skip if using Laravel's default)
php artisan autogen:model User

# Generate Comment model with controller and views
php artisan autogen:controller Comment --with-requests  
php artisan autogen:views Comment --framework=tailwind
```

## Step 12: Complete Scaffold (Alternative Approach)

Instead of generating individual components, you can use the scaffold command:

```bash
php artisan autogen:scaffold Comment --framework=tailwind --with-datatable
```

This generates everything at once:
- Model
- Controller with form requests
- Views with search functionality
- Factory for testing
- Basic tests

## Next Steps

Now that you have a working CRUD interface, you can:

1. **Customize the views** to match your design
2. **Add authentication** if not already present
3. **Implement authorization** using policies
4. **Add more complex relationships** between models
5. **Generate API controllers** for mobile apps
6. **Create datatables** for large datasets
7. **Add comprehensive tests**

## Customization Tips

### Custom Stubs

To customize generated code, publish the stubs:

```bash
php artisan vendor:publish --tag="autogen-stubs"
```

Then modify the templates in `resources/stubs/autogen/`.

### Configuration

Edit `config/autogen.php` to:
- Change default CSS framework
- Modify naming conventions
- Set up AI providers
- Configure performance settings

### Multiple Databases

For legacy systems with multiple databases:

```php
// config/database.php
'legacy' => [
    'driver' => 'mysql',
    'host' => env('LEGACY_DB_HOST'),
    'database' => env('LEGACY_DB_DATABASE'),
    // ... other settings
],
```

Then generate from the legacy connection:

```bash
php artisan autogen:model --connection=legacy --dir=Legacy
```

## Troubleshooting

### Common Issues

1. **Permission errors**: Ensure proper file permissions
2. **Database connection**: Verify credentials and connection
3. **Missing relationships**: Check foreign key naming conventions
4. **View compilation**: Clear view cache with `php artisan view:clear`

### Getting Help

- Check the [documentation](../README.md)
- Review [examples](../examples/)
- Join [GitHub Discussions](https://github.com/autogen/laravel-autogen/discussions)

## Conclusion

You've successfully generated a complete CRUD interface using AutoGen! The generated code follows Laravel best practices and is ready for customization and production use.

In the next tutorial, we'll explore working with legacy databases and complex relationships.