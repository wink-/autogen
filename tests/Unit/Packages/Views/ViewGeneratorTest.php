<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Views;

use AutoGen\Packages\Views\Generators\ViewGenerator;
use AutoGen\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Mockery;

class ViewGeneratorTest extends TestCase
{
    /** @test */
    public function it_can_generate_basic_views_with_bootstrap(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();
        $mockCommand->shouldReceive('warn')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['index', 'create', 'edit', 'show'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/user');
        $this->assertDirectoryExists($viewPath);
        $this->assertFileExists("{$viewPath}/index.blade.php");
        $this->assertFileExists("{$viewPath}/create.blade.php");
        $this->assertFileExists("{$viewPath}/edit.blade.php");
        $this->assertFileExists("{$viewPath}/show.blade.php");
    }

    /** @test */
    public function it_can_generate_views_with_tailwind_framework(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'tailwind',
            layout: 'app',
            viewsToGenerate: ['index', 'form'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/user');
        $indexContent = File::get("{$viewPath}/index.blade.php");
        $formContent = File::get("{$viewPath}/_form.blade.php");

        // Check for Tailwind-specific classes
        $this->assertStringContainsString('bg-white', $indexContent);
        $this->assertStringContainsString('shadow-md', $indexContent);
        $this->assertStringContainsString('rounded-lg', $indexContent);
        $this->assertStringContainsString('px-4', $formContent);
        $this->assertStringContainsString('py-2', $formContent);
    }

    /** @test */
    public function it_can_generate_views_with_plain_css(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();
        $mockCommand->shouldReceive('comment')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'css',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/user');
        $cssPath = public_path('css/autogen');
        
        $this->assertFileExists("{$viewPath}/index.blade.php");
        $this->assertDirectoryExists($cssPath);
        $this->assertFileExists("{$cssPath}/crud.css");
    }

    /** @test */
    public function it_handles_nested_model_names(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'Admin/User',
            framework: 'bootstrap',
            layout: 'admin',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/admin/user');
        $this->assertDirectoryExists($viewPath);
        $this->assertFileExists("{$viewPath}/index.blade.php");
    }

    /** @test */
    public function it_can_generate_views_with_datatable_support(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: true,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/user');
        $indexContent = File::get("{$viewPath}/index.blade.php");

        $this->assertStringContainsString('DataTable', $indexContent);
        $this->assertStringContainsString('jquery.dataTables', $indexContent);
    }

    /** @test */
    public function it_can_generate_views_with_search_functionality(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: true,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/user');
        $this->assertFileExists("{$viewPath}/index.blade.php");
        $this->assertFileExists("{$viewPath}/_filters.blade.php");

        $filtersContent = File::get("{$viewPath}/_filters.blade.php");
        $this->assertStringContainsString('form', $filtersContent);
        $this->assertStringContainsString('input', $filtersContent);
        $this->assertStringContainsString('Search', $filtersContent);
    }

    /** @test */
    public function it_can_generate_views_with_modal_support(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: false,
            withModals: true,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/user');
        $indexContent = File::get("{$viewPath}/index.blade.php");

        $this->assertStringContainsString('modal', $indexContent);
        $this->assertStringContainsString('data-toggle="modal"', $indexContent);
    }

    /** @test */
    public function it_respects_force_flag_when_overwriting_files(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();
        $mockCommand->shouldReceive('warn')->andReturnSelf();

        // First generation
        $generator1 = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator1->generate();

        $viewPath = resource_path('views/user');
        $indexPath = "{$viewPath}/index.blade.php";
        
        // Modify the file
        File::put($indexPath, '<!-- Modified content -->');
        $originalContent = File::get($indexPath);

        // Second generation without force
        $generator2 = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: false,
            output: $mockCommand
        );

        $generator2->generate();

        // Content should remain unchanged
        $this->assertEquals($originalContent, File::get($indexPath));
    }

    /** @test */
    public function it_throws_exception_for_unknown_framework(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown framework: unknown');

        new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'unknown',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );
    }

    /** @test */
    public function it_generates_correct_view_structure_for_bootstrap(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['create', 'form'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/user');
        $createContent = File::get("{$viewPath}/create.blade.php");
        $formContent = File::get("{$viewPath}/_form.blade.php");

        // Check create view structure
        $this->assertStringContainsString('@extends(\'app\')', $createContent);
        $this->assertStringContainsString('@section(\'content\')', $createContent);
        $this->assertStringContainsString('@include(\'user._form\')', $createContent);

        // Check form structure
        $this->assertStringContainsString('<form', $formContent);
        $this->assertStringContainsString('form-group', $formContent);
        $this->assertStringContainsString('btn btn-primary', $formContent);
    }

    /** @test */
    public function it_generates_appropriate_route_names(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'Admin/BlogPost',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/admin/blog_post');
        $indexContent = File::get("{$viewPath}/index.blade.php");

        $this->assertStringContainsString('admin.blog-posts', $indexContent);
    }

    /** @test */
    public function it_creates_directory_structure_when_missing(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $viewPath = resource_path('views/deep/nested/model');
        
        // Ensure directory doesn't exist
        if (File::exists($viewPath)) {
            File::deleteDirectory($viewPath);
        }

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'Deep/Nested/Model',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: ['index'],
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $this->assertDirectoryExists($viewPath);
        $this->assertFileExists("{$viewPath}/index.blade.php");
    }

    /** @test */
    public function it_generates_all_views_when_none_specified(): void
    {
        $mockCommand = Mockery::mock('Illuminate\Console\Command');
        $mockCommand->shouldReceive('info')->andReturnSelf();

        $generator = new ViewGenerator(
            modelClass: 'Illuminate\Foundation\Auth\User',
            modelName: 'User',
            framework: 'bootstrap',
            layout: 'app',
            viewsToGenerate: [], // Empty array should generate all views
            withDatatable: false,
            withSearch: false,
            withModals: false,
            force: true,
            output: $mockCommand
        );

        $generator->generate();

        $viewPath = resource_path('views/user');
        $this->assertFileExists("{$viewPath}/index.blade.php");
        $this->assertFileExists("{$viewPath}/create.blade.php");
        $this->assertFileExists("{$viewPath}/edit.blade.php");
        $this->assertFileExists("{$viewPath}/show.blade.php");
        $this->assertFileExists("{$viewPath}/_form.blade.php");
        $this->assertFileExists("{$viewPath}/_table.blade.php");
    }

    protected function tearDown(): void
    {
        // Clean up generated views
        $viewPaths = [
            resource_path('views/user'),
            resource_path('views/admin'),
            resource_path('views/deep'),
            public_path('css/autogen'),
        ];

        foreach ($viewPaths as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }

        parent::tearDown();
    }
}