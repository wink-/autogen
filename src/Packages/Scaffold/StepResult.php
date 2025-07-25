<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

class StepResult
{
    /**
     * Result status.
     *
     * @var string
     */
    protected string $status;

    /**
     * Generated files.
     *
     * @var array
     */
    protected array $generatedFiles;

    /**
     * Skipped files.
     *
     * @var array
     */
    protected array $skippedFiles;

    /**
     * Error message.
     *
     * @var string
     */
    protected string $error;

    /**
     * Warning messages.
     *
     * @var array
     */
    protected array $warnings;

    /**
     * Step execution data.
     *
     * @var array
     */
    protected array $data;

    /**
     * Execution time in seconds.
     *
     * @var float|null
     */
    protected ?float $executionTime;

    /**
     * Result constants.
     */
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * Create a new step result.
     */
    public function __construct(
        string $status,
        array $generatedFiles = [],
        array $skippedFiles = [],
        string $error = '',
        array $warnings = [],
        array $data = [],
        ?float $executionTime = null
    ) {
        $this->status = $status;
        $this->generatedFiles = $generatedFiles;
        $this->skippedFiles = $skippedFiles;
        $this->error = $error;
        $this->warnings = $warnings;
        $this->data = $data;
        $this->executionTime = $executionTime;
    }

    /**
     * Create a successful result.
     */
    public static function success(
        array $generatedFiles = [],
        array $skippedFiles = [],
        array $warnings = [],
        array $data = [],
        ?float $executionTime = null
    ): self {
        return new self(
            self::STATUS_SUCCESS,
            $generatedFiles,
            $skippedFiles,
            '',
            $warnings,
            $data,
            $executionTime
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(
        string $error,
        array $generatedFiles = [],
        array $skippedFiles = [],
        array $warnings = [],
        array $data = [],
        ?float $executionTime = null
    ): self {
        return new self(
            self::STATUS_FAILURE,
            $generatedFiles,
            $skippedFiles,
            $error,
            $warnings,
            $data,
            $executionTime
        );
    }

    /**
     * Create a skipped result.
     */
    public static function skipped(
        string $reason = '',
        array $warnings = [],
        array $data = []
    ): self {
        return new self(
            self::STATUS_SKIPPED,
            [],
            [],
            $reason,
            $warnings,
            $data,
            0.0
        );
    }

    /**
     * Check if the result is successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if the result is a failure.
     */
    public function isFailure(): bool
    {
        return $this->status === self::STATUS_FAILURE;
    }

    /**
     * Check if the result was skipped.
     */
    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    /**
     * Get the result status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get generated files.
     */
    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }

    /**
     * Get skipped files.
     */
    public function getSkippedFiles(): array
    {
        return $this->skippedFiles;
    }

    /**
     * Get error message.
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Get warning messages.
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get execution data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get execution time.
     */
    public function getExecutionTime(): ?float
    {
        return $this->executionTime;
    }

    /**
     * Set execution time.
     */
    public function setExecutionTime(float $executionTime): void
    {
        $this->executionTime = $executionTime;
    }

    /**
     * Get all processed files (generated + skipped).
     */
    public function getAllProcessedFiles(): array
    {
        return array_merge($this->generatedFiles, $this->skippedFiles);
    }

    /**
     * Get total file count.
     */
    public function getTotalFileCount(): int
    {
        return count($this->generatedFiles) + count($this->skippedFiles);
    }

    /**
     * Get generated file count.
     */
    public function getGeneratedFileCount(): int
    {
        return count($this->generatedFiles);
    }

    /**
     * Get skipped file count.
     */
    public function getSkippedFileCount(): int
    {
        return count($this->skippedFiles);
    }

    /**
     * Get warning count.
     */
    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Check if any files were processed.
     */
    public function hasProcessedFiles(): bool
    {
        return $this->getTotalFileCount() > 0;
    }

    /**
     * Add a generated file.
     */
    public function addGeneratedFile(string $file): void
    {
        if (!in_array($file, $this->generatedFiles)) {
            $this->generatedFiles[] = $file;
        }
    }

    /**
     * Add a skipped file.
     */
    public function addSkippedFile(string $file): void
    {
        if (!in_array($file, $this->skippedFiles)) {
            $this->skippedFiles[] = $file;
        }
    }

    /**
     * Add a warning message.
     */
    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * Set execution data.
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Add data item.
     */
    public function addData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a specific data item.
     */
    public function getDataItem(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if data item exists.
     */
    public function hasDataItem(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_FAILURE => 'Failure', 
            self::STATUS_SKIPPED => 'Skipped',
            default => 'Unknown',
        };
    }

    /**
     * Get status color for CLI output.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'green',
            self::STATUS_FAILURE => 'red',
            self::STATUS_SKIPPED => 'yellow',
            default => 'white',
        };
    }

    /**
     * Get status emoji.
     */
    public function getStatusEmoji(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => '✅',
            self::STATUS_FAILURE => '❌',
            self::STATUS_SKIPPED => '⏭️',
            default => '❓',
        };
    }

