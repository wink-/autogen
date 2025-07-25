<?php

declare(strict_types=1);

namespace AutoGen\Tests\E2E;

use AutoGen\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ScaffoldCommandTest extends TestCase
{
    /** @test */
    public function it_can_scaffold_complete_crud_for_existing_table(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
            ])
            ->expectsOutput('Starting AutoGen Scaffold for table: users')
            ->expectsOutput('✓ Model generated successfully')
            ->expectsOutput('✓ Controller generated successfully')
            ->expectsOutput('✓ Views generated successfully')  
            ->expectsOutput('✓ Factory generated successfully')
            ->expectsOutput('✓ Migration generated successfully')
            ->expectsOutput('✓ Datatable generated successfully')
            ->expectsOutput('🎉 Scaffold completed successfully!')
            ->assertExitCode(0);

        // Verify all files were created
        $this->assertFileExists($this->getModelPath('User'));
        $this->assertFileExists($this->getControllerPath('User'));
        $this->assertFileExists($this->getFactoryPath('User'));
        $this->assertFileExists(resource_path('views/user/index.blade.php'));
        $this->assertFileExists(resource_path('views/user/create.blade.php'));
        $this->assertFileExists(resource_path('views/user/edit.blade.php'));
        $this->assertFileExists(resource_path('views/user/show.blade.php'));
    }

    /** @test */
    public function it_can_scaffold_with_specific_components(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'posts',
                '--model' => true,
                '--controller' => true,
                '--views' => true,
            ])
            ->expectsOutput('Starting AutoGen Scaffold for table: posts')
            ->expectsOutput('✓ Model generated successfully')
            ->expectsOutput('✓ Controller generated successfully')
            ->expectsOutput('✓ Views generated successfully')
            ->expectsOutput('⏭ Factory skipped (not requested)')
            ->expectsOutput('⏭ Migration skipped (not requested)')
            ->expectsOutput('⏭ Datatable skipped (not requested)')
            ->expectsOutput('🎉 Scaffold completed successfully!')
            ->assertExitCode(0);

        // Verify only requested files were created
        $this->assertFileExists($this->getModelPath('Post'));
        $this->assertFileExists($this->getControllerPath('Post'));
        $this->assertFileExists(resource_path('views/post/index.blade.php'));
        $this->assertFileDoesNotExist($this->getFactoryPath('Post'));
    }

    /** @test */
    public function it_can_scaffold_with_relationships(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'posts',
                '--all' => true,
                '--with-relationships' => true,
            ])
            ->expectsOutput('Starting AutoGen Scaffold for table: posts')
            ->expectsOutput('🔍 Analyzing table relationships...')
            ->expectsOutput('Found relationships: user (belongsTo)')
            ->expectsOutput('✓ Model generated with relationships')
            ->expectsOutput('✓ Controller generated with relationship handling')
            ->expectsOutput('✓ Factory generated with relationship methods')
            ->assertExitCode(0);

        // Check model contains relationships
        $modelContent = File::get($this->getModelPath('Post'));
        $this->assertStringContainsString('public function user(): BelongsTo', $modelContent);
        
        // Check factory contains relationship methods
        $factoryContent = File::get($this->getFactoryPath('Post'));
        $this->assertStringContainsString('withUser()', $factoryContent);
    }

    /** @test */
    public function it_can_scaffold_with_validation_and_policies(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
                '--with-validation' => true,
                '--with-policies' => true,
            ])
            ->expectsOutput('Starting AutoGen Scaffold for table: users')
            ->expectsOutput('✓ Model generated successfully')
            ->expectsOutput('✓ Controller generated with validation')
            ->expectsOutput('✓ Form requests generated')
            ->expectsOutput('✓ Policy generated')
            ->expectsOutput('✓ Views generated with validation display')
            ->assertExitCode(0);

        // Verify validation files
        $this->assertFileExists(app_path('Http/Requests/StoreUserRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/UpdateUserRequest.php'));
        $this->assertFileExists(app_path('Policies/UserPolicy.php'));
        
        // Check controller uses form requests
        $controllerContent = File::get($this->getControllerPath('User'));  
        $this->assertStringContainsString('StoreUserRequest $request', $controllerContent);
        $this->assertStringContainsString('UpdateUserRequest $request', $controllerContent);
    }

    /** @test */
    public function it_can_scaffold_with_custom_framework(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'categories',
                '--all' => true,
                '--framework' => 'tailwind',
            ])
            ->expectsOutput('Starting AutoGen Scaffold for table: categories')
            ->expectsOutput('✓ Views generated with Tailwind CSS')
            ->assertExitCode(0);

        // Check views contain Tailwind classes
        $indexContent = File::get(resource_path('views/category/index.blade.php'));
        $this->assertStringContainsString('bg-white', $indexContent);
        $this->assertStringContainsString('shadow-md', $indexContent);
        $this->assertStringContainsString('rounded-lg', $indexContent);
    }

    /** @test */
    public function it_can_scaffold_with_datatable_integration(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
                '--datatable' => 'yajra',
            ])
            ->expectsOutput('Starting AutoGen Scaffold for table: users')
            ->expectsOutput('✓ Datatable generated (Yajra)')
            ->expectsOutput('✓ Views generated with datatable integration')
            ->expectsOutput('✓ Controller updated for datatable support')
            ->assertExitCode(0);

        // Check datatable file exists
        $this->assertFileExists(app_path('DataTables/UserDataTable.php'));
        
        // Check views include datatable
        $indexContent = File::get(resource_path('views/user/index.blade.php'));
        $this->assertStringContainsString('DataTable', $indexContent);
    }

    /** @test */
    public function it_can_scaffold_api_resources(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'posts',
                '--all' => true,
                '--api' => true,
            ])
            ->expectsOutput('Starting AutoGen Scaffold for table: posts')
            ->expectsOutput('✓ API Controller generated')
            ->expectsOutput('✓ API Resources generated')
            ->expectsOutput('✓ API Routes suggested')
            ->expectsOutput('⏭ Views skipped (API mode)')
            ->assertExitCode(0);

        // Verify API files
        $this->assertFileExists(app_path('Http/Controllers/Api/PostController.php'));
        $this->assertFileExists(app_path('Http/Resources/PostResource.php'));
        $this->assertFileExists(app_path('Http/Resources/PostCollection.php'));
        
        // Views should not be generated in API mode
        $this->assertFileDoesNotExist(resource_path('views/post/index.blade.php'));
    }

    /** @test */
    public function it_handles_nested_model_scaffolding(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
                '--namespace' => 'Admin',
            ])
            ->expectsOutput('Starting AutoGen Scaffold for table: users')
            ->expectsOutput('✓ Model generated in Admin namespace')
            ->expectsOutput('✓ Controller generated in Admin namespace')
            ->expectsOutput('✓ Views generated in admin directory')
            ->assertExitCode(0);

        // Verify nested structure
        $this->assertFileExists(app_path('Models/Admin/User.php'));
        $this->assertFileExists(app_path('Http/Controllers/Admin/UserController.php'));
        $this->assertFileExists(resource_path('views/admin/user/index.blade.php'));
    }

    /** @test */
    public function it_shows_progress_during_scaffolding(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
                '--verbose' => true,
            ])
            ->expectsOutputToContain('Progress: [▓▓▓▓▓▓▓▓▓▓] 100%')
            ->expectsOutputToContain('Step 1/6: Generating Model')
            ->expectsOutputToContain('Step 2/6: Generating Controller')
            ->expectsOutputToContain('Step 3/6: Generating Views')
            ->expectsOutputToContain('Step 4/6: Generating Factory')
            ->expectsOutputToContain('Step 5/6: Generating Migration')
            ->expectsOutputToContain('Step 6/6: Generating Datatable')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_scaffold_with_configuration_file(): void
    {
        // Create configuration file
        $configPath = storage_path('app/testing/scaffold-config.json');
        File::ensureDirectoryExists(dirname($configPath));
        File::put($configPath, json_encode([
            'components' => ['model', 'controller', 'views', 'factory'],
            'framework' => 'bootstrap',
            'with_relationships' => true,
            'with_validation' => true,
            'datatable' => 'yajra',
            'pagination' => 25,
        ]));

        $this->artisan('autogen:scaffold', [
                'table' => 'posts',
                '--config' => $configPath,
            ])
            ->expectsOutput('Loading configuration from: ' . $configPath)
            ->expectsOutput('✓ Model generated with relationships')
            ->expectsOutput('✓ Controller generated with validation')
            ->expectsOutput('✓ Views generated with Bootstrap')
            ->expectsOutput('✓ Factory generated successfully')
            ->expectsOutput('✓ Datatable generated (Yajra)')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_dry_run_scaffold(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
                '--dry-run' => true,
            ])
            ->expectsOutput('🔍 Dry Run Mode - No files will be created')
            ->expectsOutput('Would generate the following files:')
            ->expectsOutputToContain('Model: app/Models/User.php')
            ->expectsOutputToContain('Controller: app/Http/Controllers/UserController.php')
            ->expectsOutputToContain('Views: resources/views/user/*.blade.php')
            ->expectsOutputToContain('Factory: database/factories/UserFactory.php')
            ->expectsOutput('Total files: 8')
            ->assertExitCode(0);

        // Verify no files were actually created
        $this->assertFileDoesNotExist($this->getModelPath('User'));
        $this->assertFileDoesNotExist($this->getControllerPath('User'));
    }

    /** @test */
    public function it_handles_errors_gracefully(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'non_existent_table',
                '--all' => true,
            ])
            ->expectsOutput('❌ Error: Table non_existent_table does not exist')
            ->expectsOutput('Available tables: users, posts, categories, comments, tags, sample_table')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_can_scaffold_multiple_tables(): void
    {
        $this->artisan('autogen:scaffold', [
                '--tables' => 'users,posts,categories',
                '--model' => true,
                '--controller' => true,
            ])
            ->expectsOutput('Starting batch scaffold for tables: users, posts, categories')
            ->expectsOutput('Processing table: users')
            ->expectsOutput('✓ User scaffold completed')
            ->expectsOutput('Processing table: posts')
            ->expectsOutput('✓ Post scaffold completed')
            ->expectsOutput('Processing table: categories')
            ->expectsOutput('✓ Category scaffold completed')
            ->expectsOutput('🎉 All scaffolds completed successfully!')
            ->assertExitCode(0);

        // Verify files for all tables
        $this->assertFileExists($this->getModelPath('User'));
        $this->assertFileExists($this->getModelPath('Post'));
        $this->assertFileExists($this->getModelPath('Category'));
        $this->assertFileExists($this->getControllerPath('User'));
        $this->assertFileExists($this->getControllerPath('Post'));
        $this->assertFileExists($this->getControllerPath('Category'));
    }

    /** @test */
    public function it_prompts_for_overwrite_confirmation(): void
    {
        // Create existing files
        File::ensureDirectoryExists(dirname($this->getModelPath('User')));
        File::put($this->getModelPath('User'), '<?php // Existing model');

        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
            ])
            ->expectsQuestion('The following files already exist:', true)
            ->expectsQuestion('Do you want to overwrite them?', false)
            ->expectsOutput('❌ Scaffold cancelled by user')
            ->assertExitCode(0);

        // Original file should be preserved
        $content = File::get($this->getModelPath('User'));
        $this->assertStringContainsString('// Existing model', $content);
    }

    /** @test */
    public function it_can_force_overwrite_existing_files(): void
    {
        // Create existing files
        File::ensureDirectoryExists(dirname($this->getModelPath('User')));
        File::put($this->getModelPath('User'), '<?php // Existing model');

        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
                '--force' => true,
            ])
            ->expectsOutput('🔄 Force mode enabled - overwriting existing files')
            ->expectsOutput('✓ Model generated successfully')
            ->assertExitCode(0);

        // File should be overwritten
        $content = File::get($this->getModelPath('User'));
        $this->assertStringNotContainsString('// Existing model', $content);
        $this->assertStringContainsString('class User extends Model', $content);
    }

    /** @test */
    public function it_displays_summary_after_completion(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
            ])
            ->expectsOutput('📊 Scaffold Summary:')
            ->expectsOutput('Table: users')
            ->expectsOutput('Files generated: 8')
            ->expectsOutput('Components: Model, Controller, Views, Factory, Migration, Datatable')
            ->expectsOutputToContain('Total time:')
            ->expectsOutput('🎉 Scaffold completed successfully!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_scaffold_with_soft_deletes(): void
    {
        $this->artisan('autogen:scaffold', [
                'table' => 'users',
                '--all' => true,
                '--soft-deletes' => true,
            ])
            ->expectsOutput('✓ Model generated with soft deletes')
            ->expectsOutput('✓ Controller generated with restore/force delete methods')
            ->expectsOutput('✓ Views generated with soft delete handling')
            ->expectsOutput('✓ Policy generated with restore/force delete permissions')
            ->assertExitCode(0);

        // Verify soft delete implementation
        $modelContent = File::get($this->getModelPath('User'));
        $this->assertStringContainsString('use SoftDeletes', $modelContent);
        
        $controllerContent = File::get($this->getControllerPath('User'));
        $this->assertStringContainsString('public function restore', $controllerContent);
        $this->assertStringContainsString('public function forceDelete', $controllerContent);
    }

    protected function tearDown(): void
    {
        // Clean up all generated files
        $paths = [
            app_path('Models'),
            app_path('Http/Controllers'),
            app_path('Http/Requests'),
            app_path('Policies'),
            app_path('DataTables'),
            app_path('Exports'),
            database_path('factories'),
            database_path('migrations'),
            resource_path('views'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                $generatedFiles = File::glob($path . '/*Test*.php');
                $generatedFiles = array_merge($generatedFiles, File::glob($path . '/*/*Test*.php'));
                $generatedFiles = array_merge($generatedFiles, File::glob($path . '/**/Test*.blade.php'));
                
                foreach ($generatedFiles as $file) {
                    File::delete($file);
                }
            }
        }

        parent::tearDown();
    }
}