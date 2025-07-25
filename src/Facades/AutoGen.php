<?php

declare(strict_types=1);

namespace AutoGen\Facades;

use Illuminate\Support\Facades\Facade;
use AutoGen\Common\AI\AIProviderManager;

/**
 * AutoGen Facade
 * 
 * @method static \AutoGen\Common\AI\AIProviderManager ai()
 * @method static \AutoGen\Common\Analysis\CodeAnalyzer analyzer()
 * @method static \AutoGen\Common\Formatting\CodeFormatter formatter()
 * @method static \AutoGen\Common\Templates\TemplateEngine templates()
 * @method static array getPackageInfo()
 * @method static string getVersion()
 * @method static array getAvailableCommands()
 * @method static bool isPackageEnabled(string $package)
 * 
 * @see \AutoGen\AutoGenManager
 */
class AutoGen extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'autogen';
    }
}