<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Controller;

use AutoGen\Packages\Controller\ControllerGenerator;
use AutoGen\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ControllerGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up any existing test files
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        // Clean up test files after each test
        $this->cleanupTestFiles();
        
        parent::tearDown();
    }

    /** @test */
    public function it_can_generate_a_resource_controller(): void
    {
        $generator = new ControllerGenerator(
            modelName: 'User',
            controllerType: 'resource',
            withValidation: false,
            withPolicy: false,
            paginate: 15,
            force: true
        );

        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertFileExists($files[0]);
        $this->assertStringContainsString('UserController', file_get_contents($files[0]));
        $this->assertStringContainsString('extends Controller', file_get_contents($files[0]));
    }

    /** @test */
    public function it_can_generate_an_api_controller(): void
    {
        $generator = new ControllerGenerator(
            modelName: 'User',
            controllerType: 'api',
            withValidation: false,
            withPolicy: false,
            paginate: 20,
            force: true
        );

        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertFileExists($files[0]);
        
        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('UserController', $content);
        $this->assertStringContainsString('JsonResponse', $content);
        $this->assertStringContainsString('JsonResource', $content);
    }

    /** @test */
    public function it_can_generate_controller_with_validation(): void
    {
        $generator = new ControllerGenerator(
            modelName: 'User',
            controllerType: 'resource',
            withValidation: true,
            withPolicy: false,
            paginate: 15,
            force: true
        );

        $files = $generator->generate();

        $this->assertCount(3, $files); // Controller + 2 request classes
        
        // Check controller exists
        $controllerFile = array_filter($files, fn($file) => str_contains($file, 'Controller.php'));
        $this->assertCount(1, $controllerFile);
        
        // Check request files exist
        $requestFiles = array_filter($files, fn($file) => str_contains($file, 'Request.php'));
        $this->assertCount(2, $requestFiles);
    }

    /** @test */
    public function it_can_generate_controller_with_policy(): void
    {
        $generator = new ControllerGenerator(
            modelName: 'User',
            controllerType: 'resource',
            withValidation: false,
            withPolicy: true,
            paginate: 15,
            force: true
        );

        $files = $generator->generate();

        $this->assertCount(2, $files); // Controller + Policy
        
        // Check policy file exists
        $policyFile = array_filter($files, fn($file) => str_contains($file, 'Policy.php'));
        $this->assertCount(1, $policyFile);
        
        $policyContent = file_get_contents(array_values($policyFile)[0]);
        $this->assertStringContainsString('UserPolicy', $policyContent);
        $this->assertStringContainsString('viewAny', $policyContent);
        $this->assertStringContainsString('create', $policyContent);
        $this->assertStringContainsString('update', $policyContent);
        $this->assertStringContainsString('delete', $policyContent);
    }

    /** @test */
    public function it_handles_nested_model_names(): void
    {
        $generator = new ControllerGenerator(
            modelName: 'Admin/User',
            controllerType: 'resource',
            withValidation: false,
            withPolicy: false,
            paginate: 15,
            force: true
        );

        $files = $generator->generate();

        $this->assertCount(1, $files);
        $this->assertStringContainsString('Admin', $files[0]);
        
        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('namespace App\Http\Controllers\Admin', $content);
        $this->assertStringContainsString('use App\Models\Admin\User', $content);
    }

    /** @test */
    public function it_throws_exception_when_file_exists_and_force_is_false(): void
    {
        // Create a dummy file first
        $controllerPath = app_path('Http/Controllers/UserController.php');
        File::ensureDirectoryExists(dirname($controllerPath));
        File::put($controllerPath, '<?php // dummy file');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Controller already exists/');

        $generator = new ControllerGenerator(
            modelName: 'User',
            controllerType: 'resource',
            withValidation: false,
            withPolicy: false,
            paginate: 15,
            force: false
        );

        $generator->generate();
    }

    protected function cleanupTestFiles(): void
    {
        $paths = [
            app_path('Http/Controllers/UserController.php'),
            app_path('Http/Controllers/Api/UserController.php'),
            app_path('Http/Controllers/Admin/UserController.php'),
            app_path('Http/Controllers/Api/Admin/UserController.php'),
            app_path('Http/Requests/StoreUserRequest.php'),
            app_path('Http/Requests/UpdateUserRequest.php'),
            app_path('Http/Requests/Admin/StoreUserRequest.php'),
            app_path('Http/Requests/Admin/UpdateUserRequest.php'),
            app_path('Policies/UserPolicy.php'),
            app_path('Policies/Admin/UserPolicy.php'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        // Clean up empty directories
        $directories = [
            app_path('Http/Controllers/Admin'),
            app_path('Http/Controllers/Api/Admin'),
            app_path('Http/Requests/Admin'),
            app_path('Policies/Admin'),
        ];

        foreach ($directories as $directory) {
            if (File::exists($directory) && count(File::files($directory)) === 0) {
                File::deleteDirectory($directory);
            }
        }
    }
}