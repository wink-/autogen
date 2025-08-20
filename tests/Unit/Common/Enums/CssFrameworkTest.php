<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Enums;

use AutoGen\Common\Enums\CssFramework;
use Tests\TestCase;

class CssFrameworkTest extends TestCase
{
    public function test_enum_values(): void
    {
        $this->assertEquals('tailwind', CssFramework::Tailwind->value);
        $this->assertEquals('bootstrap', CssFramework::Bootstrap->value);
        $this->assertEquals('css', CssFramework::PlainCSS->value);
    }

    public function test_labels(): void
    {
        $this->assertEquals('Tailwind CSS', CssFramework::Tailwind->label());
        $this->assertEquals('Bootstrap', CssFramework::Bootstrap->label());
        $this->assertEquals('Plain CSS', CssFramework::PlainCSS->label());
    }

    public function test_generator_classes(): void
    {
        $this->assertEquals('AutoGen\\Packages\\Views\\Generators\\TailwindGenerator', CssFramework::Tailwind->generatorClass());
        $this->assertEquals('AutoGen\\Packages\\Views\\Generators\\BootstrapGenerator', CssFramework::Bootstrap->generatorClass());
        $this->assertEquals('AutoGen\\Packages\\Views\\Generators\\PlainCssGenerator', CssFramework::PlainCSS->generatorClass());
    }

    public function test_css_classes(): void
    {
        $tailwindClasses = CssFramework::Tailwind->cssClasses();
        $this->assertIsArray($tailwindClasses);
        $this->assertArrayHasKey('container', $tailwindClasses);
        $this->assertArrayHasKey('button', $tailwindClasses);

        $bootstrapClasses = CssFramework::Bootstrap->cssClasses();
        $this->assertIsArray($bootstrapClasses);
        $this->assertArrayHasKey('container', $bootstrapClasses);
        $this->assertArrayHasKey('button', $bootstrapClasses);
    }

    public function test_dark_mode_support(): void
    {
        $this->assertTrue(CssFramework::Tailwind->supportsDarkMode());
        $this->assertTrue(CssFramework::Bootstrap->supportsDarkMode());
        $this->assertFalse(CssFramework::PlainCSS->supportsDarkMode());
    }

    public function test_responsive_support(): void
    {
        $this->assertTrue(CssFramework::Tailwind->supportsResponsive());
        $this->assertTrue(CssFramework::Bootstrap->supportsResponsive());
        $this->assertFalse(CssFramework::PlainCSS->supportsResponsive());
    }

    public function test_stub_directories(): void
    {
        $this->assertEquals('tailwind', CssFramework::Tailwind->stubDirectory());
        $this->assertEquals('bootstrap', CssFramework::Bootstrap->stubDirectory());
        $this->assertEquals('css', CssFramework::PlainCSS->stubDirectory());
    }

    public function test_values_method(): void
    {
        $expected = ['tailwind', 'bootstrap', 'css'];
        $this->assertEquals($expected, CssFramework::values());
    }

    public function test_options_method(): void
    {
        $expected = [
            'Tailwind CSS' => 'tailwind',
            'Bootstrap' => 'bootstrap',
            'Plain CSS' => 'css',
        ];
        $this->assertEquals($expected, CssFramework::options());
    }

    public function test_with_dark_mode(): void
    {
        $frameworks = CssFramework::withDarkMode();
        $this->assertContains(CssFramework::Tailwind, $frameworks);
        $this->assertContains(CssFramework::Bootstrap, $frameworks);
        $this->assertNotContains(CssFramework::PlainCSS, $frameworks);
    }

    public function test_with_responsive(): void
    {
        $frameworks = CssFramework::withResponsive();
        $this->assertContains(CssFramework::Tailwind, $frameworks);
        $this->assertContains(CssFramework::Bootstrap, $frameworks);
        $this->assertNotContains(CssFramework::PlainCSS, $frameworks);
    }
}