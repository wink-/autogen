<?php

declare(strict_types=1);

namespace AutoGen\Common\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Template compilation and caching system
 */
class TemplateCache
{
    protected array $config;
    protected array $memoryCache = [];
    protected array $compiledTemplates = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'ttl' => 7200,
            'prefix' => 'autogen_template',
            'compile_cache' => true,
            'precompile_stubs' => true,
            'lazy_compilation' => true,
            'compression' => true,
            'cache_path' => storage_path('framework/cache/autogen/templates'),
        ], $config);

        // Ensure cache directory exists
        if (!File::exists($this->config['cache_path'])) {
            File::makeDirectory($this->config['cache_path'], 0755, true);
        }
    }

    /**
     * Get compiled template with caching.
     */
    public function getCompiledTemplate(string $stubPath, array $variables = []): string
    {
        if (!$this->config['enabled']) {
            return $this->compileTemplate($stubPath, $variables);
        }

        $cacheKey = $this->getCacheKey($stubPath, $variables);
        
        // Check memory cache first
        if (isset($this->memoryCache[$cacheKey])) {
            return $this->memoryCache[$cacheKey];
        }

        // Check if template needs recompilation
        if ($this->shouldRecompile($stubPath, $cacheKey)) {
            $compiled = $this->compileAndCacheTemplate($stubPath, $variables, $cacheKey);
        } else {
            $compiled = $this->getFromCache($cacheKey);
            
            if ($compiled === null) {
                $compiled = $this->compileAndCacheTemplate($stubPath, $variables, $cacheKey);
            }
        }

        // Store in memory cache for quick access
        $this->memoryCache[$cacheKey] = $compiled;
        
        return $compiled;
    }

    /**
     * Precompile all stub files.
     */
    public function precompileStubs(array $stubPaths): int
    {
        if (!$this->config['precompile_stubs']) {
            return 0;
        }

        $compiled = 0;
        
        foreach ($stubPaths as $stubPath) {
            try {
                if (File::exists($stubPath)) {
                    $this->precompileStub($stubPath);
                    $compiled++;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to precompile stub: {$stubPath}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("Precompiled {$compiled} stub templates");
        return $compiled;
    }

    /**
     * Warm template cache for common patterns.
     */
    public function warmCache(array $patterns = []): int
    {
        $defaultPatterns = [
            'model' => ['fillable' => ['name', 'email'], 'casts' => ['active' => 'boolean']],
            'controller' => ['model' => 'User', 'namespace' => 'App\\Http\\Controllers'],
            'migration' => ['table' => 'users', 'action' => 'create'],
            'factory' => ['model' => 'User', 'namespace' => 'Database\\Factories'],
        ];

        $patterns = array_merge($defaultPatterns, $patterns);
        $warmed = 0;

        foreach ($patterns as $type => $variables) {
            try {
                $stubPath = $this->getStubPath($type);
                if (File::exists($stubPath)) {
                    $this->getCompiledTemplate($stubPath, $variables);
                    $warmed++;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to warm cache for pattern: {$type}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("Warmed cache for {$warmed} template patterns");
        return $warmed;
    }

    /**
     * Get template from cache.
     */
    protected function getFromCache(string $cacheKey): ?string
    {
        // Try persistent cache first
        $cached = Cache::tags(['autogen', 'templates'])->get($cacheKey);
        
        if ($cached !== null) {
            if ($this->config['compression'] && is_string($cached)) {
                return gzuncompress($cached);
            }
            return $cached;
        }

        // Try file cache
        $filePath = $this->getFileCachePath($cacheKey);
        if (File::exists($filePath)) {
            $content = File::get($filePath);
            
            if ($this->config['compression']) {
                $content = gzuncompress($content);
            }
            
            return $content;
        }

        return null;
    }

    /**
     * Compile and cache template.
     */
    protected function compileAndCacheTemplate(string $stubPath, array $variables, string $cacheKey): string
    {
        $compiled = $this->compileTemplate($stubPath, $variables);
        
        // Cache the compiled template
        $this->cacheCompiledTemplate($cacheKey, $compiled, $stubPath);
        
        return $compiled;
    }

    /**
     * Cache compiled template.
     */
    protected function cacheCompiledTemplate(string $cacheKey, string $compiled, string $stubPath): void
    {
        $data = $compiled;
        
        // Compress if enabled
        if ($this->config['compression']) {
            $data = gzcompress($compiled);
        }

        // Store in persistent cache
        Cache::tags(['autogen', 'templates'])->put($cacheKey, $data, $this->config['ttl']);
        
        // Store in file cache for persistence
        $filePath = $this->getFileCachePath($cacheKey);
        File::put($filePath, $data);
        
        // Update cache metadata
        $this->updateCacheMetadata($cacheKey, $stubPath);
    }

    /**
     * Compile template from stub.
     */
    protected function compileTemplate(string $stubPath, array $variables): string
    {
        if (!File::exists($stubPath)) {
            throw new \InvalidArgumentException("Stub file not found: {$stubPath}");
        }

        $content = File::get($stubPath);
        
        // Replace variables in template
        foreach ($variables as $key => $value) {
            $placeholder = "{{ {$key} }}";
            
            if (is_array($value)) {
                $value = $this->arrayToString($value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) {
                $value = 'null';
            }
            
            $content = str_replace($placeholder, (string) $value, $content);
        }

        return $content;
    }

    /**
     * Check if template should be recompiled.
     */
    protected function shouldRecompile(string $stubPath, string $cacheKey): bool
    {
        if (!$this->config['lazy_compilation']) {
            return false;
        }

        // Check if stub file is newer than cached version
        $filePath = $this->getFileCachePath($cacheKey);
        
        if (!File::exists($filePath)) {
            return true;
        }

        $stubModified = File::lastModified($stubPath);
        $cacheModified = File::lastModified($filePath);
        
        return $stubModified > $cacheModified;
    }

    /**
     * Precompile individual stub.
     */
    protected function precompileStub(string $stubPath): void
    {
        // Compile with empty variables for basic structure
        $basicCompiled = $this->compileTemplate($stubPath, []);
        
        $cacheKey = $this->getCacheKey($stubPath, []);
        $this->cacheCompiledTemplate($cacheKey, $basicCompiled, $stubPath);
        
        // Store the precompiled version
        $this->compiledTemplates[$stubPath] = $basicCompiled;
    }

    /**
     * Get cache key for template.
     */
    protected function getCacheKey(string $stubPath, array $variables): string
    {
        $variablesHash = md5(serialize($variables));
        $stubHash = md5($stubPath . File::lastModified($stubPath));
        
        return implode('_', [
            $this->config['prefix'],
            $stubHash,
            $variablesHash
        ]);
    }

    /**
     * Get file cache path.
     */
    protected function getFileCachePath(string $cacheKey): string
    {
        return $this->config['cache_path'] . '/' . $cacheKey . '.php';
    }

    /**
     * Get stub path for type.
     */
    protected function getStubPath(string $type): string
    {
        $stubsPath = __DIR__ . '/../../Packages/' . ucfirst($type) . '/Stubs';
        
        $extensions = ['stub', 'blade.php', 'php'];
        
        foreach ($extensions as $ext) {
            $path = "{$stubsPath}/{$type}.{$ext}";
            if (File::exists($path)) {
                return $path;
            }
        }

        throw new \InvalidArgumentException("Stub not found for type: {$type}");
    }

    /**
     * Convert array to string representation.
     */
    protected function arrayToString(array $array): string
    {
        if (empty($array)) {
            return '[]';
        }

        $items = [];
        $isAssoc = array_keys($array) !== range(0, count($array) - 1);
        
        foreach ($array as $key => $value) {
            if ($isAssoc) {
                $items[] = "'{$key}' => " . $this->formatValue($value);
            } else {
                $items[] = $this->formatValue($value);
            }
        }

        return '[' . implode(', ', $items) . ']';
    }

    /**
     * Format value for template.
     */
    protected function formatValue($value): string
    {
        if (is_string($value)) {
            return "'{$value}'";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        } elseif (is_array($value)) {
            return $this->arrayToString($value);
        }

        return (string) $value;
    }

    /**
     * Update cache metadata.
     */
    protected function updateCacheMetadata(string $cacheKey, string $stubPath): void
    {
        $metadata = Cache::tags(['autogen', 'templates'])->get('template_metadata', []);
        
        $metadata[$cacheKey] = [
            'stub_path' => $stubPath,
            'cached_at' => now()->toISOString(),
            'file_size' => File::size($this->getFileCachePath($cacheKey)),
            'hits' => ($metadata[$cacheKey]['hits'] ?? 0) + 1,
        ];
        
        Cache::tags(['autogen', 'templates'])->put('template_metadata', $metadata, $this->config['ttl'] * 2);
    }

    /**
     * Clear template cache.
     */
    public function clearCache(): void
    {
        // Clear memory cache
        $this->memoryCache = [];
        $this->compiledTemplates = [];
        
        // Clear persistent cache
        Cache::tags(['autogen', 'templates'])->flush();
        
        // Clear file cache
        if (File::exists($this->config['cache_path'])) {
            File::cleanDirectory($this->config['cache_path']);
        }
        
        Log::info('Template cache cleared');
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $metadata = Cache::tags(['autogen', 'templates'])->get('template_metadata', []);
        
        $stats = [
            'memory_cache_size' => count($this->memoryCache),
            'compiled_templates' => count($this->compiledTemplates),
            'cached_templates' => count($metadata),
            'total_cache_hits' => array_sum(array_column($metadata, 'hits')),
            'cache_directory_size' => $this->getCacheDirectorySize(),
        ];

        return $stats;
    }

    /**
     * Get cache directory size.
     */
    protected function getCacheDirectorySize(): int
    {
        if (!File::exists($this->config['cache_path'])) {
            return 0;
        }

        $size = 0;
        $files = File::allFiles($this->config['cache_path']);
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}