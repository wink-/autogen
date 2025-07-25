<?php

declare(strict_types=1);

namespace AutoGen\Common\Traits;

use Illuminate\Support\Facades\File;
use AutoGen\Common\Exceptions\FileNotFoundException;
use AutoGen\Common\Exceptions\FileNotWritableException;

trait HandlesFiles
{
    /**
     * Read file contents safely.
     */
    protected function readFile(string $path): string
    {
        if (!File::exists($path)) {
            throw new FileNotFoundException("File not found: {$path}");
        }

        if (!File::isReadable($path)) {
            throw new FileNotFoundException("File is not readable: {$path}");
        }

        return File::get($path);
    }

    /**
     * Write file contents safely.
     */
    protected function writeFile(string $path, string $content): bool
    {
        $directory = dirname($path);
        
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (File::exists($path) && !File::isWritable($path)) {
            throw new FileNotWritableException("File is not writable: {$path}");
        }

        return File::put($path, $content) !== false;
    }

    /**
     * Check if file has allowed extension.
     */
    protected function hasAllowedExtension(string $path, array $allowedExtensions): bool
    {
        $extension = File::extension($path);
        return empty($allowedExtensions) || in_array($extension, $allowedExtensions, true);
    }

    /**
     * Get file extension.
     */
    protected function getFileExtension(string $path): string
    {
        return File::extension($path);
    }

    /**
     * Get file name without extension.
     */
    protected function getFileName(string $path): string
    {
        return File::name($path);
    }

    /**
     * Get directory path.
     */
    protected function getDirectoryPath(string $path): string
    {
        return File::dirname($path);
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Get all files in directory with optional extension filter.
     */
    protected function getFilesInDirectory(string $directory, array $extensions = []): array
    {
        if (!File::isDirectory($directory)) {
            return [];
        }

        $files = File::allFiles($directory);
        
        if (empty($extensions)) {
            return array_map(fn($file) => $file->getPathname(), $files);
        }

        return array_filter(
            array_map(fn($file) => $file->getPathname(), $files),
            fn($file) => $this->hasAllowedExtension($file, $extensions)
        );
    }

    /**
     * Check if path is safe (prevent directory traversal).
     */
    protected function isSafePath(string $path, string $basePath): bool
    {
        $realPath = realpath($path);
        $realBasePath = realpath($basePath);
        
        return $realPath !== false 
            && $realBasePath !== false 
            && str_starts_with($realPath, $realBasePath);
    }
}