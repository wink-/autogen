<?php

declare(strict_types=1);

namespace AutoGen\Tests\Feature\Performance;

use AutoGen\Commands\Performance\OptimizeCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptimizeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function testOptimizeCommandExists(): void
    {
        $this->assertTrue(class_exists(OptimizeCommand::class));
    }

    public function testOptimizeCommandRunsSuccessfully(): void
    {
        $this->artisan('autogen:optimize')
            ->assertExitCode(0);
    }

    public function testOptimizeCommandWithClearCache(): void
    {
        $this->artisan('autogen:optimize', [
            '--clear-cache' => true,
        ])->assertExitCode(0);
    }

    public function testOptimizeCommandWithWarmCache(): void
    {
        $this->artisan('autogen:optimize', [
            '--warm-cache' => true,
        ])->assertExitCode(0);
    }

    public function testOptimizeCommandWithAnalyze(): void
    {
        $this->artisan('autogen:optimize', [
            '--analyze' => true,
        ])->assertExitCode(0);
    }

    public function testOptimizeCommandWithSuggestions(): void
    {
        $this->artisan('autogen:optimize', [
            '--suggestions' => true,
        ])->assertExitCode(0);
    }

    public function testOptimizeCommandWithAllOptions(): void
    {
        $this->artisan('autogen:optimize', [
            '--clear-cache' => true,
            '--warm-cache' => true,
            '--analyze' => true,
            '--suggestions' => true,
        ])->assertExitCode(0);
    }
}