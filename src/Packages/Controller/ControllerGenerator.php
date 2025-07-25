<?php

declare(strict_types=1);

namespace AutoGen\Packages\Controller;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ControllerGenerator
{
    protected string $modelName;
    protected string $controllerType;
    protected bool $withValidation;
    protected bool $withPolicy;
    protected int $paginate;
    protected bool $force;
    protected array $generatedFiles = [];

    public function __construct(
        string $modelName,
        string $controllerType,
        bool $withValidation,
        bool $withPolicy,
        int $paginate,
        bool $force
    ) {
        $this->modelName = $modelName;
        $this->controllerType = $controllerType;
        $this->withValidation = $withValidation;
        $this->withPolicy = $withPolicy;
        $this->paginate = $paginate;
        $this->force = $force;
    }

    /**
     * Generate all required files.
     */
    public function generate(): array
    {
        // Generate controller
        $this->generateController();

        // Generate form requests if requested
        if ($this->withValidation) {
            $formRequestGenerator = new FormRequestGenerator($this->modelName, $this->force);
            $requestFiles = $formRequestGenerator->generate();
            $this->generatedFiles = array_merge($this->generatedFiles, $requestFiles);
        }

        // Generate policy if requested
        if ($this->withPolicy) {
            $policyGenerator = new PolicyGenerator($this->modelName, $this->force);
            $policyFiles = $policyGenerator->generate();
            $this->generatedFiles = array_merge($this->generatedFiles, $policyFiles);
        }

        return $this->generatedFiles;
    }

    /**
     * Generate the controller file.
     */
    protected function generateController(): void
    {
        $stub = $this->getStub();
        $content = $this->replaceStubVariables($stub);
        $path = $this->getControllerPath();

        $this->ensureDirectoryExists(dirname($path));

        if (File::exists($path) && !$this->force) {
            throw new \Exception("Controller already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Get the appropriate stub file.
     */
    protected function getStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        $stubName = $this->controllerType . '.stub';

        if ($customStubPath && File::exists($customStubPath . '/controller/' . $stubName)) {
            return File::get($customStubPath . '/controller/' . $stubName);
        }

        return File::get(__DIR__ . '/Stubs/controller.' . $stubName);
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

        $replacements = [
            'namespace' => $controllerNamespace,
            'modelNamespace' => $modelNamespace,
            'modelClass' => $modelClass,
            'controllerName' => $controllerName,
            'modelVariable' => $modelVariable,
            'modelVariablePlural' => $modelVariablePlural,
            'routeName' => $routeName,
            'paginate' => $this->paginate,
            'viewPath' => $this->getViewPath(),
        ];

        if ($this->withValidation) {
            $replacements['requestNamespace'] = $this->getRequestNamespace();
            $replacements['storeRequest'] = 'Store' . $modelClass . 'Request';
            $replacements['updateRequest'] = 'Update' . $modelClass . 'Request';
        }

        if ($this->withPolicy) {
            $replacements['policyChecks'] = $this->getPolicyChecks();
        } else {
            $replacements['policyChecks'] = '';
        }

        return $replacements;
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

        if ($this->controllerType === 'api') {
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
     * Get the view path.
     */
    protected function getViewPath(): string
    {
        $parts = explode('/', $this->modelName);
        $paths = array_map(fn($part) => Str::kebab($part), $parts);
        return implode('.', $paths);
    }

    /**
     * Get the request namespace.
     */
    protected function getRequestNamespace(): string
    {
        $namespace = 'App\\Http\\Requests';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get policy check code.
     */
    protected function getPolicyChecks(): string
    {
        if (!$this->withPolicy) {
            return '';
        }

        return "\$this->authorizeResource({$this->getModelClass()}::class, '{$this->getModelVariable()}');";
    }

    /**
     * Get the controller file path.
     */
    protected function getControllerPath(): string
    {
        $path = app_path('Http/Controllers');

        if ($this->controllerType === 'api') {
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
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}