<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

class ExecutionStep
{
    /**
     * Execution order.
     *
     * @var int
     */
    protected int $order;

    /**
     * Package name.
     *
     * @var string
     */
    protected string $package;

    /**
     * Command to execute.
     *
     * @var string
     */
    protected string $command;

    /**
     * Human-readable description.
     *
     * @var string
     */
    protected string $description;

    /**
     * Dependencies (other packages this step depends on).
     *
     * @var array
     */
    protected array $dependencies;

    /**
     * Whether this step is critical for the overall process.
     *
     * @var bool
     */
    protected bool $isCritical;

    /**
     * Command parameters.
     *
     * @var array
     */
    protected array $parameters;

    /**
     * Resolved dependencies (computed by dependency resolver).
     *
     * @var array
     */
    protected array $resolvedDependencies = [];

    /**
     * Step metadata.
     *
     * @var array
     */
    protected array $metadata;

    /**
     * Create a new execution step.
     */
    public function __construct(
        int $order,
        string $package,
        string $command,
        string $description,
        array $dependencies = [],
        bool $isCritical = true,
        array $parameters = [],
        array $metadata = []
    ) {
        $this->order = $order;
        $this->package = $package;
        $this->command = $command;
        $this->description = $description;
        $this->dependencies = $dependencies;
        $this->isCritical = $isCritical;
        $this->parameters = $parameters;
        $this->metadata = array_merge([
            'created_at' => now(),
            'estimated_duration' => $this->getEstimatedDuration(),
        ], $metadata);
    }

    /**
     * Get execution order.
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Set execution order.
     */
    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    /**
     * Get package name.
     */
    public function getPackage(): string
    {
        return $this->package;
    }

