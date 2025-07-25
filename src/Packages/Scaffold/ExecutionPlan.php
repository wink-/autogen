<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

use Illuminate\Support\Collection;

class ExecutionPlan
{
    /**
     * Collection of execution steps.
     *
     * @var Collection<ExecutionStep>
     */
    protected Collection $steps;

    /**
     * Expected files to be generated.
     *
     * @var array
     */
    protected array $expectedFiles;

    /**
     * Plan metadata.
     *
     * @var array
     */
    protected array $metadata;

    /**
     * Create a new execution plan.
     */
    public function __construct(Collection $steps, array $expectedFiles = [], array $metadata = [])
    {
        $this->steps = $steps;
        $this->expectedFiles = $expectedFiles;
        $this->metadata = array_merge([
            'created_at' => now(),
            'total_steps' => $steps->count(),
            'estimated_duration' => $this->calculateEstimatedDuration(),
        ], $metadata);
    }

    /**
     * Get all execution steps.
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    /**
     * Get expected files to be generated.
     */
    public function getExpectedFiles(): array
    {
        return $this->expectedFiles;
    }

    /**
     * Get plan metadata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get steps by package name.
     */
    public function getStepsByPackage(string $package): Collection
    {
        return $this->steps->filter(fn($step) => $step->getPackage() === $package);
    }

    /**
     * Get critical steps (steps that must succeed).
     */
    public function getCriticalSteps(): Collection
    {
        return $this->steps->filter(fn($step) => $step->isCritical());
    }

    /**
     * Get optional steps (steps that can fail without stopping execution).
     */
    public function getOptionalSteps(): Collection
    {
        return $this->steps->filter(fn($step) => !$step->isCritical());
    }

    /**
     * Get steps that can run in parallel.
     */
    public function getParallelizableSteps(): Collection
    {
        $parallelizable = collect();
        $groupedByOrder = $this->steps->groupBy('order');

        foreach ($groupedByOrder as $order => $stepsAtLevel) {
            if ($stepsAtLevel->count() > 1) {
                $parallelizable = $parallelizable->merge($stepsAtLevel);
            }
        }

        return $parallelizable;
    }

    /**
     * Get the total estimated duration for all steps.
     */
    public function getEstimatedDuration(): int
    {
        return $this->metadata['estimated_duration'];
    }

    /**
     * Get steps that have dependencies.
     */
    public function getStepsWithDependencies(): Collection
    {
        return $this->steps->filter(fn($step) => !empty($step->getDependencies()));
    }

    /**
     * Get steps that have no dependencies (can run first).
     */
    public function getIndependentSteps(): Collection
    {
        return $this->steps->filter(fn($step) => empty($step->getDependencies()));
    }

