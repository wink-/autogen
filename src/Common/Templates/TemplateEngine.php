<?php

declare(strict_types=1);

namespace AutoGen\Common\Templates;

use AutoGen\Common\Contracts\TemplateEngineInterface;
use AutoGen\Common\Traits\HasConfiguration;
use AutoGen\Common\Traits\HandlesFiles;
use AutoGen\Common\Traits\ValidatesInput;
use AutoGen\Common\Exceptions\FileNotFoundException;

class TemplateEngine implements TemplateEngineInterface
{
    use HasConfiguration;
    use HandlesFiles;
    use ValidatesInput;

    /**
     * Global template variables.
     */
    protected array $globalVariables = [];

    /**
     * Template cache.
     */
    protected array $cache = [];

    /**
     * Create a new template engine instance.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig(array_merge($this->getDefaultConfig(), $config));
        $this->ensureCacheDirectoryExists();
    }

    /**
     * Render a template with the given variables.
     */
    public function render(string $template, array $variables = []): string
    {
        $this->validateNotEmpty($template, 'template');

        $allVariables = array_merge($this->globalVariables, $variables);
        
        return $this->processTemplate($template, $allVariables);
    }

    /**
     * Render a template file with the given variables.
     */
    public function renderFile(string $templatePath, array $variables = []): string
    {
        $this->validateNotEmpty($templatePath, 'templatePath');

        $fullPath = $this->findTemplatePath($templatePath);
        
        if (!$fullPath) {
            throw new FileNotFoundException("Template not found: {$templatePath}");
        }

        $template = $this->readFile($fullPath);
        
        return $this->render($template, $variables);
    }

    /**
     * Check if a template exists.
     */
    public function exists(string $template): bool
    {
        return $this->findTemplatePath($template) !== null;
    }

    /**
     * Add a template path.
     */
    public function addPath(string $path): void
    {
        $this->validateNotEmpty($path, 'path');

        $paths = $this->getConfigValue('paths', []);
        $paths[] = $path;
        $this->setConfigValue('paths', array_unique($paths));
    }

    /**
     * Set template variables globally.
     */
    public function setGlobalVariables(array $variables): void
    {
        $this->globalVariables = array_merge($this->globalVariables, $variables);
    }

    /**
     * Get available templates.
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];
        $paths = $this->getConfigValue('paths', []);
        $extension = $this->getConfigValue('file_extension', '.stub');

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = $this->getFilesInDirectory($path);
                
                foreach ($files as $file) {
                    if (str_ends_with($file, $extension)) {
                        $relativePath = str_replace($path . '/', '', $file);
                        $templateName = str_replace($extension, '', $relativePath);
                        $templates[] = $templateName;
                    }
                }
            }
        }

        return array_unique($templates);
    }

    /**
     * Cache a compiled template.
     */
    public function cacheTemplate(string $template, string $compiled): void
    {
        $cacheEnabled = $this->getConfigValue('cache_enabled', true);
        
        if (!$cacheEnabled) {
            return;
        }

        $cacheKey = $this->getCacheKey($template);
        $this->cache[$cacheKey] = $compiled;

        // Also save to disk cache if path is configured
        $cachePath = $this->getConfigValue('cache');
        if ($cachePath) {
            $cacheFile = $cachePath . '/' . $cacheKey . '.php';
            $this->writeFile($cacheFile, "<?php\nreturn " . var_export($compiled, true) . ';');
        }
    }

