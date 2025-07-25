<?php

declare(strict_types=1);

namespace AutoGen\Tests\Helpers;

use Illuminate\Support\Facades\File;

class FileTestHelper
{
    /**
     * Create a temporary directory structure for testing
     */
    public static function createTempStructure(array $structure, string $basePath = null): string
    {
        $basePath = $basePath ?? storage_path('app/testing/' . uniqid('test_'));
        File::ensureDirectoryExists($basePath);

        foreach ($structure as $path => $content) {
            $fullPath = $basePath . '/' . $path;
            
            if (is_array($content)) {
                // It's a directory
                File::ensureDirectoryExists($fullPath);
                self::createTempStructure($content, $fullPath);
            } else {
                // It's a file
                File::ensureDirectoryExists(dirname($fullPath));
                File::put($fullPath, $content);
            }
        }

        return $basePath;
    }

    /**
     * Clean up temporary directory
     */
    public static function cleanupTempDirectory(string $path): void
    {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }

    /**
     * Assert file contains expected patterns
     */
    public static function assertFileContains(string $filePath, array $patterns): array
    {
        $results = [];
        $content = File::get($filePath);

        foreach ($patterns as $pattern) {
            $results[$pattern] = str_contains($content, $pattern);
        }

        return $results;
    }

    /**
     * Assert file matches PHP syntax
     */
    public static function assertValidPhpSyntax(string $filePath): bool
    {
        $content = File::get($filePath);
        
        // Basic PHP syntax check
        if (!str_starts_with($content, '<?php')) {
            return false;
        }

        // Try to parse the PHP file
        $result = php_check_syntax($filePath);
        return $result !== false;
    }

    /**
     * Get file content without comments and empty lines
     */
    public static function getCleanFileContent(string $filePath): string
    {
        $content = File::get($filePath);
        
        // Remove single-line comments
        $content = preg_replace('/\/\/.*$/m', '', $content);
        
        // Remove multi-line comments
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        
        // Remove empty lines
        $content = preg_replace('/^\s*$/m', '', $content);
        
        return trim($content);
    }

    /**
     * Compare two generated files
     */
    public static function compareFiles(string $file1, string $file2, bool $ignoreWhitespace = true): array
    {
        $content1 = File::get($file1);
        $content2 = File::get($file2);

        if ($ignoreWhitespace) {
            $content1 = preg_replace('/\s+/', ' ', $content1);
            $content2 = preg_replace('/\s+/', ' ', $content2);
        }

        return [
            'identical' => $content1 === $content2,
            'similarity' => similar_text($content1, $content2, $percent),
            'percentage' => $percent,
        ];
    }

    /**
     * Create a stub file for testing
     */
    public static function createStubFile(string $stubPath, string $content): void
    {
        File::ensureDirectoryExists(dirname($stubPath));
        File::put($stubPath, $content);
    }

    /**
     * Get expected file paths for a model
     */
    public static function getExpectedModelFiles(string $modelName): array
    {
        return [
            'model' => app_path("Models/{$modelName}.php"),
            'controller' => app_path("Http/Controllers/{$modelName}Controller.php"),
            'api_controller' => app_path("Http/Controllers/Api/{$modelName}Controller.php"),
            'factory' => database_path("factories/{$modelName}Factory.php"),
            'store_request' => app_path("Http/Requests/Store{$modelName}Request.php"),
            'update_request' => app_path("Http/Requests/Update{$modelName}Request.php"),
            'policy' => app_path("Policies/{$modelName}Policy.php"),
            'resource' => app_path("Http/Resources/{$modelName}Resource.php"),
        ];
    }

    /**
     * Backup existing files before test
     */
    public static function backupFiles(array $filePaths): array
    {
        $backups = [];
        
        foreach ($filePaths as $path) {
            if (File::exists($path)) {
                $backupPath = $path . '.backup.' . time();
                File::copy($path, $backupPath);
                $backups[$path] = $backupPath;
            }
        }

        return $backups;
    }

    /**
     * Restore backed up files
     */
    public static function restoreFiles(array $backups): void
    {
        foreach ($backups as $originalPath => $backupPath) {
            if (File::exists($backupPath)) {
                File::copy($backupPath, $originalPath);
                File::delete($backupPath);
            }
        }
    }

    /**
     * Clean up all generated test files
     */
    public static function cleanupGeneratedFiles(array $patterns = []): void
    {
        $defaultPatterns = [
            app_path('**/Test*.php'),
            app_path('**/*Test.php'),
            database_path('migrations/*_test_*.php'),
            database_path('factories/*Test*.php'),
            resource_path('views/test*'),
        ];

        $patterns = array_merge($defaultPatterns, $patterns);

        foreach ($patterns as $pattern) {
            $files = File::glob($pattern);
            foreach ($files as $file) {
                File::delete($file);
            }
        }
    }

    /**
     * Count lines of code in a file
     */
    public static function countLinesOfCode(string $filePath): array
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        $stats = [
            'total' => count($lines),
            'code' => 0,
            'comments' => 0,
            'blank' => 0,
        ];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            if (empty($trimmed)) {
                $stats['blank']++;
            } elseif (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
                $stats['comments']++;
            } else {
                $stats['code']++;
            }
        }

        return $stats;
    }
}