    /**
     * Get the execution timeline grouped by order.
     */
    public function getTimeline(): array
    {
        return $this->steps
            ->groupBy('order')
            ->map(function ($stepsAtLevel, $order) {
                return [
                    'order' => $order,
                    'steps' => $stepsAtLevel->map(function ($step) {
                        return [
                            'package' => $step->getPackage(),
                            'command' => $step->getCommand(),
                            'description' => $step->getDescription(),
                            'dependencies' => $step->getDependencies(),
                            'critical' => $step->isCritical(),
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Calculate estimated duration based on steps.
     */
    protected function calculateEstimatedDuration(): int
    {
        $estimates = [
            'model' => 5,
            'controller' => 8,
            'views' => 12,
            'factory' => 3,
            'datatable' => 6,
            'migration' => 4,
        ];

        $totalDuration = 0;

        foreach ($this->steps as $step) {
            $package = $step->getPackage();
            $totalDuration += $estimates[$package] ?? 3;
        }

        return $totalDuration;
    }

    /**
     * Get execution statistics.
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_steps' => $this->steps->count(),
            'critical_steps' => $this->getCriticalSteps()->count(),
            'optional_steps' => $this->getOptionalSteps()->count(),
            'steps_with_dependencies' => $this->getStepsWithDependencies()->count(),
            'independent_steps' => $this->getIndependentSteps()->count(),
            'parallelizable_steps' => $this->getParallelizableSteps()->count(),
            'expected_files' => count($this->expectedFiles),
            'estimated_duration' => $this->getEstimatedDuration(),
        ];

        // Package breakdown
        $packageBreakdown = $this->steps
            ->groupBy('package')
            ->map(fn($steps) => $steps->count())
            ->toArray();

        $stats['package_breakdown'] = $packageBreakdown;

        return $stats;
    }

    /**
     * Validate the execution plan.
     */
    public function validate(): array
    {
        $errors = [];

        // Check for empty plan
        if ($this->steps->isEmpty()) {
            $errors[] = 'Execution plan is empty';
            return $errors;
        }

        // Check for duplicate packages
        $packages = $this->steps->pluck('package')->toArray();
        $duplicates = array_diff_assoc($packages, array_unique($packages));
        
        if (!empty($duplicates)) {
            $errors[] = 'Duplicate packages found: ' . implode(', ', array_unique($duplicates));
        }

        // Check for invalid orders
        $orders = $this->steps->pluck('order')->toArray();
        $invalidOrders = array_filter($orders, fn($order) => !is_int($order) || $order < 1);
        
        if (!empty($invalidOrders)) {
            $errors[] = 'Invalid execution orders found';
        }

        // Check dependency resolution
        foreach ($this->steps as $step) {
            $dependencies = $step->getDependencies();
            $availablePackages = $packages;
            
            $missingDependencies = array_diff($dependencies, $availablePackages);
            if (!empty($missingDependencies)) {
                $errors[] = "Step '{$step->getPackage()}' has unresolved dependencies: " . 
                    implode(', ', $missingDependencies);
            }
        }

        return $errors;
    }

    /**
     * Get a step by package name.
     */
    public function getStep(string $package): ?ExecutionStep
    {
        return $this->steps->firstWhere('package', $package);
    }

    /**
     * Check if plan contains a specific package.
     */
    public function hasPackage(string $package): bool
    {
        return $this->steps->contains('package', $package);
    }

    /**
     * Get the next step after the given package.
     */
    public function getNextStep(string $afterPackage): ?ExecutionStep
    {
        $currentStep = $this->getStep($afterPackage);
        
        if (!$currentStep) {
            return null;
        }

        return $this->steps
            ->where('order', '>', $currentStep->getOrder())
            ->sortBy('order')
            ->first();
    }

    /**
     * Get the previous step before the given package.
     */
    public function getPreviousStep(string $beforePackage): ?ExecutionStep
    {
        $currentStep = $this->getStep($beforePackage);
        
        if (!$currentStep) {
            return null;
        }

        return $this->steps
            ->where('order', '<', $currentStep->getOrder())
            ->sortByDesc('order')
            ->first();
    }

    /**
     * Export plan to array format.
     */
    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata,
            'statistics' => $this->getStatistics(),
            'timeline' => $this->getTimeline(),
            'expected_files' => $this->expectedFiles,
            'steps' => $this->steps->map(fn($step) => $step->toArray())->toArray(),
        ];
    }

    /**
     * Create plan from array data.
     */
    public static function fromArray(array $data): self
    {
        $steps = collect($data['steps'] ?? [])->map(fn($stepData) => ExecutionStep::fromArray($stepData));
        
        return new self(
            $steps,
            $data['expected_files'] ?? [],
            $data['metadata'] ?? []
        );
    }

    /**
     * Clone the plan with modifications.
     */
    public function clone(array $modifications = []): self
    {
        $newSteps = $this->steps->map(fn($step) => clone $step);
        $newExpectedFiles = $this->expectedFiles;
        $newMetadata = array_merge($this->metadata, $modifications);

        return new self($newSteps, $newExpectedFiles, $newMetadata);
    }
}