<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Performance;

use AutoGen\Common\Performance\LazyLoader;
use PHPUnit\Framework\TestCase;

class LazyLoaderTest extends TestCase
{
    private LazyLoader $lazyLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lazyLoader = new LazyLoader([
            'enabled' => true,
            'memory_limit' => 128 * 1024 * 1024,
            'max_lazy_items' => 1000,
        ]);
    }

    public function testLazyValueCreation(): void
    {
        $lazyValue = $this->lazyLoader->lazy('test_key', function () {
            return 'computed_value';
        });
        
        $this->assertInstanceOf(\AutoGen\Common\Performance\LazyValue::class, $lazyValue);
        $this->assertFalse($lazyValue->isLoaded());
    }

    public function testLazyValueLoading(): void
    {
        $computedValue = 'computed_result';
        
        $lazyValue = $this->lazyLoader->lazy('test_key', function () use ($computedValue) {
            return $computedValue;
        });
        
        $this->assertFalse($lazyValue->isLoaded());
        
        $result = $lazyValue->getValue();
        
        $this->assertTrue($lazyValue->isLoaded());
        $this->assertEquals($computedValue, $result);
    }

    public function testLazyValueCaching(): void
    {
        $callCount = 0;
        
        $lazyValue = $this->lazyLoader->lazy('test_key', function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        });
        
        // First call should compute
        $result1 = $lazyValue->getValue();
        $this->assertEquals(1, $callCount);
        
        // Second call should use cached value
        $result2 = $lazyValue->getValue();
        $this->assertEquals(1, $callCount);
        
        $this->assertEquals($result1, $result2);
    }

    public function testPreload(): void
    {
        $this->lazyLoader->lazy('key1', fn() => 'value1');
        $this->lazyLoader->lazy('key2', fn() => 'value2');
        $this->lazyLoader->lazy('key3', fn() => 'value3');
        
        $results = $this->lazyLoader->preload(['key1', 'key2']);
        
        $this->assertCount(2, $results);
        $this->assertEquals('value1', $results['key1']);
        $this->assertEquals('value2', $results['key2']);
        $this->assertArrayNotHasKey('key3', $results);
    }

    public function testInvalidateByDependency(): void
    {
        $this->lazyLoader->lazy('key1', fn() => 'value1', ['dep1']);
        $this->lazyLoader->lazy('key2', fn() => 'value2', ['dep1', 'dep2']);
        $this->lazyLoader->lazy('key3', fn() => 'value3', ['dep2']);
        
        $invalidated = $this->lazyLoader->invalidateByDependency('dep1');
        
        $this->assertEquals(2, $invalidated);
    }

    public function testClear(): void
    {
        $this->lazyLoader->lazy('key1', fn() => 'value1');
        $this->lazyLoader->lazy('key2', fn() => 'value2');
        
        $stats = $this->lazyLoader->getStats();
        $this->assertEquals(2, $stats['total_values']);
        
        $this->lazyLoader->clear();
        
        $stats = $this->lazyLoader->getStats();
        $this->assertEquals(0, $stats['total_values']);
    }

    public function testMemoization(): void
    {
        $callCount = 0;
        
        $memoizedFunction = $this->lazyLoader->memoize(function ($a, $b) use (&$callCount) {
            $callCount++;
            return $a + $b;
        });
        
        // First call should compute
        $result1 = $memoizedFunction(1, 2);
        $this->assertEquals(3, $result1);
        $this->assertEquals(1, $callCount);
        
        // Second call with same args should use cached result
        $result2 = $memoizedFunction(1, 2);
        $this->assertEquals(3, $result2);
        $this->assertEquals(1, $callCount);
        
        // Call with different args should compute again
        $result3 = $memoizedFunction(2, 3);
        $this->assertEquals(5, $result3);
        $this->assertEquals(2, $callCount);
    }

    public function testDisabledLazyLoader(): void
    {
        $disabledLoader = new LazyLoader(['enabled' => false]);
        
        $lazyValue = $disabledLoader->lazy('test_key', function () {
            return 'computed_value';
        });
        
        $this->assertTrue($lazyValue->isLoaded());
        $this->assertEquals('computed_value', $lazyValue->getValue());
    }

    public function testGetStats(): void
    {
        $this->lazyLoader->lazy('key1', fn() => 'value1');
        $lazyValue2 = $this->lazyLoader->lazy('key2', fn() => 'value2');
        
        // Load one value
        $lazyValue2->getValue();
        
        $stats = $this->lazyLoader->getStats();
        
        $this->assertEquals(2, $stats['total_values']);
        $this->assertEquals(1, $stats['loaded_values']);
        $this->assertEquals(1, $stats['unloaded_values']);
        $this->assertEquals(0.5, $stats['load_ratio']);
    }
}