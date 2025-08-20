<?php

declare(strict_types=1);

namespace AutoGen\Tests\Feature\Performance;

use AutoGen\Commands\Performance\BenchmarkCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function testBenchmarkCommandExists(): void
    {
        $this->assertTrue(class_exists(BenchmarkCommand::class));
    }

    public function testBenchmarkCommandRunsSuccessfully(): void
    {
        $this->artisan('autogen:benchmark')
            ->assertExitCode(0);
    }

    public function testBenchmarkCommandWithSpecificOperations(): void
    {
        $this->artisan('autogen:benchmark', [
            '--operation' => ['cache_operations'],
            '--iterations' => 3,
            '--warmup' => 1,
        ])->assertExitCode(0);
    }

    public function testBenchmarkCommandWithJsonOutput(): void
    {
        $this->artisan('autogen:benchmark', [
            '--output' => 'json',
            '--iterations' => 2,
        ])->assertExitCode(0);
    }

    public function testBenchmarkCommandWithCsvOutput(): void
    {
        $this->artisan('autogen:benchmark', [
            '--output' => 'csv',
            '--iterations' => 2,
        ])->assertExitCode(0);
    }

    public function testBenchmarkCommandWithWarmup(): void
    {
        $this->artisan('autogen:benchmark', [
            '--iterations' => 2,
            '--warmup' => 2,
        ])->assertExitCode(0);
    }
}