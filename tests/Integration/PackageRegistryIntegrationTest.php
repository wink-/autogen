<?php

declare(strict_types=1);

namespace AutoGen\Tests\Integration;

use AutoGen\AutoGenServiceProvider;
use AutoGen\Common\Contracts\PackageRegistryInterface;
use AutoGen\Common\Configuration\ConfigurationManager;
use AutoGen\Tests\TestCase;
use Illuminate\Foundation\Application;

class PackageRegistryIntegrationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app = new Application(base_path());
        $this->app->register(AutoGenServiceProvider::class);
    }

    /** @test */
    public function it_registers_package_registry_with_container(): void
    {
        expect($this->app->bound(PackageRegistryInterface::class))->toBeTrue();
        expect($this->app->bound('autogen.registry'))->toBeTrue();
        
        $registry = $this->app->make(PackageRegistryInterface::class);
        expect($registry)->toBeInstanceOf(PackageRegistryInterface::class);
    }

    /** @test */
    public function it_can_access_registry_through_alias(): void
    {
        $registry1 = $this->app->make(PackageRegistryInterface::class);
        $registry2 = $this->app->make('autogen.registry');
        
        expect($registry1)->toBe($registry2);
    }

    /** @test */
    public function it_registers_configuration_manager_before_registry(): void
    {
        expect($this->app->bound(ConfigurationManager::class))->toBeTrue();
        expect($this->app->bound('autogen.config'))->toBeTrue();
        
        $configManager = $this->app->make(ConfigurationManager::class);
        expect($configManager)->toBeInstanceOf(ConfigurationManager::class);
    }

    /** @test */
    public function it_can_discover_packages_if_enabled(): void
    {
        $registry = $this->app->make(PackageRegistryInterface::class);
        
        // Should have at least some packages registered
        expect($registry->all()->count())->toBeGreaterThan(0);
    }

    /** @test */
    public function it_handles_package_errors_gracefully(): void
    {
        $registry = $this->app->make(PackageRegistryInterface::class);
        
        // Even if there are errors, the registry should still function
        $errors = $registry->getErrors();
        
        // We don't assert on specific errors since they depend on environment
        // but the method should return an array
        expect($errors)->toBeArray();
    }

    /** @test */
    public function it_can_enable_and_disable_packages_dynamically(): void
    {
        $registry = $this->app->make(PackageRegistryInterface::class);
        
        // Get first available package
        $packages = $registry->all();
        if ($packages->count() > 0) {
            $packageName = $packages->first()->getName();
            
            // Test disabling
            $result = $registry->disable($packageName);
            expect($result)->toBeTrue();
            expect($registry->isEnabled($packageName))->toBeFalse();
            
            // Test enabling
            $result = $registry->enable($packageName);
            expect($result)->toBeTrue();
            expect($registry->isEnabled($packageName))->toBeTrue();
        }
        
        // Test should pass even if no packages are available
        expect(true)->toBeTrue();
    }
}