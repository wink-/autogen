<?php

declare(strict_types=1);

namespace AutoGen\Tests\Feature\Packages\Controller;

use AutoGen\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ControllerGeneratorCommandTest extends TestCase
{
    /** @test */
    public function it_can_generate_resource_controller(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--type' => 'resource',
            ])
            ->expectsOutput('Generating resource controller for User...')
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('User');
        $this->assertFileGenerated($controllerPath, [
            'class UserController extends Controller',
            'public function index()',
            'public function create()',
            'public function store(Request $request)',
            'public function show(User $user)',
            'public function edit(User $user)',
            'public function update(Request $request, User $user)',
            'public function destroy(User $user)',
        ]);
    }

    /** @test */
    public function it_can_generate_api_controller(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'Post',
                '--type' => 'api',
            ])
            ->expectsOutput('Generating API controller for Post...')
            ->expectsOutput('Controller PostController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('Post', 'api');
        $this->assertFileGenerated($controllerPath, [
            'namespace App\\Http\\Controllers\\Api',
            'class PostController extends Controller',
            'public function index()',
            'public function store(Request $request)',
            'public function show(Post $post)',
            'public function update(Request $request, Post $post)',
            'public function destroy(Post $post)',
            'use Illuminate\\Http\\JsonResponse',
        ]);
    }

    /** @test */
    public function it_can_generate_controller_with_validation(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--validation' => true,
            ])
            ->expectsOutput('Generating form request classes...')
            ->expectsOutput('Generated StoreUserRequest')
            ->expectsOutput('Generated UpdateUserRequest')
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        // Check controller uses form requests
        $controllerPath = $this->getControllerPath('User');
        $content = File::get($controllerPath);
        $this->assertStringContainsString('StoreUserRequest $request', $content);
        $this->assertStringContainsString('UpdateUserRequest $request', $content);

        // Check form request files were created
        $this->assertFileExists(app_path('Http/Requests/StoreUserRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/UpdateUserRequest.php'));
    }

    /** @test */
    public function it_can_generate_controller_with_policy(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'Post',
                '--policy' => true,
            ])
            ->expectsOutput('Generating policy class...')
            ->expectsOutput('Generated PostPolicy')
            ->expectsOutput('Controller PostController generated successfully!')
            ->assertExitCode(0);

        // Check controller uses authorization
        $controllerPath = $this->getControllerPath('Post');
        $content = File::get($controllerPath);
        $this->assertStringContainsString('$this->authorize(\'view\', $post)', $content);
        $this->assertStringContainsString('$this->authorize(\'create\', Post::class)', $content);

        // Check policy file was created
        $this->assertFileExists(app_path('Policies/PostPolicy.php'));
    }

    /** @test */
    public function it_can_generate_controller_with_pagination(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--paginate' => 25,
            ])
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('User');
        $content = File::get($controllerPath);
        $this->assertStringContainsString('paginate(25)', $content);
    }

    /** @test */
    public function it_can_generate_nested_controller(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'Admin/User',
                '--type' => 'resource',
            ])
            ->expectsOutput('Controller Admin\\UserController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = app_path('Http/Controllers/Admin/UserController.php');
        $this->assertFileGenerated($controllerPath, [
            'namespace App\\Http\\Controllers\\Admin',
            'use App\\Models\\Admin\\User',
            'class UserController extends Controller',
        ]);
    }

    /** @test */
    public function it_can_generate_controller_with_custom_methods(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--methods' => 'index,show,activate,deactivate',
            ])
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('User');
        $content = File::get($controllerPath);
        
        $this->assertStringContainsString('public function index()', $content);
        $this->assertStringContainsString('public function show(User $user)', $content);
        $this->assertStringContainsString('public function activate(User $user)', $content);
        $this->assertStringContainsString('public function deactivate(User $user)', $content);
        
        // Should not contain other resource methods
        $this->assertStringNotContainsString('public function create()', $content);
        $this->assertStringNotContainsString('public function store', $content);
    }

    /** @test */
    public function it_prompts_for_overwrite_confirmation(): void
    {
        // Create existing controller
        $controllerPath = $this->getControllerPath('User');
        File::ensureDirectoryExists(dirname($controllerPath));
        File::put($controllerPath, '<?php // Existing controller');

        $this->artisan('autogen:controller', ['model' => 'User'])
            ->expectsQuestion('Controller UserController already exists. Do you want to overwrite it?', false)
            ->expectsOutput('Controller generation cancelled.')
            ->assertExitCode(0);

        // Original content should be preserved
        $content = File::get($controllerPath);
        $this->assertStringContainsString('// Existing controller', $content);
    }

    /** @test */
    public function it_can_force_overwrite_existing_controller(): void
    {
        // Create existing controller
        $controllerPath = $this->getControllerPath('User');
        File::ensureDirectoryExists(dirname($controllerPath));
        File::put($controllerPath, '<?php // Existing controller');

        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--force' => true,
            ])
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        // Content should be replaced
        $content = File::get($controllerPath);
        $this->assertStringNotContainsString('// Existing controller', $content);
        $this->assertStringContainsString('class UserController extends Controller', $content);
    }

    /** @test */
    public function it_can_generate_controller_with_resources(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'Post',
                '--type' => 'api',
                '--resources' => true,
            ])
            ->expectsOutput('Generating API resource classes...')
            ->expectsOutput('Generated PostResource')
            ->expectsOutput('Controller PostController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('Post', 'api');
        $content = File::get($controllerPath);
        
        $this->assertStringContainsString('use App\\Http\\Resources\\PostResource', $content);
        $this->assertStringContainsString('return PostResource::collection', $content);
        $this->assertStringContainsString('return new PostResource', $content);

        // Check resource file was created
        $this->assertFileExists(app_path('Http/Resources/PostResource.php'));
    }

    /** @test */
    public function it_can_generate_invokable_controller(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--invokable' => true,
            ])
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('User');
        $content = File::get($controllerPath);
        
        $this->assertStringContainsString('public function __invoke(', $content);
        $this->assertStringNotContainsString('public function index(', $content);
    }

    /** @test */
    public function it_validates_model_exists_in_database(): void
    {
        $this->artisan('autogen:controller', ['model' => 'NonExistentModel'])
            ->expectsOutput('Error: No table found for model NonExistentModel')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_can_generate_controller_from_table_name(): void
    {
        $this->artisan('autogen:controller', [
                '--table' => 'users',
                '--type' => 'resource',
            ])
            ->expectsOutput('Generating resource controller for User (from table: users)...')
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('User');
        $this->assertFileExists($controllerPath);
    }

    /** @test */
    public function it_can_generate_controller_with_middleware(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--middleware' => 'auth,verified',
            ])
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('User');
        $content = File::get($controllerPath);
        
        $this->assertStringContainsString('$this->middleware([\'auth\', \'verified\'])', $content);
    }

    /** @test */
    public function it_can_generate_controller_with_route_model_binding(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'Post',
                '--route-binding' => 'slug',
            ])
            ->expectsOutput('Controller PostController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('Post');
        $content = File::get($controllerPath);
        
        $this->assertStringContainsString('Route::bind(\'post\', function ($value) {', $content);
        $this->assertStringContainsString('return Post::where(\'slug\', $value)->firstOrFail();', $content);
    }

    /** @test */
    public function it_shows_progress_when_generating_multiple_files(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--validation' => true,
                '--policy' => true,
                '--resources' => true,
            ])
            ->expectsOutputToContain('Generating form request classes...')
            ->expectsOutputToContain('Generating policy class...')
            ->expectsOutputToContain('Generating API resource classes...')
            ->expectsOutputToContain('Progress:')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_dry_run_controller_generation(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--dry-run' => true,
            ])
            ->expectsOutput('Dry run mode - no files will be created')
            ->expectsOutput('Would generate: UserController')
            ->expectsOutputToContain('Target path:')
            ->assertExitCode(0);

        // Verify no file was actually created
        $this->assertFileDoesNotExist($this->getControllerPath('User'));
    }

    /** @test */
    public function it_shows_detailed_output_in_verbose_mode(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '-v' => true,
            ])
            ->expectsOutputToContain('Analyzing model structure...')
            ->expectsOutputToContain('Generating controller methods...')
            ->expectsOutputToContain('Writing controller file...')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_generate_controller_with_soft_deletes_support(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'Post',
                '--soft-deletes' => true,
            ])
            ->expectsOutput('Controller PostController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('Post');
        $content = File::get($controllerPath);
        
        $this->assertStringContainsString('public function restore(Post $post)', $content);
        $this->assertStringContainsString('public function forceDelete(Post $post)', $content);
        $this->assertStringContainsString('$post->restore()', $content);
        $this->assertStringContainsString('$post->forceDelete()', $content);
    }

    /** @test */
    public function it_can_generate_controller_with_custom_base_controller(): void
    {
        $this->artisan('autogen:controller', [
                'model' => 'User',
                '--base-controller' => 'App\\Http\\Controllers\\BaseApiController',
            ])
            ->expectsOutput('Controller UserController generated successfully!')
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('User');
        $content = File::get($controllerPath);
        
        $this->assertStringContainsString('use App\\Http\\Controllers\\BaseApiController', $content);
        $this->assertStringContainsString('class UserController extends BaseApiController', $content);
    }

    /** @test */
    public function it_generates_appropriate_imports_for_controller_type(): void
    {
        // Test API controller imports
        $this->artisan('autogen:controller', [
                'model' => 'Post',
                '--type' => 'api',
                '--resources' => true,
            ])
            ->assertExitCode(0);

        $controllerPath = $this->getControllerPath('Post', 'api');
        $content = File::get($controllerPath);
        
        $this->assertStringContainsString('use Illuminate\\Http\\JsonResponse', $content);
        $this->assertStringContainsString('use App\\Http\\Resources\\PostResource', $content);
    }
}