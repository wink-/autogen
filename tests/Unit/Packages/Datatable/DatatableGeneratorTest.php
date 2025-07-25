<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Packages\Datatable;

use AutoGen\Packages\Datatable\DatatableGenerator;
use AutoGen\Tests\TestCase;
use Illuminate\Support\Facades\File;

class DatatableGeneratorTest extends TestCase
{
    private DatatableGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DatatableGenerator();
    }

    /** @test */
    public function it_can_generate_yajra_datatable(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email', 'created_at'],
            'searchable' => ['name', 'email'],
            'sortable' => ['name', 'email', 'created_at'],
            'with_export' => false,
        ];

        $files = $this->generator->generate($config);

        $this->assertCount(1, $files);
        $this->assertFileExists($files[0]);
        
        $content = File::get($files[0]);
        $this->assertStringContainsString('class UserDataTable extends DataTable', $content);
        $this->assertStringContainsString('use Yajra\DataTables\Services\DataTable', $content);
        $this->assertStringContainsString('public function dataTable', $content);
    }

    /** @test */
    public function it_can_generate_livewire_datatable(): void
    {
        $config = [
            'type' => 'livewire',
            'model' => 'Post',
            'columns' => ['id', 'title', 'status', 'created_at'],
            'searchable' => ['title'],
            'sortable' => ['title', 'created_at'],
            'with_filters' => true,
        ];

        $files = $this->generator->generate($config);

        $this->assertGreaterThan(1, count($files));
        
        // Check component file
        $componentFile = collect($files)->first(fn($file) => str_contains($file, 'PostDatatable.php'));
        $this->assertNotNull($componentFile);
        
        $content = File::get($componentFile);
        $this->assertStringContainsString('class PostDatatable extends Component', $content);
        $this->assertStringContainsString('use Livewire\Component', $content);
        $this->assertStringContainsString('public function render()', $content);
    }

    /** @test */
    public function it_can_generate_inertia_datatable(): void
    {
        $config = [
            'type' => 'inertia',
            'model' => 'Category',
            'columns' => ['id', 'name', 'slug', 'is_active'],
            'searchable' => ['name', 'slug'],
            'with_pagination' => true,
        ];

        $files = $this->generator->generate($config);

        $this->assertGreaterThan(1, count($files));
        
        // Check controller file
        $controllerFile = collect($files)->first(fn($file) => str_contains($file, 'Controller.php'));
        $this->assertNotNull($controllerFile);
        
        $content = File::get($controllerFile);
        $this->assertStringContainsString('use Inertia\Inertia', $content);
        $this->assertStringContainsString('return Inertia::render', $content);
    }

    /** @test */
    public function it_can_generate_api_datatable(): void
    {
        $config = [
            'type' => 'api',
            'model' => 'Order',
            'columns' => ['id', 'customer_name', 'total', 'status', 'created_at'],
            'searchable' => ['customer_name'],
            'sortable' => ['total', 'created_at'],
            'with_filters' => true,
            'with_export' => true,
        ];

        $files = $this->generator->generate($config);

        $this->assertGreaterThan(1, count($files));
        
        // Check API controller
        $apiFile = collect($files)->first(fn($file) => str_contains($file, 'Api/'));
        $this->assertNotNull($apiFile);
        
        $content = File::get($apiFile);
        $this->assertStringContainsString('JsonResponse', $content);
        $this->assertStringContainsString('return response()->json', $content);
    }

    /** @test */
    public function it_generates_export_functionality_when_enabled(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email'],
            'with_export' => true,
            'export_formats' => ['excel', 'csv'],
        ];

        $files = $this->generator->generate($config);

        // Should generate export class
        $exportFile = collect($files)->first(fn($file) => str_contains($file, 'Export.php'));
        $this->assertNotNull($exportFile);
        
        $content = File::get($exportFile);
        $this->assertStringContainsString('class UserExport', $content);
        $this->assertStringContainsString('implements FromCollection', $content);
    }

    /** @test */
    public function it_generates_filter_functionality_when_enabled(): void
    {
        $config = [
            'type' => 'livewire',
            'model' => 'Product',
            'columns' => ['id', 'name', 'price', 'category_id'],
            'with_filters' => true,
            'filters' => [
                'category_id' => 'select',
                'price_range' => 'range',
                'is_active' => 'boolean',
            ],
        ];

        $files = $this->generator->generate($config);

        $componentFile = collect($files)->first(fn($file) => str_contains($file, 'ProductDatatable.php'));
        $content = File::get($componentFile);
        
        $this->assertStringContainsString('public $categoryFilter', $content);
        $this->assertStringContainsString('public $priceRangeFilter', $content);
        $this->assertStringContainsString('public $isActiveFilter', $content);
    }

    /** @test */
    public function it_handles_different_column_types_correctly(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'is_active' => ['type' => 'boolean'],
                'created_at' => ['type' => 'datetime'],
                'avatar' => ['type' => 'image'],
            ],
        ];

        $files = $this->generator->generate($config);
        $content = File::get($files[0]);

        $this->assertStringContainsString('editColumn(\'is_active\'', $content);
        $this->assertStringContainsString('editColumn(\'created_at\'', $content);
        $this->assertStringContainsString('editColumn(\'avatar\'', $content);
    }

    /** @test */
    public function it_generates_action_buttons_when_specified(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email'],
            'actions' => ['view', 'edit', 'delete'],
            'action_column' => true,
        ];

        $files = $this->generator->generate($config);
        $content = File::get($files[0]);

        $this->assertStringContainsString('addColumn(\'action\'', $content);
        $this->assertStringContainsString('btn-primary', $content); // View button
        $this->assertStringContainsString('btn-warning', $content); // Edit button
        $this->assertStringContainsString('btn-danger', $content);  // Delete button
    }

    /** @test */
    public function it_generates_bulk_actions_when_enabled(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email'],
            'bulk_actions' => ['delete', 'activate', 'deactivate'],
            'with_checkbox' => true,
        ];

        $files = $this->generator->generate($config);
        $content = File::get($files[0]);

        $this->assertStringContainsString('checkbox', $content);
        $this->assertStringContainsString('bulk-delete', $content);
        $this->assertStringContainsString('bulk-activate', $content);
    }

    /** @test */
    public function it_generates_relationship_columns(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'Post',
            'columns' => [
                'id',
                'title',
                'user.name' => ['relation' => 'user', 'column' => 'name'],
                'category.name' => ['relation' => 'category', 'column' => 'name'],
            ],
        ];

        $files = $this->generator->generate($config);
        $content = File::get($files[0]);

        $this->assertStringContainsString('->with(\'user\'', $content);
        $this->assertStringContainsString('->with(\'category\'', $content);
        $this->assertStringContainsString('editColumn(\'user.name\'', $content);
    }

    /** @test */
    public function it_generates_custom_css_and_js_when_needed(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email'],
            'custom_styling' => true,
            'theme' => 'dark',
        ];

        $files = $this->generator->generate($config);

        // Should generate CSS file
        $cssFile = collect($files)->first(fn($file) => str_contains($file, '.css'));
        $this->assertNotNull($cssFile);
        
        // Should generate JS file
        $jsFile = collect($files)->first(fn($file) => str_contains($file, '.js'));
        $this->assertNotNull($jsFile);
    }

    /** @test */
    public function it_handles_pagination_configuration(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email'],
            'pagination' => [
                'per_page' => 25,
                'show_info' => true,
                'show_length_menu' => true,
                'length_menu' => [10, 25, 50, 100],
            ],
        ];

        $files = $this->generator->generate($config);
        $content = File::get($files[0]);

        $this->assertStringContainsString('pageLength: 25', $content);
        $this->assertStringContainsString('lengthMenu', $content);
        $this->assertStringContainsString('[10, 25, 50, 100]', $content);
    }

    /** @test */
    public function it_validates_configuration_before_generation(): void
    {
        $invalidConfig = [
            'type' => 'invalid_type',
            'model' => '',
            'columns' => [],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generate($invalidConfig);
    }

    /** @test */
    public function it_generates_responsive_datatable(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email', 'created_at'],
            'responsive' => true,
            'mobile_columns' => ['name', 'email'],
        ];

        $files = $this->generator->generate($config);
        $content = File::get($files[0]);

        $this->assertStringContainsString('responsive: true', $content);
        $this->assertStringContainsString('responsivePriority', $content);
    }

    /** @test */
    public function it_generates_server_side_processing_configuration(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email'],
            'server_side' => true,
            'ajax_url' => '/admin/users/data',
        ];

        $files = $this->generator->generate($config);
        $content = File::get($files[0]);

        $this->assertStringContainsString('processing: true', $content);
        $this->assertStringContainsString('serverSide: true', $content);
        $this->assertStringContainsString('/admin/users/data', $content);
    }

    /** @test */
    public function it_generates_state_saving_functionality(): void
    {
        $config = [
            'type' => 'yajra',
            'model' => 'User',
            'columns' => ['id', 'name', 'email'],
            'state_save' => true,
            'state_duration' => 7200, // 2 hours
        ];

        $files = $this->generator->generate($config);
        $content = File::get($files[0]);

        $this->assertStringContainsString('stateSave: true', $content);
        $this->assertStringContainsString('stateDuration: 7200', $content);
    }

    protected function tearDown(): void
    {
        // Clean up generated datatable files
        $paths = [
            app_path('DataTables'),
            app_path('Http/Livewire'),
            app_path('Exports'),
            resource_path('js/datatables'),
            resource_path('css/datatables'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }

        parent::tearDown();
    }
}