    /**
     * Clear template cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];

        $cachePath = $this->getConfigValue('cache');
        if ($cachePath && is_dir($cachePath)) {
            $cacheFiles = glob($cachePath . '/*.php');
            foreach ($cacheFiles as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Get the default configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'engine' => 'simple',
            'cache_enabled' => true,
            'cache' => null,
            'paths' => [],
            'file_extension' => '.stub',
            'variables_style' => 'double_curly', // double_curly: {{var}}, at_sign: @var
        ];
    }

    /**
     * Process template with variables.
     */
    protected function processTemplate(string $template, array $variables): string
    {
        $cacheKey = $this->getCacheKey($template);
        
        // Check cache first
        if (isset($this->cache[$cacheKey])) {
            return $this->replaceVariables($this->cache[$cacheKey], $variables);
        }

        // Try to load from disk cache
        $compiled = $this->loadFromDiskCache($cacheKey);
        if ($compiled !== null) {
            $this->cache[$cacheKey] = $compiled;
            return $this->replaceVariables($compiled, $variables);
        }

        // Process template
        $processed = $this->compileTemplate($template);
        
        // Cache the compiled template
        $this->cacheTemplate($template, $processed);
        
        return $this->replaceVariables($processed, $variables);
    }

    /**
     * Compile template (basic implementation).
     */
    protected function compileTemplate(string $template): string
    {
        $engine = $this->getConfigValue('engine', 'simple');
        
        return match ($engine) {
            'blade' => $this->compileBlade($template),
            'twig' => $this->compileTwig($template),
            default => $template, // simple/plain
        };
    }

    /**
     * Replace variables in template.
     */
    protected function replaceVariables(string $template, array $variables): string
    {
        $style = $this->getConfigValue('variables_style', 'double_curly');
        
        return match ($style) {
            'at_sign' => $this->replaceAtSignVariables($template, $variables),
            default => $this->replaceDoubleCurlyVariables($template, $variables),
        };
    }

    /**
     * Replace double curly variables {{variable}}.
     */
    protected function replaceDoubleCurlyVariables(string $template, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($matches) use ($variables) {
            $varName = $matches[1];
            return $variables[$varName] ?? $matches[0];
        }, $template);
    }

    /**
     * Replace at-sign variables @variable.
     */
    protected function replaceAtSignVariables(string $template, array $variables): string
    {
        return preg_replace_callback('/@(\w+)/', function ($matches) use ($variables) {
            $varName = $matches[1];
            return $variables[$varName] ?? $matches[0];
        }, $template);
    }

    /**
     * Basic Blade template compilation.
     */
    protected function compileBlade(string $template): string
    {
        // Very basic Blade-like syntax support
        $template = preg_replace('/@if\s*\((.*?)\)/', '<?php if($1): ?>', $template);
        $template = preg_replace('/@endif/', '<?php endif; ?>', $template);
        $template = preg_replace('/@foreach\s*\((.*?)\)/', '<?php foreach($1): ?>', $template);
        $template = preg_replace('/@endforeach/', '<?php endforeach; ?>', $template);
        
        return $template;
    }

    /**
     * Basic Twig template compilation.
     */
    protected function compileTwig(string $template): string
    {
        // Very basic Twig-like syntax support
        $template = preg_replace('/\{\%\s*if\s+(.*?)\s*\%\}/', '<?php if($1): ?>', $template);
        $template = preg_replace('/\{\%\s*endif\s*\%\}/', '<?php endif; ?>', $template);
        
        return $template;
    }

    /**
     * Find template path in configured directories.
     */
    protected function findTemplatePath(string $template): ?string
    {
        $paths = $this->getConfigValue('paths', []);
        $extension = $this->getConfigValue('file_extension', '.stub');
        
        foreach ($paths as $basePath) {
            $fullPath = $basePath . '/' . $template . $extension;
            
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * Get cache key for template.
     */
    protected function getCacheKey(string $template): string
    {
        return md5($template);
    }

    /**
     * Load compiled template from disk cache.
     */
    protected function loadFromDiskCache(string $cacheKey): ?string
    {
        $cachePath = $this->getConfigValue('cache');
        
        if (!$cachePath) {
            return null;
        }

        $cacheFile = $cachePath . '/' . $cacheKey . '.php';
        
        if (file_exists($cacheFile)) {
            return include $cacheFile;
        }

        return null;
    }

    /**
     * Ensure cache directory exists.
     */
    protected function ensureCacheDirectoryExists(): void
    {
        $cachePath = $this->getConfigValue('cache');
        
        if ($cachePath) {
            $this->ensureDirectoryExists($cachePath);
        }
    }
}