<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Package;

use AutoGen\Common\Package\AbstractAutoGenPackage;
use AutoGen\Tests\TestCase;
use Illuminate\Container\Container;
use Mockery;

class AbstractAutoGenPackageTest extends TestCase
{
    private TestPackage $package;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = new Container;
        $this->package = new TestPackage();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_has_default_values(): void
    {
        expect($this->package->getName())->toBe('test-package');
        expect($this->package->getDescription())->toBe('Test package for unit testing');
        expect($this->package->getVersion())->toBe('1.0.0');
        expect($this->package->getPriority())->toBe(100);
        expect($this->package->isEnabled())->toBeTrue();
    }

    /** @test */
    public function it_can_be_configured_with_options(): void
    {
        $package = new TestPackage([
            'enabled' => false,
            'custom_option' => 'value'
        ]);

        expect($package->isEnabled())->toBeFalse();
        expect($package->getConfig())->toHaveKey('custom_option');
    }

    /** @test */
    public function it_can_be_enabled_and_disabled(): void
    {
        $this->package->disable();
        expect($this->package->isEnabled())->toBeFalse();

        $this->package->enable();
        expect($this->package->isEnabled())->toBeTrue();
    }

    /** @test */
    public function it_can_check_requirements(): void
    {
        expect($this->package->canRegister($this->container))->toBeTrue();
    }

    /** @test */
    public function it_can_register_with_container(): void
    {
        // Should not throw any exceptions
        $this->package->register($this->container);
        
        // Package should still be enabled after registration
        expect($this->package->isEnabled())->toBeTrue();
    }

    /** @test */
    public function it_skips_registration_when_disabled(): void
    {
        $this->package->disable();
        
        // Should not throw any exceptions and should handle gracefully
        $this->package->register($this->container);
    }

    /** @test */
    public function it_can_boot_with_container(): void
    {
        // Should not throw any exceptions
        $this->package->boot($this->container);
        
        // Package should still be enabled after booting
        expect($this->package->isEnabled())->toBeTrue();
    }

    /** @test */
    public function it_skips_booting_when_disabled(): void
    {
        $this->package->disable();
        
        // Should not throw any exceptions and should handle gracefully
        $this->package->boot($this->container);
    }

    /** @test */
    public function it_handles_failures_gracefully(): void
    {
        $exception = new \Exception('Test failure');
        
        // Should not throw any exceptions
        $this->package->handleFailure($exception);
    }

    /** @test */
    public function it_generates_package_name_from_class_name(): void
    {
        $package = new class extends AbstractAutoGenPackage {
            // No configuration needed for this test
        };

        // Class name should be converted to kebab-case
        expect($package->getName())->toBe('abstract-auto-gen');
    }
}

class TestPackage extends AbstractAutoGenPackage
{
    protected function configure(): void
    {
        $this->name = 'test-package';
        $this->description = 'Test package for unit testing';
        $this->version = '1.0.0';
        $this->priority = 100;
        
        $this->commands = ['TestCommand'];
        $this->dependencies = ['TestDependency'];
    }
}