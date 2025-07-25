<?php

declare(strict_types=1);

namespace AutoGen\Packages\Controller;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PolicyGenerator
{
    protected string $modelName;
    protected bool $force;
    protected array $generatedFiles = [];

    public function __construct(string $modelName, bool $force)
    {
        $this->modelName = $modelName;
        $this->force = $force;
    }

    /**
     * Generate policy class.
     */
    public function generate(): array
    {
        $this->generatePolicy();
        return $this->generatedFiles;
    }

    /**
     * Generate the policy file.
     */
    protected function generatePolicy(): void
    {
        $stub = $this->getStub();
        $content = $this->replaceStubVariables($stub);
        $path = $this->getPolicyPath();

        $this->ensureDirectoryExists(dirname($path));

        if (File::exists($path) && !$this->force) {
            throw new \Exception("Policy already exists at: {$path}");
        }

        File::put($path, $content);
        $this->generatedFiles[] = $path;
    }

    /**
     * Get the stub file.
     */
    protected function getStub(): string
    {
        $customStubPath = config('autogen.custom_stubs_path');
        $stubName = 'policy.stub';

        if ($customStubPath && File::exists($customStubPath . '/' . $stubName)) {
            return File::get($customStubPath . '/' . $stubName);
        }

        return File::get(__DIR__ . '/Stubs/' . $stubName);
    }

    /**
     * Replace variables in the stub.
     */
    protected function replaceStubVariables(string $stub): string
    {
        $replacements = [
            'namespace' => $this->getPolicyNamespace(),
            'policyName' => $this->getPolicyName(),
            'userModel' => $this->getUserModel(),
            'modelClass' => $this->getModelClass(),
            'modelNamespace' => $this->getModelNamespace(),
            'modelVariable' => $this->getModelVariable(),
        ];

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Get the policy name.
     */
    protected function getPolicyName(): string
    {
        return $this->getModelClass() . 'Policy';
    }

    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return class_basename($this->modelName);
    }

    /**
     * Get the model variable name.
     */
    protected function getModelVariable(): string
    {
        return Str::camel($this->getModelClass());
    }

    /**
     * Get the user model.
     */
    protected function getUserModel(): string
    {
        return config('auth.providers.users.model', 'App\\Models\\User');
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
     * Get the policy namespace.
     */
    protected function getPolicyNamespace(): string
    {
        $namespace = 'App\\Policies';

        $modelPath = str_replace('/', '\\', $this->modelName);
        if (str_contains($modelPath, '\\')) {
            $parts = explode('\\', $modelPath);
            array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
        }

        return $namespace;
    }

    /**
     * Get the policy file path.
     */
    protected function getPolicyPath(): string
    {
        $path = app_path('Policies');

        $modelPath = str_replace('\\', '/', $this->modelName);
        if (str_contains($modelPath, '/')) {
            $parts = explode('/', $modelPath);
            array_pop($parts);
            $path .= '/' . implode('/', $parts);
        }

        return $path . '/' . $this->getPolicyName() . '.php';
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