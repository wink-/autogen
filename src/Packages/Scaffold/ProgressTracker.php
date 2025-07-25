<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProgressTracker
{
    /**
     * The execution plan being tracked.
     *
     * @var ExecutionPlan|null
     */
    protected ?ExecutionPlan $plan = null;

    /**
     * Current step being executed.
     *
     * @var ExecutionStep|null
     */
    protected ?ExecutionStep $currentStep = null;

    /**
     * Completed steps.
     *
     * @var Collection
     */
    protected Collection $completedSteps;

    /**
     * Failed steps.
     *
     * @var Collection
     */
    protected Collection $failedSteps;

    /**
     * Step execution times.
     *
     * @var array
     */
    protected array $stepTimes = [];

    /**
     * Overall start time.
     *
     * @var \Carbon\Carbon|null
     */
    protected ?\Carbon\Carbon $startTime = null;

    /**
     * Overall end time.
     *
     * @var \Carbon\Carbon|null
     */
    protected ?\Carbon\Carbon $endTime = null;

    /**
     * Progress statistics.
     *
     * @var array
     */
    protected array $statistics = [];

    /**
     * Progress callbacks.
     *
     * @var array
     */
    protected array $callbacks = [];

    /**
     * Create a new progress tracker instance.
     */
    public function __construct()
    {
        $this->completedSteps = collect();
        $this->failedSteps = collect();
    }

    /**
     * Initialize tracking for a given configuration.
     */
    public function initialize(array $config): void
    {
        $this->statistics = [
            'total_files_to_generate' => $this->estimateFileCount($config),
            'generated_files' => 0,
            'skipped_files' => 0,
            'failed_files' => 0,
            'estimated_duration' => $this->estimateDuration($config),
        ];
    }

    /**
     * Start tracking an execution plan.
     */
    public function start(ExecutionPlan $plan): void
    {
        $this->plan = $plan;
        $this->startTime = now();
        $this->completedSteps = collect();
        $this->failedSteps = collect();
        $this->stepTimes = [];

        Log::info('Scaffold generation started', [
            'total_steps' => $plan->getSteps()->count(),
            'expected_files' => count($plan->getExpectedFiles()),
        ]);

        $this->triggerCallback('started', [
            'plan' => $plan,
            'start_time' => $this->startTime,
        ]);
    }

    /**
     * Start tracking a specific step.
     */
    public function startStep(ExecutionStep $step): void
    {
        $this->currentStep = $step;
        $this->stepTimes[$step->getPackage()] = [
            'start' => now(),
            'end' => null,
            'duration' => null,
        ];

        Log::info('Starting scaffold step', [
            'package' => $step->getPackage(),
            'command' => $step->getCommand(),
            'dependencies' => $step->getDependencies(),
        ]);

        $this->triggerCallback('step_started', [
            'step' => $step,
            'progress' => $this->getProgress(),
        ]);
    }

    /**
     * Mark a step as completed.
     */
    public function completeStep(ExecutionStep $step, StepResult $result): void
    {
        $this->completedSteps->push($step);
        
        if (isset($this->stepTimes[$step->getPackage()])) {
            $this->stepTimes[$step->getPackage()]['end'] = now();
            $this->stepTimes[$step->getPackage()]['duration'] = 
                $this->stepTimes[$step->getPackage()]['end']->diffInSeconds(
                    $this->stepTimes[$step->getPackage()]['start']
                );
        }

        // Update statistics
        $this->statistics['generated_files'] += count($result->getGeneratedFiles());
        $this->statistics['skipped_files'] += count($result->getSkippedFiles());

        Log::info('Scaffold step completed', [
            'package' => $step->getPackage(),
            'duration' => $this->stepTimes[$step->getPackage()]['duration'] ?? 0,
            'generated_files' => count($result->getGeneratedFiles()),
            'skipped_files' => count($result->getSkippedFiles()),
        ]);

        $this->triggerCallback('step_completed', [
            'step' => $step,
            'result' => $result,
            'progress' => $this->getProgress(),
        ]);

        $this->currentStep = null;
    }

    /**
     * Mark a step as failed.
     */
    public function failStep(ExecutionStep $step, string $error): void
    {
        $this->failedSteps->push([
            'step' => $step,
            'error' => $error,
            'timestamp' => now(),
        ]);

        if (isset($this->stepTimes[$step->getPackage()])) {
            $this->stepTimes[$step->getPackage()]['end'] = now();
            $this->stepTimes[$step->getPackage()]['duration'] = 
                $this->stepTimes[$step->getPackage()]['end']->diffInSeconds(
                    $this->stepTimes[$step->getPackage()]['start']
                );
        }

        $this->statistics['failed_files']++;

        Log::error('Scaffold step failed', [
            'package' => $step->getPackage(),
            'error' => $error,
            'duration' => $this->stepTimes[$step->getPackage()]['duration'] ?? 0,
        ]);

        $this->triggerCallback('step_failed', [
            'step' => $step,
            'error' => $error,
            'progress' => $this->getProgress(),
        ]);

        $this->currentStep = null;
    }

    /**
     * Mark the entire process as completed.
     */
    public function complete(): void
    {
        $this->endTime = now();
        
        $totalDuration = $this->startTime ? 
            $this->endTime->diffInSeconds($this->startTime) : 0;

        Log::info('Scaffold generation completed', [
            'total_duration' => $totalDuration,
            'completed_steps' => $this->completedSteps->count(),
            'failed_steps' => $this->failedSteps->count(),
            'statistics' => $this->statistics,
        ]);

        $this->triggerCallback('completed', [
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'duration' => $totalDuration,
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * Mark the entire process as failed.
     */
    public function fail(string $error): void
    {
        $this->endTime = now();
        
        $totalDuration = $this->startTime ? 
            $this->endTime->diffInSeconds($this->startTime) : 0;

        Log::error('Scaffold generation failed', [
            'error' => $error,
            'total_duration' => $totalDuration,
            'completed_steps' => $this->completedSteps->count(),
            'failed_steps' => $this->failedSteps->count(),
        ]);

        $this->triggerCallback('failed', [
            'error' => $error,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'duration' => $totalDuration,
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * Get current progress information.
     */
    public function getProgress(): array
    {
        if (!$this->plan) {
            return [
                'percentage' => 0,
                'completed_steps' => 0,
                'total_steps' => 0,
                'current_step' => null,
                'estimated_time_remaining' => null,
            ];
        }

        $totalSteps = $this->plan->getSteps()->count();
        $completedSteps = $this->completedSteps->count();
        $percentage = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;

        return [
            'percentage' => round($percentage, 2),
            'completed_steps' => $completedSteps,
            'total_steps' => $totalSteps,
            'failed_steps' => $this->failedSteps->count(),
            'current_step' => $this->currentStep ? [
                'package' => $this->currentStep->getPackage(),
                'description' => $this->currentStep->getDescription(),
                'started_at' => $this->stepTimes[$this->currentStep->getPackage()]['start'] ?? null,
            ] : null,
            'estimated_time_remaining' => $this->getEstimatedTimeRemaining(),
        ];
    }

    /**
     * Get detailed statistics.
     */
    public function getStatistics(): array
    {
        $stats = $this->statistics;
        
        $stats['execution_time'] = $this->getTotalExecutionTime();
        $stats['average_step_time'] = $this->getAverageStepTime();
        $stats['slowest_step'] = $this->getSlowestStep();
        $stats['fastest_step'] = $this->getFastestStep();
        $stats['steps_breakdown'] = $this->getStepsBreakdown();
        
        return $stats;
    }

    /**
     * Get step execution times breakdown.
     */
    public function getStepsBreakdown(): array
    {
        $breakdown = [];
        
        foreach ($this->stepTimes as $package => $times) {
            $breakdown[$package] = [
                'duration' => $times['duration'],
                'start_time' => $times['start'],
                'end_time' => $times['end'],
                'status' => $this->getStepStatus($package),
            ];
        }
        
        return $breakdown;
    }

    /**
     * Get the status of a specific step.
     */
    protected function getStepStatus(string $package): string
    {
        if ($this->completedSteps->firstWhere('package', $package)) {
            return 'completed';
        }
        
        if ($this->failedSteps->firstWhere('step.package', $package)) {
            return 'failed';
        }
        
        if ($this->currentStep && $this->currentStep->getPackage() === $package) {
            return 'running';
        }
        
        return 'pending';
    }

    /**
     * Estimate the number of files to be generated.
     */
    protected function estimateFileCount(array $config): int
    {
        $count = 0;
        
        // Model files
        if (!$config['model']['skip']) {
            $count += 1; // Model class
        }
        
        // Controller files
        if (!$config['controller']['skip']) {
            $count += 1; // Controller class
            if ($config['controller']['with_validation']) {
                $count += 2; // Store and Update requests
            }
            if ($config['controller']['with_policy']) {
                $count += 1; // Policy class
            }
        }
        
        // View files
        if (!$config['views']['skip'] && $config['controller']['type'] === 'resource') {
            $viewCount = empty($config['views']['only']) ? 4 : count($config['views']['only']);
            $count += $viewCount;
        }
        
        // Factory files
        if ($config['factory']['generate']) {
            $count += 1;
        }
        
        // DataTable files
        if ($config['datatable']['generate']) {
            $count += 1;
        }
        
        // Migration files
        if ($config['migration']['generate']) {
            $count += 1;
        }
        
        return $count;
    }

    /**
     * Estimate total duration based on configuration.
     */
    protected function estimateDuration(array $config): int
    {
        // Base estimation per component type (in seconds)
        $estimates = [
            'model' => 5,
            'controller' => 8,
            'views' => 12,
            'factory' => 3,
            'datatable' => 6,
            'migration' => 4,
        ];
        
        $totalEstimate = 0;
        
        if (!$config['model']['skip']) {
            $totalEstimate += $estimates['model'];
        }
        
        if (!$config['controller']['skip']) {
            $totalEstimate += $estimates['controller'];
        }
        
        if (!$config['views']['skip']) {
            $totalEstimate += $estimates['views'];
        }
        
        if ($config['factory']['generate']) {
            $totalEstimate += $estimates['factory'];
        }
        
        if ($config['datatable']['generate']) {
            $totalEstimate += $estimates['datatable'];
        }
        
        if ($config['migration']['generate']) {
            $totalEstimate += $estimates['migration'];
        }
        
        return $totalEstimate;
    }

    /**
     * Get estimated time remaining.
     */
    protected function getEstimatedTimeRemaining(): ?int
    {
        if (!$this->plan || !$this->startTime) {
            return null;
        }

        $completedSteps = $this->completedSteps->count();
        $totalSteps = $this->plan->getSteps()->count();
        
        if ($completedSteps === 0) {
            return $this->statistics['estimated_duration'] ?? null;
        }

        $elapsedTime = now()->diffInSeconds($this->startTime);
        $averageTimePerStep = $elapsedTime / $completedSteps;
        $remainingSteps = $totalSteps - $completedSteps;
        
        return (int) ($remainingSteps * $averageTimePerStep);
    }

    /**
     * Get total execution time.
     */
    protected function getTotalExecutionTime(): ?int
    {
        if (!$this->startTime) {
            return null;
        }
        
        $endTime = $this->endTime ?? now();
        return $endTime->diffInSeconds($this->startTime);
    }

    /**
     * Get average step execution time.
     */
    protected function getAverageStepTime(): ?float
    {
        $durations = array_filter(array_column($this->stepTimes, 'duration'));
        
        if (empty($durations)) {
            return null;
        }
        
        return array_sum($durations) / count($durations);
    }

    /**
     * Get the slowest step.
     */
    protected function getSlowestStep(): ?array
    {
        $slowestDuration = 0;
        $slowestStep = null;
        
        foreach ($this->stepTimes as $package => $times) {
            if ($times['duration'] && $times['duration'] > $slowestDuration) {
                $slowestDuration = $times['duration'];
                $slowestStep = [
                    'package' => $package,
                    'duration' => $times['duration'],
                ];
            }
        }
        
        return $slowestStep;
    }

    /**
     * Get the fastest step.
     */
    protected function getFastestStep(): ?array
    {
        $fastestDuration = PHP_INT_MAX;
        $fastestStep = null;
        
        foreach ($this->stepTimes as $package => $times) {
            if ($times['duration'] && $times['duration'] < $fastestDuration) {
                $fastestDuration = $times['duration'];
                $fastestStep = [
                    'package' => $package,
                    'duration' => $times['duration'],
                ];
            }
        }
        
        return $fastestStep;
    }

    /**
     * Register a progress callback.
     */
    public function onProgress(string $event, callable $callback): void
    {
        if (!isset($this->callbacks[$event])) {
            $this->callbacks[$event] = [];
        }
        
        $this->callbacks[$event][] = $callback;
    }

    /**
     * Trigger progress callbacks.
     */
    protected function triggerCallback(string $event, array $data = []): void
    {
        if (isset($this->callbacks[$event])) {
            foreach ($this->callbacks[$event] as $callback) {
                try {
                    call_user_func($callback, $data);
                } catch (\Exception $e) {
                    Log::warning('Progress callback failed', [
                        'event' => $event,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Export progress data to array.
     */
    public function toArray(): array
    {
        return [
            'progress' => $this->getProgress(),
            'statistics' => $this->getStatistics(),
            'completed_steps' => $this->completedSteps->map(function ($step) {
                return [
                    'package' => $step->getPackage(),
                    'command' => $step->getCommand(),
                ];
            })->toArray(),
            'failed_steps' => $this->failedSteps->toArray(),
            'step_times' => $this->stepTimes,
        ];
    }
}