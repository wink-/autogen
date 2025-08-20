<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Registry;

use AutoGen\Common\Contracts\AutoGenPackageInterface;
use AutoGen\Common\Contracts\PackageRegistryInterface;
use AutoGen\Common\Exceptions\AutoGenException;
use AutoGen\Common\Registry\PackageRegistry;
use AutoGen\Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Mockery;

class PackageRegistryTest extends TestCase
{
    private PackageRegistryInterface $registry;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = new Container;
        $this->registry = new PackageRegistry($this->container);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_register_a_package(): void
    {
        $package = Mockery::mock(AutoGenPackageInterface::class);
        $package->shouldReceive('getName')->andReturn('test-package');
        $package->shouldReceive('canRegister')->andReturn(true);
        $package->shouldReceive('isEnabled')->andReturn(true);
        $package->shouldReceive('register')->once();

        $this->registry->register($package);

        expect($this->registry->has('test-package'))->toBeTrue();
        expect($this->registry->get('test-package'))->toBe($package);
    }

    /** @test */
    public function it_can_get_all_packages(): void
    {
        $package1 = Mockery::mock(AutoGenPackageInterface::class);
        $package1->shouldReceive('getName')->andReturn('package-1');
        $package1->shouldReceive('canRegister')->andReturn(true);
        $package1->shouldReceive('isEnabled')->andReturn(true);
        $package1->shouldReceive('register');
        $package1->shouldReceive('getPriority')->andReturn(10);

        $package2 = Mockery::mock(AutoGenPackageInterface::class);
        $package2->shouldReceive('getName')->andReturn('package-2');
        $package2->shouldReceive('canRegister')->andReturn(true);
        $package2->shouldReceive('isEnabled')->andReturn(true);
        $package2->shouldReceive('register');
        $package2->shouldReceive('getPriority')->andReturn(5);

        $this->registry->register($package1);
        $this->registry->register($package2);

        $allPackages = $this->registry->all();
        
        expect($allPackages)->toBeInstanceOf(Collection::class);
        expect($allPackages->count())->toBe(2);
        expect($allPackages->first())->toBe($package2); // Lower priority loads first
    }

    /** @test */
    public function it_can_get_enabled_packages_only(): void
    {
        $enabledPackage = Mockery::mock(AutoGenPackageInterface::class);
        $enabledPackage->shouldReceive('getName')->andReturn('enabled-package');
        $enabledPackage->shouldReceive('canRegister')->andReturn(true);
        $enabledPackage->shouldReceive('isEnabled')->andReturn(true);
        $enabledPackage->shouldReceive('register');
        $enabledPackage->shouldReceive('getPriority')->andReturn(10);

        $disabledPackage = Mockery::mock(AutoGenPackageInterface::class);
        $disabledPackage->shouldReceive('getName')->andReturn('disabled-package');
        $disabledPackage->shouldReceive('canRegister')->andReturn(true);
        $disabledPackage->shouldReceive('isEnabled')->andReturn(false);

        $this->registry->register($enabledPackage);
        $this->registry->register($disabledPackage);

        $enabledPackages = $this->registry->enabled();
        
        expect($enabledPackages->count())->toBe(1);
        expect($enabledPackages->first())->toBe($enabledPackage);
    }

    /** @test */
    public function it_can_enable_a_package(): void
    {
        $package = Mockery::mock(AutoGenPackageInterface::class);
        $package->shouldReceive('getName')->andReturn('test-package');
        $package->shouldReceive('canRegister')->andReturn(true);
        $package->shouldReceive('isEnabled')->andReturn(false);
        $package->shouldReceive('enable')->once();
        $package->shouldReceive('register')->once();

        $this->registry->register($package);
        
        $result = $this->registry->enable('test-package');
        
        expect($result)->toBeTrue();
    }

    /** @test */
    public function it_can_disable_a_package(): void
    {
        $package = Mockery::mock(AutoGenPackageInterface::class);
        $package->shouldReceive('getName')->andReturn('test-package');
        $package->shouldReceive('canRegister')->andReturn(true);
        $package->shouldReceive('isEnabled')->andReturn(true);
        $package->shouldReceive('register');
        $package->shouldReceive('disable')->once();

        $this->registry->register($package);
        
        $result = $this->registry->disable('test-package');
        
        expect($result)->toBeTrue();
    }

    /** @test */
    public function it_handles_package_registration_failure_gracefully(): void
    {
        $package = Mockery::mock(AutoGenPackageInterface::class);
        $package->shouldReceive('getName')->andReturn('failing-package');
        $package->shouldReceive('canRegister')->andReturn(true);
        $package->shouldReceive('isEnabled')->andReturn(true);
        $package->shouldReceive('register')->andThrow(new \Exception('Registration failed'));
        $package->shouldReceive('handleFailure')->once();

        // Should not throw exception in non-testing environments
        $this->registry->register($package);

        $errors = $this->registry->getErrors();
        expect($errors)->toHaveKey('failing-package');
        expect($errors['failing-package'])->toBe('Registration failed');
    }

    /** @test */
    public function it_can_boot_enabled_packages(): void
    {
        $package = Mockery::mock(AutoGenPackageInterface::class);
        $package->shouldReceive('getName')->andReturn('test-package');
        $package->shouldReceive('canRegister')->andReturn(true);
        $package->shouldReceive('isEnabled')->andReturn(true);
        $package->shouldReceive('register');
        $package->shouldReceive('boot')->with($this->container)->once();
        $package->shouldReceive('getPriority')->andReturn(10);

        $this->registry->register($package);
        $this->registry->bootPackages();
    }

    /** @test */
    public function it_can_register_packages_with_container(): void
    {
        $package = Mockery::mock(AutoGenPackageInterface::class);
        $package->shouldReceive('getName')->andReturn('test-package');
        $package->shouldReceive('canRegister')->andReturn(true);
        $package->shouldReceive('isEnabled')->andReturn(true);
        $package->shouldReceive('register')->times(2); // Once during initial registration, once during registerPackages
        $package->shouldReceive('getPriority')->andReturn(10);

        $this->registry->register($package);
        $this->registry->registerPackages();
    }

    /** @test */
    public function it_can_clear_all_packages(): void
    {
        $package = Mockery::mock(AutoGenPackageInterface::class);
        $package->shouldReceive('getName')->andReturn('test-package');
        $package->shouldReceive('canRegister')->andReturn(true);
        $package->shouldReceive('isEnabled')->andReturn(true);
        $package->shouldReceive('register');

        $this->registry->register($package);
        expect($this->registry->has('test-package'))->toBeTrue();

        $this->registry->clear();
        expect($this->registry->has('test-package'))->toBeFalse();
        expect($this->registry->getErrors())->toBeEmpty();
    }
}