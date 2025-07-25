<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable\Tests;

use AutoGen\Packages\Datatable\DatatableGenerator;
use AutoGen\Packages\Datatable\YajraDatatableGenerator;
use AutoGen\Packages\Datatable\LivewireDatatableGenerator;
use AutoGen\Packages\Datatable\InertiaDatatableGenerator;
use AutoGen\Packages\Datatable\ApiDatatableGenerator;
use AutoGen\Packages\Datatable\ExportGenerator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatatableGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up any test files
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        // Clean up test files after each test
        $this->cleanupTestFiles();
        
        parent::tearDown();
    }

    /** @test */
    public function it_can_generate_yajra_datatable()
    {
        $generator = new DatatableGenerator(
            'User',
            'yajra',
            false, // withExports
            false, // withSearch
            false, // withBulk
            false, // cache
            false, // virtualScroll
            false, // cursorPagination
            false, // backgroundJobs
            true   // force
        );

        $files = $generator->generate();

        $this->assertNotEmpty($files);
        $this->assertContains(app_path('DataTables/UserDataTable.php'), $files);
    }

    /** @test */
    public function it_can_generate_livewire_datatable()
    {
        $generator = new DatatableGenerator(
            'User',
            'livewire',
            false, // withExports
            true,  // withSearch
            false, // withBulk
            false, // cache
            false, // virtualScroll
            false, // cursorPagination
            false, // backgroundJobs
            true   // force
        );

        $files = $generator->generate();

        $this->assertNotEmpty($files);
        $this->assertContains(app_path('Http/Livewire/UserDatatable.php'), $files);
        $this->assertContains(resource_path('views/livewire/user-datatable.blade.php'), $files);
    }

    /** @test */
    public function it_can_generate_inertia_datatable()
    {
        $generator = new DatatableGenerator(
            'User',
            'inertia',
            true,  // withExports
            true,  // withSearch
            false, // withBulk
            false, // cache
            false, // virtualScroll
            false, // cursorPagination
            false, // backgroundJobs
            true   // force
        );

        $files = $generator->generate();

        $this->assertNotEmpty($files);
        $this->assertContains(app_path('Http/Controllers/UserController.php'), $files);
        $this->assertContains(resource_path('js/Pages/User/Index.vue'), $files);
        $this->assertContains(resource_path('js/Composables/useUsersDatatable.js'), $files);
    }

    /** @test */
    public function it_can_generate_api_datatable()
    {
        $generator = new DatatableGenerator(
            'User',
            'api',
            true,  // withExports
            true,  // withSearch
            true,  // withBulk
            true,  // cache
            false, // virtualScroll
            false, // cursorPagination
            false, // backgroundJobs
            true   // force
        );

        $files = $generator->generate();

        $this->assertNotEmpty($files);
        $this->assertContains(app_path('Http/Controllers/Api/UserController.php'), $files);
        $this->assertContains(app_path('Http/Resources/UserResource.php'), $files);
        $this->assertContains(base_path('docs/api/user.md'), $files);
    }

    /** @test */
    public function it_can_generate_export_files()
    {
        $exportGenerator = new ExportGenerator(
            'User',
            [
                'withExports' => true,
                'backgroundJobs' => true,
                'force' => true
            ]
        );

        $files = $exportGenerator->generate();

        $this->assertNotEmpty($files);
        $this->assertContains(app_path('Exports/UserExport.php'), $files);
        $this->assertContains(app_path('Jobs/ExportUserJob.php'), $files);
        $this->assertContains(app_path('Notifications/UserExportCompleted.php'), $files);
    }

    /** @test */
    public function it_validates_datatable_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported datatable type: invalid');

        $generator = new DatatableGenerator(
            'User',
            'invalid', // invalid type
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            true
        );

        $generator->generate();
    }

    /** @test */
    public function it_generates_correct_namespaces_for_nested_models()
    {
        $generator = new YajraDatatableGenerator(
            'Admin/User',
            ['force' => true]
        );

        $files = $generator->generate();
        
        $this->assertNotEmpty($files);
        
        // Check if the file exists in the correct nested directory
        $expectedPath = app_path('DataTables/Admin/UserDataTable.php');
        $this->assertContains($expectedPath, $files);
        
        if (File::exists($expectedPath)) {
            $content = File::get($expectedPath);
            $this->assertStringContains('namespace App\\DataTables\\Admin;', $content);
            $this->assertStringContains('use App\\Models\\Admin\\User;', $content);
        }
    }

    /** @test */
    public function it_generates_all_required_options()
    {
        $generator = new DatatableGenerator(
            'User',
            'yajra',
            true,  // withExports
            true,  // withSearch
            true,  // withBulk
            true,  // cache
            true,  // virtualScroll
            true,  // cursorPagination
            true,  // backgroundJobs
            true   // force
        );

        $files = $generator->generate();

        // Should generate datatable, controller methods, views, exports, etc.
        $this->assertGreaterThan(3, count($files));
        
        // Should include export files
        $this->assertContains(app_path('Exports/UserExport.php'), $files);
        $this->assertContains(app_path('Jobs/ExportUserJob.php'), $files);
        $this->assertContains(app_path('Notifications/UserExportCompleted.php'), $files);
    }

    /** @test */
    public function it_respects_force_option()
    {
        // First generation
        $generator1 = new DatatableGenerator(
            'User',
            'yajra',
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            true // force
        );

        $files1 = $generator1->generate();
        $this->assertNotEmpty($files1);

        // Second generation without force should throw exception
        $generator2 = new DatatableGenerator(
            'User',
            'yajra',
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false // no force
        );

        $this->expectException(\Exception::class);
        $generator2->generate();
    }

    protected function cleanupTestFiles(): void
    {
        $filesToClean = [
            app_path('DataTables/UserDataTable.php'),
            app_path('DataTables/Admin/UserDataTable.php'),
            app_path('Http/Livewire/UserDatatable.php'),
            app_path('Http/Controllers/UserController.php'),
            app_path('Http/Controllers/Api/UserController.php'),
            app_path('Http/Resources/UserResource.php'),
            app_path('Exports/UserExport.php'),
            app_path('Jobs/ExportUserJob.php'),
            app_path('Notifications/UserExportCompleted.php'),
            resource_path('views/livewire/user-datatable.blade.php'),
            resource_path('views/users/index.blade.php'),
            resource_path('js/Pages/User/Index.vue'),
            resource_path('js/Composables/useUsersDatatable.js'),
            resource_path('js/datatables/users.js'),
            base_path('docs/api/user.md'),
        ];

        foreach ($filesToClean as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up directories if empty
        $dirsToClean = [
            app_path('DataTables/Admin'),
            app_path('DataTables'),
            app_path('Http/Livewire'),
            app_path('Exports'),
            app_path('Jobs'),
            app_path('Notifications'),
            resource_path('views/livewire'),
            resource_path('views/users'),
            resource_path('js/Pages/User'),
            resource_path('js/Composables'),
            resource_path('js/datatables'),
            base_path('docs/api'),
            base_path('docs'),
        ];

        foreach ($dirsToClean as $dir) {
            if (File::isDirectory($dir) && count(File::files($dir)) === 0) {
                File::deleteDirectory($dir);
            }
        }
    }
}