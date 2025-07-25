<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

use Illuminate\Support\Collection;

class DependencyResolver
{
    /**
     * Package dependency matrix.
     * Maps package -> dependencies.
     *
     * @var array
     */
    protected array $dependencyMatrix = [
        'model' => [],
        'controller' => ['model'],
        'views' => ['model', 'controller'],
        'factory' => ['model'],
        'datatable' => ['model', 'views'],
        'migration' => [],
    ];

    /**
     * Resolve dependencies and return optimized execution order.
     */
    public function resolveDependencies(Collection $steps): Collection
    {
        // Create a dependency graph
        $graph = $this->buildDependencyGraph($steps);
        
        // Perform topological sort
        $sorted = $this->topologicalSort($graph);
        
        // Return steps in resolved order
        return $sorted->sortBy('order')->values();
    }

    /**
     * Build dependency graph from execution steps.
     */
    protected function buildDependencyGraph(Collection $steps): Collection
    {
        $graph = collect();
        
        foreach ($steps as $step) {
            $packageName = $step->getPackage();
            
            // Get dependencies for this package
            $dependencies = $this->getDependenciesForPackage($packageName, $steps);
            
            // Update step with resolved dependencies
            $step->setResolvedDependencies($dependencies);
            
            $graph->push($step);
        }
        
        return $graph;
    }

    /**
     * Get dependencies for a specific package considering available steps.
     */
    protected function getDependenciesForPackage(string $package, Collection $availableSteps): array
    {
        $allDependencies = $this->dependencyMatrix[$package] ?? [];
        $availablePackages = $availableSteps->pluck('package')->toArray();
        
        // Only include dependencies that are actually being generated
        return array_intersect($allDependencies, $availablePackages);
    }

    /**
     * Perform topological sort using Kahn's algorithm.
     */
    protected function topologicalSort(Collection $graph): Collection
    {
        $sorted = collect();
        $inDegree = [];
        $adjacencyList = [];
        
        // Initialize in-degree count and adjacency list
        foreach ($graph as $step) {
            $package = $step->getPackage();
            $inDegree[$package] = 0;
            $adjacencyList[$package] = [];
        }
        
        // Build adjacency list and calculate in-degrees
        foreach ($graph as $step) {
            $package = $step->getPackage();
            $dependencies = $step->getResolvedDependencies();
            
            foreach ($dependencies as $dependency) {
                if (isset($adjacencyList[$dependency])) {
                    $adjacencyList[$dependency][] = $package;
                    $inDegree[$package]++;
                }
            }
        }
        
        // Find nodes with no incoming edges
        $queue = collect();
        foreach ($inDegree as $package => $degree) {
            if ($degree === 0) {
                $step = $graph->firstWhere('package', $package);
                if ($step) {
                    $queue->push($step);
                }
            }
        }
        
        // Process queue
        $order = 1;
        while ($queue->isNotEmpty()) {
            $currentStep = $queue->shift();
            $currentPackage = $currentStep->getPackage();
            
            // Set execution order
            $currentStep->setOrder($order++);
            $sorted->push($currentStep);
            
            // Reduce in-degree for dependent packages
            foreach ($adjacencyList[$currentPackage] as $dependentPackage) {
                $inDegree[$dependentPackage]--;
                
                if ($inDegree[$dependentPackage] === 0) {
                    $dependentStep = $graph->firstWhere('package', $dependentPackage);
                    if ($dependentStep) {
                        $queue->push($dependentStep);
                    }
                }
            }
        }
        
        // Check for circular dependencies
        if ($sorted->count() !== $graph->count()) {
            throw new \RuntimeException('Circular dependency detected in scaffold packages');
        }
        
        return $sorted;
    }

    /**
     * Validate that all dependencies can be satisfied.
     */
    public function validateDependencies(Collection $steps): array
    {
        $errors = [];
        $availablePackages = $steps->pluck('package')->toArray();
        
        foreach ($steps as $step) {
            $package = $step->getPackage();
            $requiredDependencies = $this->dependencyMatrix[$package] ?? [];
            
            foreach ($requiredDependencies as $dependency) {
                if (!in_array($dependency, $availablePackages)) {
                    // Check if the dependency is actually required
                    if ($this->isDependencyRequired($package, $dependency, $steps)) {
                        $errors[] = "Package '{$package}' requires '{$dependency}' but it's not being generated";
                    }
                }
            }
        }
        
        return $errors;
    }

    /**
     * Check if a dependency is actually required given the current configuration.
     */
    protected function isDependencyRequired(string $package, string $dependency, Collection $steps): bool
    {
        // Special cases where dependencies might be optional
        switch ($package) {
            case 'controller':
                // Controller doesn't strictly require model if it's an API controller
                // without model binding, but generally it does
                return $dependency === 'model';
                
            case 'views':
                // Views require model and controller for proper CRUD functionality
                return in_array($dependency, ['model', 'controller']);
                
            case 'factory':
                // Factory requires model
                return $dependency === 'model';
                
            case 'datatable':
                // DataTable requires model, views are recommended but not strict requirement
                return $dependency === 'model';
                
            default:
                return true;
        }
    }

