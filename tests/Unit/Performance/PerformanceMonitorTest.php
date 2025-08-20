<?php

declare(strict_types=1);

namespace AutoGen\Tests\Unit\Performance;

use AutoGen\Common\Performance\PerformanceMonitor;
use Tests\TestCase;

class PerformanceMonitorTest extends TestCase
{
    private PerformanceMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new PerformanceMonitor([
            'enabled' => true,
            'track_generation_time' => true,
            'track_memory_usage' => true,
            'track_query_count' => false, // Disable for unit tests
            'slow_query_threshold' => 1.0,
            'memory_usage_threshold' => 128 * 1024 * 1024,
        ]);
    }

    public function testStartAndEndOperation(): void
    {
        $operationId = $this->monitor->startOperation('test_operation', ['test' => true]);
        
        $this->assertIsString($operationId);
        $this->assertNotEmpty($operationId);
        
        // Simulate some work
        usleep(10000); // 10ms
        
        $metrics = $this->monitor->endOperation($operationId);
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('operation', $metrics);
        $this->assertArrayHasKey('execution_time', $metrics);
        $this->assertArrayHasKey('memory_usage', $metrics);
        $this->assertEquals('test_operation', $metrics['operation']);
        $this->assertGreaterThan(0, $metrics['execution_time']);
    }

    public function testGetMetrics(): void
    {
        // Run a test operation
        $operationId = $this->monitor->startOperation('test_operation');
        usleep(5000);
        $this->monitor->endOperation($operationId);
        
        $metrics = $this->monitor->getMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertNotEmpty($metrics);
    }

    public function testClearMetrics(): void
    {
        // Add some metrics
        $operationId = $this->monitor->startOperation('test_operation');
        $this->monitor->endOperation($operationId);
        
        $this->assertNotEmpty($this->monitor->getMetrics());
        
        $this->monitor->clearMetrics();
        
        $this->assertEmpty($this->monitor->getMetrics());
    }

    public function testDisabledMonitor(): void
    {
        $disabledMonitor = new PerformanceMonitor(['enabled' => false]);
        
        $operationId = $disabledMonitor->startOperation('test_operation');
        $this->assertEmpty($operationId);
        
        $metrics = $disabledMonitor->endOperation($operationId);
        $this->assertEmpty($metrics);
    }

    public function testFormattedMemoryUsage(): void
    {
        $formatted = $this->monitor->getFormattedMemoryUsage(1024);
        $this->assertEquals('1 KB', $formatted);
        
        $formatted = $this->monitor->getFormattedMemoryUsage(1024 * 1024);
        $this->assertEquals('1 MB', $formatted);
        
        $formatted = $this->monitor->getFormattedMemoryUsage(1024 * 1024 * 1024);
        $this->assertEquals('1 GB', $formatted);
    }

    public function testGetStatisticsWithNoMetrics(): void
    {
        $stats = $this->monitor->getStatistics();
        $this->assertEmpty($stats);
    }
}