    /**
     * Get result summary.
     */
    public function getSummary(): array
    {
        return [
            'status' => $this->status,
            'success' => $this->isSuccess(),
            'generated_files' => $this->generatedFiles,
            'skipped_files' => $this->skippedFiles,
            'total_files' => $this->getTotalFileCount(),
            'generated_count' => $this->getGeneratedFileCount(),
            'skipped_count' => $this->getSkippedFileCount(),
            'warning_count' => $this->getWarningCount(),
            'execution_time' => $this->executionTime,
            'error' => $this->error,
        ];
    }

    /**
     * Get detailed information.
     */
    public function getDetails(): array
    {
        return [
            'summary' => $this->getSummary(),
            'warnings' => $this->warnings,
            'data' => $this->data,
        ];
    }

    /**
     * Merge with another step result.
     */
    public function merge(StepResult $other): self
    {
        // Determine new status
        $newStatus = $this->status;
        if ($other->isFailure() || $this->isFailure()) {
            $newStatus = self::STATUS_FAILURE;
        } elseif ($other->isSkipped() && $this->isSkipped()) {
            $newStatus = self::STATUS_SKIPPED;
        }

        // Combine error messages
        $newError = $this->error;
        if ($other->error) {
            $newError = $newError ? $newError . '; ' . $other->error : $other->error;
        }

        // Combine execution times
        $newExecutionTime = null;
        if ($this->executionTime !== null && $other->executionTime !== null) {
            $newExecutionTime = $this->executionTime + $other->executionTime;
        } elseif ($this->executionTime !== null) {
            $newExecutionTime = $this->executionTime;
        } elseif ($other->executionTime !== null) {
            $newExecutionTime = $other->executionTime;
        }

        return new self(
            $newStatus,
            array_merge($this->generatedFiles, $other->generatedFiles),
            array_merge($this->skippedFiles, $other->skippedFiles),
            $newError,
            array_merge($this->warnings, $other->warnings),
            array_merge($this->data, $other->data),
            $newExecutionTime
        );
    }

    /**
     * Convert result to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'generated_files' => $this->generatedFiles,
            'skipped_files' => $this->skippedFiles,
            'error' => $this->error,
            'warnings' => $this->warnings,
            'data' => $this->data,
            'execution_time' => $this->executionTime,
            'summary' => $this->getSummary(),
        ];
    }

    /**
     * Create result from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['status'] ?? self::STATUS_FAILURE,
            $data['generated_files'] ?? [],
            $data['skipped_files'] ?? [],
            $data['error'] ?? '',
            $data['warnings'] ?? [],
            $data['data'] ?? [],
            $data['execution_time'] ?? null
        );
    }

    /**
     * Create result from command exit code.
     */
    public static function fromExitCode(
        int $exitCode,
        array $generatedFiles = [],
        array $skippedFiles = [],
        string $error = '',
        array $warnings = [],
        array $data = [],
        ?float $executionTime = null
    ): self {
        if ($exitCode === 0) {
            return self::success($generatedFiles, $skippedFiles, $warnings, $data, $executionTime);
        } else {
            return self::failure(
                $error ?: "Command failed with exit code {$exitCode}",
                $generatedFiles,
                $skippedFiles,
                $warnings,
                $data,
                $executionTime
            );
        }
    }

    /**
     * Create result from exception.
     */
    public static function fromException(
        \Exception $exception,
        array $generatedFiles = [],
        array $skippedFiles = [],
        array $warnings = [],
        array $data = [],
        ?float $executionTime = null
    ): self {
        return self::failure(
            $exception->getMessage(),
            $generatedFiles,
            $skippedFiles,
            $warnings,
            array_merge($data, [
                'exception_class' => get_class($exception),
                'exception_code' => $exception->getCode(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
            ]),
            $executionTime
        );
    }

    /**
     * String representation of the result.
     */
    public function __toString(): string
    {
        $parts = [
            $this->getStatusEmoji() . ' ' . $this->getStatusLabel()
        ];

        if ($this->getTotalFileCount() > 0) {
            $parts[] = sprintf(
                'Generated: %d, Skipped: %d',
                $this->getGeneratedFileCount(),
                $this->getSkippedFileCount()
            );
        }

        if ($this->hasWarnings()) {
            $parts[] = sprintf('Warnings: %d', $this->getWarningCount());
        }

        if ($this->error) {
            $parts[] = 'Error: ' . $this->error;
        }

        if ($this->executionTime !== null) {
            $parts[] = sprintf('Time: %.2fs', $this->executionTime);
        }

        return implode(' | ', $parts);
    }

    /**
     * Clone the result with modifications.
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
     * Magic clone method to ensure deep cloning.
     */
    public function __clone()
    {
        // Deep clone arrays
        $this->generatedFiles = array_slice($this->generatedFiles, 0);
        $this->skippedFiles = array_slice($this->skippedFiles, 0);
        $this->warnings = array_slice($this->warnings, 0);
        $this->data = array_merge([], $this->data);
    }
}