    /**
     * Get execution timeline showing when each package will run.
     */
    public function getExecutionTimeline(Collection $steps): array
    {
        $timeline = [];
        $resolvedSteps = $this->resolveDependencies($steps);
        
        $currentLevel = 1;
        $processedPackages = [];
        
        while ($resolvedSteps->isNotEmpty()) {
            $readyToExecute = [];
            
            foreach ($resolvedSteps as $key => $step) {
                $dependencies = $step->getResolvedDependencies();
                
                // Check if all dependencies are satisfied
                if (empty(array_diff($dependencies, $processedPackages))) {
                    $readyToExecute[] = $step;
                    $processedPackages[] = $step->getPackage();
                    $resolvedSteps->forget($key);
                }
            }
            
            if (empty($readyToExecute)) {
                // This shouldn't happen if dependencies are resolved correctly
                throw new \RuntimeException('Unable to resolve execution timeline - possible circular dependency');
            }
            
            $timeline[$currentLevel] = $readyToExecute;
            $currentLevel++;
        }
        
        return $timeline;
    }

    /**
     * Get packages that can run in parallel.
     */
    public function getParallelizableGroups(Collection $steps): array
    {
        $groups = [];
        $timeline = $this->getExecutionTimeline($steps);
        
        foreach ($timeline as $level => $stepsAtLevel) {
            if (count($stepsAtLevel) > 1) {
                $groups[$level] = array_map(function ($step) {
                    return $step->getPackage();
                }, $stepsAtLevel);
            }
        }
        
        return $groups;
    }

    /**
     * Analyze critical path through the dependency graph.
     */
    public function getCriticalPath(Collection $steps): array
    {
        $resolvedSteps = $this->resolveDependencies($steps);
        $criticalPath = [];
        
        // Find the longest path through the dependency graph
        $longestPaths = [];
        
        foreach ($resolvedSteps as $step) {
            $longestPaths[$step->getPackage()] = $this->calculateLongestPath($step, $resolvedSteps);
        }
        
        // The critical path is the longest path in the graph
        $maxLength = max($longestPaths);
        
        foreach ($longestPaths as $package => $length) {
            if ($length === $maxLength) {
                $criticalPath[] = $package;
            }
        }
        
        return $criticalPath;
    }

    /**
     * Calculate the longest path from a given step to the end.
     */
    protected function calculateLongestPath(ExecutionStep $step, Collection $allSteps): int
    {
        $dependencies = $step->getResolvedDependencies();
        
        if (empty($dependencies)) {
            return 1;
        }
        
        $maxDependencyPath = 0;
        
        foreach ($dependencies as $dependency) {
            $dependencyStep = $allSteps->firstWhere('package', $dependency);
            if ($dependencyStep) {
                $path = $this->calculateLongestPath($dependencyStep, $allSteps);
                $maxDependencyPath = max($maxDependencyPath, $path);
            }
        }
        
        return $maxDependencyPath + 1;
    }

    /**
     * Check if a package has any dependents.
     */
    public function hasDependents(string $package, Collection $steps): bool
    {
        foreach ($steps as $step) {
            $dependencies = $this->getDependenciesForPackage($step->getPackage(), $steps);
            if (in_array($package, $dependencies)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get all packages that depend on the given package.
     */
    public function getDependents(string $package, Collection $steps): array
    {
        $dependents = [];
        
        foreach ($steps as $step) {
            $dependencies = $this->getDependenciesForPackage($step->getPackage(), $steps);
            if (in_array($package, $dependencies)) {
                $dependents[] = $step->getPackage();
            }
        }
        
        return $dependents;
    }

    /**
     * Add a custom dependency relationship.
     */
    public function addDependency(string $package, string $dependency): void
    {
        if (!isset($this->dependencyMatrix[$package])) {
            $this->dependencyMatrix[$package] = [];
        }
        
        if (!in_array($dependency, $this->dependencyMatrix[$package])) {
            $this->dependencyMatrix[$package][] = $dependency;
        }
    }

    /**
     * Remove a dependency relationship.
     */
    public function removeDependency(string $package, string $dependency): void
    {
        if (isset($this->dependencyMatrix[$package])) {
            $this->dependencyMatrix[$package] = array_diff(
                $this->dependencyMatrix[$package], 
                [$dependency]
            );
        }
    }

    /**
     * Get the current dependency matrix.
     */
    public function getDependencyMatrix(): array
    {
        return $this->dependencyMatrix;
    }
}