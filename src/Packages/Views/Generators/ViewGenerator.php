<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views\Generators;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use AutoGen\Packages\Views\Generators\TailwindGenerator;
use AutoGen\Packages\Views\Generators\BootstrapGenerator;
use AutoGen\Packages\Views\Generators\PlainCssGenerator;

class ViewGenerator
{
    protected Model $modelInstance;
    protected array $fillableFields;
    protected string $tableName;
    protected string $primaryKey;
    protected string $viewPath;
    protected string $routeName;
    protected string $modelBaseName;
    protected string $modelNamespace;
    protected BaseFrameworkGenerator $frameworkGenerator;

    public function __construct(
        protected string $modelClass,
        protected string $modelName,
        protected string $framework,
        protected string $layout,
        protected array $viewsToGenerate,
        protected bool $withDatatable,
        protected bool $withSearch,
        protected bool $withModals,
        protected bool $force,
        protected Command $output
    ) {
        $this->initializeModel();
        $this->initializeFrameworkGenerator();
    }

    protected function initializeModel(): void
    {
        $this->modelInstance = new $this->modelClass();
        $this->fillableFields = $this->modelInstance->getFillable();
        $this->tableName = $this->modelInstance->getTable();
        $this->primaryKey = $this->modelInstance->getKeyName();
        
        // Parse model name for paths
        $parts = explode('/', $this->modelName);
        $this->modelBaseName = array_pop($parts);
        $this->modelNamespace = implode('\\', $parts);
        
        // Set view path
        $viewFolder = Str::snake($this->modelBaseName);
        if (!empty($parts)) {
            $viewFolder = Str::snake(implode('/', $parts)) . '/' . $viewFolder;
        }
        $this->viewPath = resource_path("views/{$viewFolder}");
        
        // Set route name
        $this->routeName = Str::plural(Str::snake($this->modelBaseName, '-'));
        if (!empty($parts)) {
            $this->routeName = Str::snake(implode('-', $parts), '-') . '.' . $this->routeName;
        }
    }

    protected function initializeFrameworkGenerator(): void
    {
        $generatorClass = match ($this->framework) {
            'tailwind' => TailwindGenerator::class,
            'bootstrap' => BootstrapGenerator::class,
            'css' => PlainCssGenerator::class,
            default => throw new \InvalidArgumentException("Unknown framework: {$this->framework}")
        };

        $this->frameworkGenerator = new $generatorClass(
            $this->modelInstance,
            $this->modelName,
            $this->modelBaseName,
            $this->routeName,
            $this->layout,
            $this->withDatatable,
            $this->withSearch,
            $this->withModals
        );
    }

    public function generate(): void
    {
        // Create view directory
        if (!File::isDirectory($this->viewPath)) {
            File::makeDirectory($this->viewPath, 0755, true);
            $this->output->info("Created directory: {$this->viewPath}");
        }

        // Determine which views to generate
        $viewsToCreate = empty($this->viewsToGenerate) 
            ? ['index', 'create', 'edit', 'show', 'form', 'table'] 
            : $this->viewsToGenerate;

        if ($this->withSearch && (empty($this->viewsToGenerate) || in_array('filters', $this->viewsToGenerate))) {
            $viewsToCreate[] = 'filters';
        }

        // Generate each view
        foreach ($viewsToCreate as $view) {
            $this->generateView($view);
        }

        // Generate additional assets if needed
        if ($this->framework === 'css') {
            $this->generateCssAssets();
        }
    }

    protected function generateView(string $viewType): void
    {
        $filename = match ($viewType) {
            'form' => '_form.blade.php',
            'table' => '_table.blade.php',
            'filters' => '_filters.blade.php',
            default => "{$viewType}.blade.php"
        };

        $filepath = "{$this->viewPath}/{$filename}";

        if (File::exists($filepath) && !$this->force) {
            $this->output->warn("View already exists: {$filename} (use --force to overwrite)");
            return;
        }

        $content = $this->frameworkGenerator->generateView($viewType);
        
        File::put($filepath, $content);
        $this->output->info("Generated view: {$filename}");
    }

    protected function generateCssAssets(): void
    {
        $cssPath = public_path('css/autogen');
        
        if (!File::isDirectory($cssPath)) {
            File::makeDirectory($cssPath, 0755, true);
        }

        $cssContent = $this->frameworkGenerator->generateCss();
        $cssFile = "{$cssPath}/crud.css";
        
        File::put($cssFile, $cssContent);
        $this->output->info("Generated CSS: css/autogen/crud.css");
        $this->output->comment("Don't forget to include the CSS in your layout: <link href=\"/css/autogen/crud.css\" rel=\"stylesheet\">");
    }
}