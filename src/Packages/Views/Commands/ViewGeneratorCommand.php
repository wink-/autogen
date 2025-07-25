<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use AutoGen\Packages\Views\Generators\ViewGenerator;

class ViewGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autogen:views {model : The model name (e.g., User or Admin/User)}
                            {--framework=tailwind : CSS framework to use (tailwind, bootstrap, css)}
                            {--layout=layouts.app : Master layout file to extend}
                            {--only= : Comma-separated list of views to generate}
                            {--with-datatable : Include DataTable integration}
                            {--with-search : Include search/filter functionality}
                            {--with-modals : Use modal dialogs for confirmations}
                            {--force : Overwrite existing view files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD views for a model';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = $this->argument('model');
        $framework = $this->option('framework');
        $layout = $this->option('layout');
        $only = $this->option('only');
        $withDatatable = $this->option('with-datatable');
        $withSearch = $this->option('with-search');
        $withModals = $this->option('with-modals');
        $force = $this->option('force');

        // Validate framework option
        if (!in_array($framework, ['tailwind', 'bootstrap', 'css'])) {
            $this->error("Invalid framework: {$framework}. Must be one of: tailwind, bootstrap, css");
            return Command::FAILURE;
        }

        // Parse model path
        $modelPath = str_replace('/', '\\', $modelName);
        $modelClass = "App\\Models\\{$modelPath}";
        
        // Check if model exists
        if (!class_exists($modelClass)) {
            $this->error("Model {$modelClass} does not exist.");
            $this->line('Please run `php artisan autogen:model` first to generate the model.');
            return Command::FAILURE;
        }

        // Parse only option
        $viewsToGenerate = [];
        if ($only) {
            $viewsToGenerate = array_map('trim', explode(',', $only));
            $validViews = ['index', 'create', 'edit', 'show', 'form', 'table', 'filters'];
            
            foreach ($viewsToGenerate as $view) {
                if (!in_array($view, $validViews)) {
                    $this->error("Invalid view type: {$view}. Valid types are: " . implode(', ', $validViews));
                    return Command::FAILURE;
                }
            }
        }

        $this->info("Generating views for model: {$modelName}");
        $this->info("Framework: {$framework}");
        $this->info("Layout: {$layout}");

        try {
            $generator = new ViewGenerator(
                modelClass: $modelClass,
                modelName: $modelName,
                framework: $framework,
                layout: $layout,
                viewsToGenerate: $viewsToGenerate,
                withDatatable: $withDatatable,
                withSearch: $withSearch,
                withModals: $withModals,
                force: $force,
                output: $this
            );

            $generator->generate();

            $this->newLine();
            $this->info('Views generated successfully!');
            
            // Show next steps
            $this->newLine();
            $this->comment('Next steps:');
            $this->comment('1. Add routes for your resource:');
            $this->line("   Route::resource('" . Str::plural(Str::snake(class_basename($modelName))) . "', {$modelName}Controller::class);");
            $this->comment('2. Run `npm run dev` or `npm run build` to compile your assets');
            
            if ($withDatatable) {
                $this->comment('3. Install DataTables dependencies if not already installed');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate views: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}