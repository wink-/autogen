<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DatatableGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autogen:datatable 
                            {model : The model name (e.g., User or Admin/User)}
                            {--type=yajra : Type of datatable (yajra|livewire|inertia|api)}
                            {--with-exports : Include Excel, CSV, PDF export functionality}
                            {--with-search : Include advanced search and filtering}
                            {--with-bulk : Include bulk operations}
                            {--cache : Enable Redis caching for performance}
                            {--virtual-scroll : Enable virtual scrolling for large datasets}
                            {--cursor-pagination : Use cursor-based pagination instead of offset}
                            {--background-jobs : Process exports via background jobs}
                            {--force : Overwrite existing datatable files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate high-performance datatable implementations for large datasets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = $this->argument('model');
        $type = $this->option('type');
        $withExports = $this->option('with-exports');
        $withSearch = $this->option('with-search');
        $withBulk = $this->option('with-bulk');
        $cache = $this->option('cache');
        $virtualScroll = $this->option('virtual-scroll');
        $cursorPagination = $this->option('cursor-pagination');
        $backgroundJobs = $this->option('background-jobs');
        $force = $this->option('force');

        // Validate datatable type
        if (!in_array($type, ['yajra', 'livewire', 'inertia', 'api'])) {
            $this->error("Invalid datatable type: {$type}. Supported types: yajra, livewire, inertia, api");
            return Command::FAILURE;
        }

        $this->info("Generating {$type} datatable for model: {$modelName}");

        try {
            $generator = new DatatableGenerator(
                $modelName,
                $type,
                $withExports,
                $withSearch,
                $withBulk,
                $cache,
                $virtualScroll,
                $cursorPagination,
                $backgroundJobs,
                $force
            );

            $files = $generator->generate();

            foreach ($files as $file) {
                $this->info("✓ Created: {$file}");
            }

            $this->newLine();
            $this->info('Datatable generation completed successfully!');

            if ($withExports) {
                $this->info('Export functionality has been included (Excel, CSV, PDF).');
                if ($backgroundJobs) {
                    $this->warn('Background job processing enabled. Make sure queue worker is running.');
                }
            }

            if ($cache) {
                $this->info('Redis caching enabled for improved performance.');
                $this->warn('Make sure Redis is configured in your .env file.');
            }

            if ($virtualScroll) {
                $this->info('Virtual scrolling enabled for handling large datasets.');
            }

            if ($cursorPagination) {
                $this->info('Cursor-based pagination enabled for better performance on large datasets.');
            }

            $this->displayNextSteps($modelName, $type);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate datatable: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display next steps after generation.
     */
    protected function displayNextSteps(string $modelName, string $type): void
    {
        $this->newLine();
        $this->info('Next steps:');
        
        $datatableName = Str::studly($modelName) . 'DataTable';
        $routePrefix = Str::plural(Str::kebab(class_basename($modelName)));
        
        switch ($type) {
            case 'yajra':
                $this->line("1. Add routes to routes/web.php:");
                $this->line("   Route::get('/{$routePrefix}', [\\App\\Http\\Controllers\\{$modelName}Controller::class, 'index'])->name('{$routePrefix}.index');");
                $this->line("   Route::post('/{$routePrefix}/data', [\\App\\DataTables\\{$datatableName}::class, 'ajax'])->name('{$routePrefix}.data');");
                break;
                
            case 'livewire':
                $this->line("1. Include the Livewire component in your Blade view:");
                $this->line("   <livewire:{$routePrefix}-datatable />");
                break;
                
            case 'inertia':
                $this->line("1. Add routes to routes/web.php:");
                $this->line("   Route::get('/{$routePrefix}', [\\App\\Http\\Controllers\\{$modelName}Controller::class, 'index'])->name('{$routePrefix}.index');");
                $this->line("   Route::get('/{$routePrefix}/data', [\\App\\Http\\Controllers\\{$modelName}Controller::class, 'data'])->name('{$routePrefix}.data');");
                break;
                
            case 'api':
                $this->line("1. Add routes to routes/api.php:");
                $this->line("   Route::get('/{$routePrefix}', [\\App\\Http\\Controllers\\Api\\{$modelName}Controller::class, 'index']);");
                $this->line("   Route::get('/{$routePrefix}/data', [\\App\\Http\\Controllers\\Api\\{$modelName}Controller::class, 'data']);");
                break;
        }

        if ($this->option('with-exports')) {
            $this->line("2. Export endpoints are available:");
            $this->line("   GET /{$routePrefix}/export/{excel|csv|pdf}");
        }

        if ($this->option('cache')) {
            $this->line("3. Configure Redis in your .env file:");
            $this->line("   REDIS_HOST=127.0.0.1");
            $this->line("   REDIS_PASSWORD=null");
            $this->line("   REDIS_PORT=6379");
        }

        if ($this->option('background-jobs')) {
            $this->line("4. Start queue worker for background exports:");
            $this->line("   php artisan queue:work");
        }

        $this->line("5. Consider adding database indexes for better performance:");
        $this->line("   php artisan make:migration add_indexes_to_{$routePrefix}_table");
    }
}