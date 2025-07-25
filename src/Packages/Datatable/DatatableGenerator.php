<?php

declare(strict_types=1);

namespace AutoGen\Packages\Datatable;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DatatableGenerator
{
    protected string $modelName;
    protected string $type;
    protected bool $withExports;
    protected bool $withSearch;
    protected bool $withBulk;
    protected bool $cache;
    protected bool $virtualScroll;
    protected bool $cursorPagination;
    protected bool $backgroundJobs;
    protected bool $force;
    protected array $generatedFiles = [];

    public function __construct(
        string $modelName,
        string $type,
        bool $withExports = false,
        bool $withSearch = false,
        bool $withBulk = false,
        bool $cache = false,
        bool $virtualScroll = false,
        bool $cursorPagination = false,
        bool $backgroundJobs = false,
        bool $force = false
    ) {
        $this->modelName = $modelName;
        $this->type = $type;
        $this->withExports = $withExports;
        $this->withSearch = $withSearch;
        $this->withBulk = $withBulk;
        $this->cache = $cache;
        $this->virtualScroll = $virtualScroll;
        $this->cursorPagination = $cursorPagination;
        $this->backgroundJobs = $backgroundJobs;
        $this->force = $force;
    }

    /**
     * Generate all required files.
     */
    public function generate(): array
    {
        // Generate the appropriate datatable implementation
        $this->generateDatatable();

        // Generate export functionality if requested
        if ($this->withExports) {
            $this->generateExports();
        }

        // Generate controller methods
        $this->generateControllerMethods();

        // Generate view files
        $this->generateViews();

        // Generate JavaScript files for frontend interactions
        $this->generateJavaScriptFiles();

        return $this->generatedFiles;
    }

    /**
     * Generate datatable based on type.
     */
    protected function generateDatatable(): void
    {
        switch ($this->type) {
            case 'yajra':
                $generator = new YajraDatatableGenerator($this->modelName, $this->getOptions());
                break;
            case 'livewire':
                $generator = new LivewireDatatableGenerator($this->modelName, $this->getOptions());
                break;
            case 'inertia':
                $generator = new InertiaDatatableGenerator($this->modelName, $this->getOptions());
                break;
            case 'api':
                $generator = new ApiDatatableGenerator($this->modelName, $this->getOptions());
                break;
            default:
                throw new \InvalidArgumentException("Unsupported datatable type: {$this->type}");
        }

        $files = $generator->generate();
        $this->generatedFiles = array_merge($this->generatedFiles, $files);
    }

    /**
     * Generate export functionality.
     */
    protected function generateExports(): void
    {
        $exportGenerator = new ExportGenerator($this->modelName, $this->getOptions());
        $files = $exportGenerator->generate();
        $this->generatedFiles = array_merge($this->generatedFiles, $files);
    }

    /**
     * Generate controller methods for datatable endpoints.
     */
    protected function generateControllerMethods(): void
    {
        $controllerPath = $this->getControllerPath();
        
        if (!File::exists($controllerPath)) {
            // Create a basic controller if it doesn't exist
            $this->createBasicController();
        } else {
            // Add datatable methods to existing controller
            $this->addDatatableMethods();
        }
    }

    /**
     * Generate view files.
     */
    protected function generateViews(): void
    {
        if ($this->type === 'api') {
            return; // API doesn't need views
        }

        $viewPath = $this->getViewPath();
        $this->ensureDirectoryExists(dirname($viewPath));

        $stub = $this->getViewStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($viewPath) && !$this->force) {
            throw new \Exception("View already exists at: {$viewPath}");
        }