    /**
     * Get command to execute.
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get dependencies.
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Set dependencies.
     */
    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }

    /**
     * Get resolved dependencies.
     */
    public function getResolvedDependencies(): array
    {
        return $this->resolvedDependencies;
    }

    /**
     * Set resolved dependencies.
     */
    public function setResolvedDependencies(array $resolvedDependencies): void
    {
        $this->resolvedDependencies = $resolvedDependencies;
    }

    /**
     * Check if this step is critical.
     */
    public function isCritical(): bool
    {
        return $this->isCritical;
    }

    /**
     * Set critical status.
     */
    public function setCritical(bool $isCritical): void
    {
        $this->isCritical = $isCritical;
    }

    /**
     * Get command parameters.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set command parameters.
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Add a parameter.
     */
    public function addParameter(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }

    /**
     * Remove a parameter.
     */
    public function removeParameter(string $key): void
    {
        unset($this->parameters[$key]);
    }

    /**
     * Get a specific parameter.
     */
    public function getParameter(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Check if parameter exists.
     */
    public function hasParameter(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Get step metadata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set metadata.
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Add metadata item.
     */
    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    /**
     * Get a specific metadata item.
     */
    public function getMetadataItem(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if this step has dependencies.
     */
    public function hasDependencies(): bool
    {
        return !empty($this->dependencies);
    }

    /**
     * Check if a specific dependency exists.
     */
    public function hasDependency(string $dependency): bool
    {
        return in_array($dependency, $this->dependencies);
    }

    /**
     * Add a dependency.
     */
    public function addDependency(string $dependency): void
    {
        if (!$this->hasDependency($dependency)) {
            $this->dependencies[] = $dependency;
        }
    }

    /**
     * Remove a dependency.
     */
    public function removeDependency(string $dependency): void
    {
        $this->dependencies = array_values(array_diff($this->dependencies, [$dependency]));
    }

    /**
     * Get estimated duration for this step.
     */
    public function getEstimatedDuration(): int
    {
        $estimates = [
            'model' => 5,
            'controller' => 8,
            'views' => 12,
            'factory' => 3,
            'datatable' => 6,
            'migration' => 4,
        ];

        return $estimates[$this->package] ?? 3;
    }

    /**
     * Check if this step can run in parallel with another step.
     */
    public function canRunInParallelWith(ExecutionStep $other): bool
    {
        // Can't run in parallel if there are dependencies between them
        if ($this->hasDependency($other->getPackage()) || $other->hasDependency($this->package)) {
            return false;
        }

        // Can't run in parallel if they have different orders
        if ($this->order !== $other->getOrder()) {
            return false;
        }

        return true;
    }

    /**
     * Check if this step depends on another step.
     */
    public function dependsOn(ExecutionStep $other): bool
    {
        return $this->hasDependency($other->getPackage());
    }

    /**
     * Check if another step depends on this step.
     */
    public function isRequiredBy(ExecutionStep $other): bool
    {
        return $other->hasDependency($this->package);
    }

    /**
     * Get the full command line that would be executed.
     */
    public function getFullCommand(): string
    {
        $command = $this->command;
        
        foreach ($this->parameters as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $command .= " {$key}";
                }
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    $command .= " {$key}=" . escapeshellarg($item);
                }
            } else {
                $command .= " {$key}=" . escapeshellarg($value);
            }
        }

        return $command;
    }

    /**
     * Validate the step configuration.
     */
    public function validate(): array
    {
        $errors = [];

        // Check required fields
        if (empty($this->package)) {
            $errors[] = 'Package name is required';
        }

        if (empty($this->command)) {
            $errors[] = 'Command is required';
        }

        if ($this->order < 1) {
            $errors[] = 'Order must be greater than 0';
        }

        // Validate package name
        if (!preg_match('/^[a-z_]+$/', $this->package)) {
            $errors[] = 'Package name must contain only lowercase letters and underscores';
        }

        // Validate command format
        if (!preg_match('/^[a-z:_-]+$/', $this->command)) {
            $errors[] = 'Command must be a valid artisan command format';
        }

        return $errors;
    }

    /**
     * Clone the step with modifications.
     */
    public function clone(array $modifications = []): self
    {
        $new = clone $this;
        
        foreach ($modifications as $property => $value) {
            if (property_exists($new, $property)) {
                $new->$property = $value;
            }
        }

        return $new;
    }

    /**
     * Convert step to array.
     */
    public function toArray(): array
    {
        return [
            'order' => $this->order,
            'package' => $this->package,
            'command' => $this->command,
            'description' => $this->description,
            'dependencies' => $this->dependencies,
            'resolved_dependencies' => $this->resolvedDependencies,
            'is_critical' => $this->isCritical,
            'parameters' => $this->parameters,
            'metadata' => $this->metadata,
            'full_command' => $this->getFullCommand(),
            'estimated_duration' => $this->getEstimatedDuration(),
        ];
    }

    /**
     * Create step from array data.
     */
    public static function fromArray(array $data): self
    {
        $step = new self(
            $data['order'] ?? 1,
            $data['package'] ?? '',
            $data['command'] ?? '',
            $data['description'] ?? '',
            $data['dependencies'] ?? [],
            $data['is_critical'] ?? true,
            $data['parameters'] ?? [],
            $data['metadata'] ?? []
        );

        if (isset($data['resolved_dependencies'])) {
            $step->setResolvedDependencies($data['resolved_dependencies']);
        }

        return $step;
    }

    /**
     * String representation of the step.
     */
    public function __toString(): string
    {
        return sprintf(
            '%d. %s (%s) - %s',
            $this->order,
            $this->package,
            $this->command,
            $this->description
        );
    }

    /**
     * Compare two steps for sorting.
     */
    public function compareTo(ExecutionStep $other): int
    {
        // First compare by order
        if ($this->order !== $other->order) {
            return $this->order <=> $other->order;
        }

        // Then by criticality (critical steps first)
        if ($this->isCritical !== $other->isCritical) {
            return $other->isCritical <=> $this->isCritical;
        }

        // Finally by package name
        return $this->package <=> $other->package;
    }

    /**
     * Magic clone method to ensure deep cloning.
     */
    public function __clone()
    {
        // Deep clone arrays
        $this->dependencies = array_slice($this->dependencies, 0);
        $this->resolvedDependencies = array_slice($this->resolvedDependencies, 0);
        $this->parameters = array_merge([], $this->parameters);
        $this->metadata = array_merge([], $this->metadata);
    }
}