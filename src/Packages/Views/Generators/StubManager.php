<?php

declare(strict_types=1);

namespace AutoGen\Packages\Views\Generators;

use Illuminate\Support\Facades\File;

class StubManager
{
    protected static array $stubCache = [];

    public static function getStub(string $framework, string $stubName): string
    {
        $cacheKey = "{$framework}.{$stubName}";
        
        if (isset(self::$stubCache[$cacheKey])) {
            return self::$stubCache[$cacheKey];
        }

        // Check for custom stub first
        $customStubPath = self::getCustomStubPath($framework, $stubName);
        if (File::exists($customStubPath)) {
            return self::$stubCache[$cacheKey] = File::get($customStubPath);
        }

        // Use built-in stub
        $builtInStubPath = self::getBuiltInStubPath($framework, $stubName);
        if (File::exists($builtInStubPath)) {
            return self::$stubCache[$cacheKey] = File::get($builtInStubPath);
        }

        throw new \InvalidArgumentException("Stub not found: {$framework}/{$stubName}");
    }

    public static function replaceStubVariables(string $stub, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $stub = str_replace("{{ {$key} }}", $value, $stub);
            $stub = str_replace("{{{$key}}}", $value, $stub);
        }

        return $stub;
    }

    protected static function getCustomStubPath(string $framework, string $stubName): string
    {
        $customStubsPath = config('autogen.custom_stubs_path');
        
        if (!$customStubsPath) {
            return '';
        }

        return "{$customStubsPath}/views/{$framework}/{$stubName}.stub";
    }

    protected static function getBuiltInStubPath(string $framework, string $stubName): string
    {
        return __DIR__ . "/../Stubs/{$framework}/{$stubName}.stub";
    }

    public static function listAvailableStubs(string $framework): array
    {
        $stubs = [];
        $stubPath = __DIR__ . "/../Stubs/{$framework}";
        
        if (File::isDirectory($stubPath)) {
            $files = File::files($stubPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'stub') {
                    $stubs[] = $file->getFilenameWithoutExtension();
                }
            }
        }

        return $stubs;
    }

    public static function publishStubs(string $targetPath): void
    {
        $sourcePath = __DIR__ . '/../Stubs';
        
        if (!File::isDirectory($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        File::copyDirectory($sourcePath, $targetPath);
    }
}