        File::put($viewPath, $content);
        $this->generatedFiles[] = $viewPath;
    }

    /**
     * Generate JavaScript files for frontend interactions.
     */
    protected function generateJavaScriptFiles(): void
    {
        if (in_array($this->type, ['livewire', 'api'])) {
            return; // These types handle JS differently
        }

        $jsPath = $this->getJavaScriptPath();
        $this->ensureDirectoryExists(dirname($jsPath));

        $stub = $this->getJavaScriptStub();
        $content = $this->replaceStubVariables($stub);

        if (File::exists($jsPath) && !$this->force) {
            throw new \Exception("JavaScript file already exists at: {$jsPath}");
        }

        File::put($jsPath, $content);
        $this->generatedFiles[] = $jsPath;
    }

    /**
     * Get options array for generators.
     */
    protected function getOptions(): array
    {
        return [
            'withExports' => $this->withExports,
            'withSearch' => $this->withSearch,
            'withBulk' => $this->withBulk,
            'cache' => $this->cache,
            'virtualScroll' => $this->virtualScroll,
            'cursorPagination' => $this->cursorPagination,
            'backgroundJobs' => $this->backgroundJobs,
            'force' => $this->force,
        ];
    }

    /**
     * Create a basic controller if it doesn't exist.
     */
    protected function createBasicController(): void
    {
        $controllerPath = $this->getControllerPath();
        $this->ensureDirectoryExists(dirname($controllerPath));

        $stub = $this->getControllerStub();
        $content = $this->replaceStubVariables($stub);

        File::put($controllerPath, $content);
        $this->generatedFiles[] = $controllerPath;
    }

    /**
     * Add datatable methods to existing controller.
     */
    protected function addDatatableMethods(): void
    {
        $controllerPath = $this->getControllerPath();
        $content = File::get($controllerPath);

        // Check if datatable methods already exist
        if (str_contains($content, 'public function data(')) {
            if (!$this->force) {
                throw new \Exception("Datatable methods already exist in controller: {$controllerPath}");
            }
        }

        $methodsStub = $this->getDatatableMethodsStub();
        $methods = $this->replaceStubVariables($methodsStub);

        // Insert methods before the last closing brace
        $content = preg_replace('/\n}\s*$/', "\n{$methods}\n}", $content);

        File::put($controllerPath, $content);
        $this->generatedFiles[] = $controllerPath . ' (updated)';
    }

    /**
     * Get appropriate stub based on type and file.
     */
    protected function getStub(string $stubName): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        $stubFile = $stubName . '.stub';

        if ($customStubPath && File::exists($customStubPath . '/datatable/' . $stubFile)) {
            return File::get($customStubPath . '/datatable/' . $stubFile);
        }

        return File::get(__DIR__ . '/Stubs/' . $stubFile);
    }

    /**
     * Get view stub.
     */
    protected function getViewStub(): string
    {
        return $this->getStub($this->type . '.view');
    }

    /**
     * Get JavaScript stub.
     */
    protected function getJavaScriptStub(): string
    {
        return $this->getStub($this->type . '.js');
    }

    /**
     * Get controller stub.
     */
    protected function getControllerStub(): string
    {
        return $this->getStub('controller.base');
    }

    /**
     * Get datatable methods stub.
     */
    protected function getDatatableMethodsStub(): string
    {
        return $this->getStub($this->type . '.methods');
    }

    /**
     * Replace variables in the stub.
     */
    protected function replaceStubVariables(string $stub): string
    {
        $replacements = $this->getReplacements();

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Get all replacement variables.
     */
    protected function getReplacements(): array
    {
        $modelClass = $this->getModelClass();
        $modelNamespace = $this->getModelNamespace();
        $controllerNamespace = $this->getControllerNamespace();
        $controllerName = $this->getControllerName();
        $modelVariable = $this->getModelVariable();
        $modelVariablePlural = Str::plural($modelVariable);
        $routeName = $this->getRouteName();
        $datatableName = $modelClass . 'DataTable';

        return [
            'namespace' => $controllerNamespace,
            'modelNamespace' => $modelNamespace,
            'modelClass' => $modelClass,
            'controllerName' => $controllerName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'routeName' => $routeName,
            'datatableName' => $datatableName,
            'viewPath' => $this->getViewPath(true),
            'type' => $this->type,
            'withExports' => $this->withExports ? 'true' : 'false',
            'withSearch' => $this->withSearch ? 'true' : 'false',
            'withBulk' => $this->withBulk ? 'true' : 'false',
            'cache' => $this->cache ? 'true' : 'false',
            'virtualScroll' => $this->virtualScroll ? 'true' : 'false',
            'cursorPagination' => $this->cursorPagination ? 'true' : 'false',
            'backgroundJobs' => $this->backgroundJobs ? 'true' : 'false',
        ];
    }

    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return class_basename($this->modelName);
    }

    /**
     * Get the model namespace.
     */
    protected function getModelNamespace(): string
    {
        $modelPath = str_replace('/', '\\', $this->modelName);
        $namespace = 'App\\Models';

        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the controller namespace.
     */
    protected function getControllerNamespace(): string
    {
        $namespace = 'App\\Http\\Controllers';

        if ($this->type === 'api') {
            $namespace .= '\\Api';
        }

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the controller name.
     */
    protected function getControllerName(): string
    {
        return $this->getModelClass() . 'Controller';
    }

    /**
     * Get the model variable name.
     */
    protected function getModelVariable(): string
    {
        return Str::camel($this->getModelClass());
    }

    /**
     * Get the route name prefix.
     */
    protected function getRouteName(): string
    {
        $parts = explode('/', $this->modelName);
        $names = array_map(fn($part) => Str::kebab($part), $parts);
        return implode('.', $names);
    }

    /**
     * Get the controller file path.
     */
    protected function getControllerPath(): string
    {
        $path = app_path('Http/Controllers');

        if ($this->type === 'api') {
            $path .= '/Api';
        }

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getControllerName() . '.php';
    }

    /**
     * Get the view file path.
     */
    protected function getViewPath(bool $dotNotation = false): string
    {
        $parts = explode('/', $this->modelName);
        $paths = array_map(fn($part) => Str::kebab($part), $parts);
        
        if ($dotNotation) {
            return implode('.', $paths) . '.index';
        }

        $viewPath = resource_path('views/' . implode('/', $paths));
        
        return $viewPath . '/index.blade.php';
    }

    /**
     * Get the JavaScript file path.
     */
    protected function getJavaScriptPath(): string
    {
        $parts = explode('/', $this->modelName);
        $paths = array_map(fn($part) => Str::kebab($part), $parts);
        
        $jsPath = resource_path('js/datatables/' . implode('/', $paths));
        
        return $jsPath . '.js';
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}