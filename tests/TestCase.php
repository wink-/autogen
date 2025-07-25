<?php

declare(strict_types=1);

namespace AutoGen\Tests;

use AutoGen\AutoGenServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->cleanupGeneratedFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AutoGenServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set up test environment
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up autogen configuration
        config()->set('autogen.defaults.controller_type', 'resource');
        config()->set('autogen.defaults.pagination', 15);
        config()->set('autogen.test_mode', true);
        
        // Filesystem configuration for testing
        config()->set('filesystems.disks.local.root', storage_path('app/testing'));
    }

    /**
     * Set up test database with sample tables for testing
     */
    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('birth_date')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->text('bio')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->string('status')->default('draft');
            $table->integer('view_count')->default(0);
            $table->json('meta_data')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#000000');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('post_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Create tables with various column types for comprehensive testing
        Schema::create('sample_table', function (Blueprint $table) {
            $table->id();
            $table->string('varchar_field', 100);
            $table->text('text_field');
            $table->integer('integer_field');
            $table->bigInteger('bigint_field');
            $table->decimal('decimal_field', 8, 2);
            $table->float('float_field');
            $table->double('double_field');
            $table->boolean('boolean_field');
            $table->date('date_field');
            $table->time('time_field');
            $table->dateTime('datetime_field');
            $table->timestamp('timestamp_field');
            $table->json('json_field');
            $table->enum('enum_field', ['option1', 'option2', 'option3']);
            $table->timestamps();
        });
    }

    /**
     * Seed test data
     */
    protected function seedTestData(): void
    {
        // Create test users
        $this->createTestUser(['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->createTestUser(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        
        // Create test categories
        $this->createTestCategory(['name' => 'Technology', 'slug' => 'technology']);
        $this->createTestCategory(['name' => 'Programming', 'slug' => 'programming']);
        
        // Create test posts
        $this->createTestPost([
            'title' => 'Sample Post',
            'slug' => 'sample-post',
            'content' => 'This is sample content',
            'user_id' => 1
        ]);
    }

    /**
     * Create a test user
     */
    protected function createTestUser(array $attributes = []): array
    {
        $user = array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ], $attributes);

        return \DB::table('users')->insertGetId($user) ? $user : [];
    }

    /**
     * Create a test post
     */
    protected function createTestPost(array $attributes = []): array
    {
        $post = array_merge([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Test content',
            'status' => 'published',
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);

        return \DB::table('posts')->insertGetId($post) ? $post : [];
    }

    /**
     * Create a test category
     */
    protected function createTestCategory(array $attributes = []): array
    {
        $category = array_merge([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);

        return \DB::table('categories')->insertGetId($category) ? $category : [];
    }

    /**
     * Clean up generated files after tests
     */
    protected function cleanupGeneratedFiles(): void
    {
        $paths = [
            // Controllers
            app_path('Http/Controllers'),
            // Models
            app_path('Models'),
            // Migrations
            database_path('migrations'),
            // Factories
            database_path('factories'),
            // Views
            resource_path('views'),
            // Policies
            app_path('Policies'),
            // Requests
            app_path('Http/Requests'),
            // Resources
            app_path('Http/Resources'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                $files = File::glob($path . '/*Test*.php');
                $files = array_merge($files, File::glob($path . '/*/*Test*.php'));
                foreach ($files as $file) {
                    File::delete($file);
                }
            }
        }
    }

    /**
     * Assert that a file was generated with expected content
     */
    protected function assertFileGenerated(string $path, array $expectedContent = []): void
    {
        $this->assertFileExists($path, "Expected file was not generated: {$path}");
        
        if (!empty($expectedContent)) {
            $content = File::get($path);
            foreach ($expectedContent as $expected) {
                $this->assertStringContainsString(
                    $expected,
                    $content,
                    "Generated file does not contain expected content: {$expected}"
                );
            }
        }
    }

    /**
     * Assert that multiple files were generated
     */
    protected function assertFilesGenerated(array $paths): void
    {
        foreach ($paths as $path) {
            $this->assertFileExists($path, "Expected file was not generated: {$path}");
        }
    }

    /**
     * Get expected controller path
     */
    protected function getControllerPath(string $modelName, string $type = 'web'): string
    {
        $namespace = $type === 'api' ? 'Api/' : '';
        return app_path("Http/Controllers/{$namespace}{$modelName}Controller.php");
    }

    /**
     * Get expected model path
     */
    protected function getModelPath(string $modelName): string
    {
        return app_path("Models/{$modelName}.php");
    }

    /**
     * Get expected factory path
     */
    protected function getFactoryPath(string $modelName): string
    {
        return database_path("factories/{$modelName}Factory.php");
    }

    /**
     * Get expected migration path
     */
    protected function getMigrationPath(string $tableName): string
    {
        $timestamp = date('Y_m_d_His');
        return database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
    }

    /**
     * Mock console output for testing commands
     */
    protected function mockConsoleOutput(): void
    {
        $this->app->bind('command.output', function () {
            return new \Symfony\Component\Console\Output\BufferedOutput();
        });
    }

    /**
     * Get table schema information for testing
     */
    protected function getTableSchema(string $tableName): array
    {
        return Schema::getColumnListing($tableName);
    }

    /**
     * Create temporary directory for testing
     */
    protected function createTempDirectory(string $name = 'temp'): string
    {
        $path = storage_path("app/testing/{$name}");
        File::ensureDirectoryExists($path);
        return $path;
    }
}