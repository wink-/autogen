<?php

declare(strict_types=1);

namespace AutoGen\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use AutoGen\AutoGenServiceProvider;

class ViewGeneratorCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            AutoGenServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function test_it_can_generate_views_for_existing_model(): void
    {
        // Create a simple test model
        $this->createTestUserModel();
        
        // Run the command
        $this->artisan('autogen:views', ['model' => 'User'])
            ->expectsOutput('Generating views for model: User')
            ->expectsOutput('Framework: tailwind')
            ->assertExitCode(0);
        
        // Assert view files are created
        $viewPath = resource_path('views/user');
        $this->assertTrue(File::exists("{$viewPath}/index.blade.php"));
        $this->assertTrue(File::exists("{$viewPath}/create.blade.php"));
        $this->assertTrue(File::exists("{$viewPath}/edit.blade.php"));
        $this->assertTrue(File::exists("{$viewPath}/show.blade.php"));
        $this->assertTrue(File::exists("{$viewPath}/_form.blade.php"));
        $this->assertTrue(File::exists("{$viewPath}/_table.blade.php"));
    }

    public function test_it_can_generate_bootstrap_views(): void
    {
        $this->createTestUserModel();
        
        $this->artisan('autogen:views', [
            'model' => 'User',
            '--framework' => 'bootstrap'
        ])
            ->expectsOutput('Framework: bootstrap')
            ->assertExitCode(0);
        
        $indexContent = File::get(resource_path('views/user/index.blade.php'));
        $this->assertStringContains('class="card shadow"', $indexContent);
        $this->assertStringContains('class="btn btn-primary"', $indexContent);
    }

    public function test_it_can_generate_specific_views_only(): void
    {
        $this->createTestUserModel();
        
        $this->artisan('autogen:views', [
            'model' => 'User',
            '--only' => 'index,create'
        ])->assertExitCode(0);
        
        $viewPath = resource_path('views/user');
        $this->assertTrue(File::exists("{$viewPath}/index.blade.php"));
        $this->assertTrue(File::exists("{$viewPath}/create.blade.php"));
        $this->assertFalse(File::exists("{$viewPath}/edit.blade.php"));
        $this->assertFalse(File::exists("{$viewPath}/show.blade.php"));
    }

    public function test_it_fails_for_non_existent_model(): void
    {
        $this->artisan('autogen:views', ['model' => 'NonExistentModel'])
            ->expectsOutput('Model App\\Models\\NonExistentModel does not exist.')
            ->assertExitCode(1);
    }

    public function test_it_rejects_invalid_framework(): void
    {
        $this->createTestUserModel();
        
        $this->artisan('autogen:views', [
            'model' => 'User',
            '--framework' => 'invalid'
        ])
            ->expectsOutput('Invalid framework: invalid. Must be one of: tailwind, bootstrap, css')
            ->assertExitCode(1);
    }

    public function test_it_can_generate_with_search_and_modals(): void
    {
        $this->createTestUserModel();
        
        $this->artisan('autogen:views', [
            'model' => 'User',
            '--with-search' => true,
            '--with-modals' => true
        ])->assertExitCode(0);
        
        $viewPath = resource_path('views/user');
        $this->assertTrue(File::exists("{$viewPath}/_filters.blade.php"));
        
        $indexContent = File::get("{$viewPath}/index.blade.php");
        $this->assertStringContains('deleteModal', $indexContent);
        $this->assertStringContains('toggleFilters', $indexContent);
    }

    protected function createTestUserModel(): void
    {
        // Create users table migration
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Create a simple User model
        $modelContent = "<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class User extends Model
{
    protected \$fillable = [
        'name',
        'email',
        'password',
    ];

    protected \$hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}";

        File::ensureDirectoryExists(app_path('Models'));
        File::put(app_path('Models/User.php'), $modelContent);
    }

    protected function tearDown(): void
    {
        // Clean up generated files
        $viewPath = resource_path('views/user');
        if (File::isDirectory($viewPath)) {
            File::deleteDirectory($viewPath);
        }

        $modelPath = app_path('Models/User.php');
        if (File::exists($modelPath)) {
            File::delete($modelPath);
        }

        parent::tearDown();
    }
}