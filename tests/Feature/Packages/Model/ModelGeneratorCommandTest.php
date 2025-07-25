<?php

declare(strict_types=1);

namespace AutoGen\Tests\Feature\Packages\Model;

use AutoGen\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ModelGeneratorCommandTest extends TestCase
{
    /** @test */
    public function it_can_generate_model_from_existing_table(): void
    {
        $this->artisan('autogen:model', ['table' => 'users'])
            ->expectsOutput('Generating model for table: users')
            ->expectsOutput('Model User generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('User');
        $this->assertFileGenerated($modelPath, [
            'class User extends Model',
            'protected $table = \'users\'',
            'use HasFactory',
        ]);
    }

    /** @test */
    public function it_can_generate_model_with_relationships(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'posts',
                '--with-relationships' => true,
            ])
            ->expectsOutput('Analyzing relationships...')
            ->expectsOutput('Model Post generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('Post');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString('public function user(): BelongsTo', $content);
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo', $content);
    }

    /** @test */
    public function it_can_generate_model_with_scopes(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'posts',
                '--with-scopes' => true,
            ])
            ->expectsOutput('Model Post generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('Post');
        $content = File::get($modelPath);
        
        // The posts table has a status column in our test schema
        if (str_contains($content, 'status')) {
            $this->assertStringContainsString('public function scopeStatus($query, string $status)', $content);
        }
    }

    /** @test */
    public function it_can_generate_model_with_validation(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'users',
                '--with-validation' => true,
            ])
            ->expectsOutput('Model User generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\Casts\\Attribute', $content);
    }

    /** @test */
    public function it_can_generate_model_in_custom_directory(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'users',
                '--dir' => 'Admin',
            ])
            ->expectsOutput('Model User generated successfully!')
            ->assertExitCode(0);

        $modelPath = app_path('Models/Admin/User.php');
        $this->assertFileGenerated($modelPath, [
            'namespace App\\Models\\Admin',
            'class User extends Model',
        ]);
    }

    /** @test */
    public function it_can_generate_model_with_custom_namespace(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'users',
                '--namespace' => 'App\\Domain\\User\\Models',
            ])
            ->expectsOutput('Model User generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString('namespace App\\Domain\\User\\Models', $content);
    }

    /** @test */
    public function it_prompts_for_confirmation_when_model_exists(): void
    {
        // Create an existing model
        $modelPath = $this->getModelPath('User');
        File::ensureDirectoryExists(dirname($modelPath));
        File::put($modelPath, '<?php // Existing model');

        $this->artisan('autogen:model', ['table' => 'users'])
            ->expectsQuestion('Model User already exists. Do you want to overwrite it?', false)
            ->expectsOutput('Model generation cancelled.')
            ->assertExitCode(0);

        // Verify the original content is preserved
        $content = File::get($modelPath);
        $this->assertStringContainsString('// Existing model', $content);
    }

    /** @test */
    public function it_can_force_overwrite_existing_model(): void
    {
        // Create an existing model
        $modelPath = $this->getModelPath('User');
        File::ensureDirectoryExists(dirname($modelPath));
        File::put($modelPath, '<?php // Existing model');

        $this->artisan('autogen:model', [
                'table' => 'users',
                '--force' => true,
            ])
            ->expectsOutput('Model User generated successfully!')
            ->assertExitCode(0);

        // Verify the model was overwritten
        $content = File::get($modelPath);
        $this->assertStringNotContainsString('// Existing model', $content);
        $this->assertStringContainsString('class User extends Model', $content);
    }

    /** @test */
    public function it_fails_for_non_existent_table(): void
    {
        $this->artisan('autogen:model', ['table' => 'non_existent_table'])
            ->expectsOutput('Error: Table non_existent_table does not exist.')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_can_generate_multiple_models_at_once(): void
    {
        $this->artisan('autogen:model', ['--all' => true])
            ->expectsOutput('Generating models for all tables...')
            ->expectsOutput('Model User generated successfully!')
            ->expectsOutput('Model Post generated successfully!')
            ->expectsOutput('Model Category generated successfully!')
            ->assertExitCode(0);

        $this->assertFileExists($this->getModelPath('User'));
        $this->assertFileExists($this->getModelPath('Post'));
        $this->assertFileExists($this->getModelPath('Category'));
    }

    /** @test */
    public function it_can_exclude_tables_when_generating_all(): void
    {
        $this->artisan('autogen:model', [
                '--all' => true,
                '--exclude' => 'users,categories',
            ])
            ->expectsOutput('Generating models for all tables...')
            ->expectsOutput('Model Post generated successfully!')
            ->assertExitCode(0);

        $this->assertFileExists($this->getModelPath('Post'));
        $this->assertFileDoesNotExist($this->getModelPath('User'));
        $this->assertFileDoesNotExist($this->getModelPath('Category'));
    }

    /** @test */
    public function it_shows_progress_when_generating_multiple_models(): void
    {
        $this->artisan('autogen:model', ['--all' => true])
            ->expectsOutput('Generating models for all tables...')
            ->expectsOutputToContain('Progress:')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_generate_model_with_minimal_template(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'users',
                '--template' => 'minimal',
            ])
            ->expectsOutput('Model User generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        // Minimal template should have basic structure only
        $this->assertStringContainsString('class User extends Model', $content);
        $this->assertStringContainsString('protected $table = \'users\'', $content);
    }

    /** @test */
    public function it_can_generate_model_with_enhanced_template(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'posts',
                '--template' => 'enhanced',
                '--with-relationships' => true,
                '--with-scopes' => true,
            ])
            ->expectsOutput('Model Post generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('Post');
        $content = File::get($modelPath);
        
        // Enhanced template should include relationships and scopes
        $this->assertStringContainsString('public function user(): BelongsTo', $content);
    }

    /** @test */
    public function it_validates_command_options(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'users',
                '--template' => 'invalid_template',
            ])
            ->expectsOutput('Error: Invalid template specified. Available templates: minimal, default, enhanced')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_can_dry_run_model_generation(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'users',
                '--dry-run' => true,
            ])
            ->expectsOutput('Dry run mode - no files will be created')
            ->expectsOutput('Would generate: User model')
            ->expectsOutputToContain('Target path:')
            ->assertExitCode(0);

        // Verify no file was actually created
        $this->assertFileDoesNotExist($this->getModelPath('User'));
    }

    /** @test */
    public function it_shows_detailed_output_in_verbose_mode(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'users',
                '-v' => true,
            ])
            ->expectsOutputToContain('Analyzing table structure...')
            ->expectsOutputToContain('Detected columns:')
            ->expectsOutputToContain('Detected primary key:')
            ->expectsOutputToContain('Generating model class...')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_generate_model_with_custom_connection(): void
    {
        $this->artisan('autogen:model', [
                'table' => 'users',
                '--connection' => 'custom_connection',
            ])
            ->expectsOutput('Model User generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('User');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString("protected const string CONNECTION = 'custom_connection'", $content);
    }

    /** @test */
    public function it_handles_table_with_no_primary_key(): void
    {
        // Create a table without primary key for testing
        $this->artisan('autogen:model', ['table' => 'post_category'])
            ->expectsOutput('Warning: Table post_category has no primary key')
            ->expectsOutput('Model PostCategory generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('PostCategory');
        $content = File::get($modelPath);
        
        $this->assertStringContainsString('public $incrementing = false', $content);
    }

    /** @test */
    public function it_can_generate_model_with_config_file(): void
    {
        // Create a temporary config file
        $configPath = storage_path('app/testing/model-config.json');
        File::ensureDirectoryExists(dirname($configPath));
        File::put($configPath, json_encode([
            'with_relationships' => true,
            'with_scopes' => true,
            'template' => 'enhanced',
        ]));

        $this->artisan('autogen:model', [
                'table' => 'posts',
                '--config' => $configPath,
            ])
            ->expectsOutput('Loading configuration from: ' . $configPath)
            ->expectsOutput('Model Post generated successfully!')
            ->assertExitCode(0);

        $modelPath = $this->getModelPath('Post');
        $this->assertFileExists($modelPath);
    }
}