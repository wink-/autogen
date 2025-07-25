<?php

declare(strict_types=1);

namespace AutoGen\Packages\Scaffold;

class WorkflowResult
{
    /**
     * Result status.
     *
     * @var string
     */
    protected string $status;

    /**
     * Configuration that was executed.
     *
     * @var array
     */
    protected array $configuration;

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
     * Error messages.
     *
     * @var array
     */
    protected array $errors;

    /**
     * Warning messages.
     *
     * @var array
     */
    protected array $warnings;

    /**
     * Result message.
     *
     * @var string
     */
    protected string $message;

    /**
     * Execution statistics.
     *
     * @var array
     */
    protected array $statistics;

    /**
     * Partial files (for rollback).
     *
     * @var array
     */
    protected array $partialFiles;

    /**
     * Result constants.
     */
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL_SUCCESS = 'partial_success';
    public const STATUS_FAILURE = 'failure';

    /**
     * Create a new workflow result.
     */
    public function __construct(
        string $status,
        array $configuration,
        array $generatedFiles = [],
        array $skippedFiles = [],
        array $errors = [],
        array $warnings = [],
        string $message = '',
        array $statistics = [],
        array $partialFiles = []
    ) {
        $this->status = $status;
        $this->configuration = $configuration;
        $this->generatedFiles = $generatedFiles;
        $this->skippedFiles = $skippedFiles;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->message = $message;
        $this->statistics = array_merge([
            'execution_time' => null,
            'total_files_processed' => count($generatedFiles) + count($skippedFiles),
            'success_rate' => $this->calculateSuccessRate(),
        ], $statistics);
        $this->partialFiles = $partialFiles;
    }

    /**
     * Create a successful result.
     */
    public static function success(
        array $configuration,
        array $generatedFiles = [],
        array $skippedFiles = [],
        string $message = '',
        array $statistics = []
    ): self {
        return new self(
            self::STATUS_SUCCESS,
            $configuration,
            $generatedFiles,
            $skippedFiles,
            [],
            [],
            $message ?: 'Scaffold generation completed successfully',
            $statistics
        );
    }

    /**
     * Create a partial success result.
     */
    public static function partialSuccess(
        array $configuration,
        array $generatedFiles = [],
        array $skippedFiles = [],
        array $errors = [],
        array $warnings = [],
        string $message = '',
        array $statistics = [],
        array $partialFiles = []
    ): self {
        return new self(
            self::STATUS_PARTIAL_SUCCESS,
            $configuration,
            $generatedFiles,
            $skippedFiles,
            $errors,
            $warnings,
            $message ?: 'Scaffold generation completed with some errors',
            $statistics,
            $partialFiles
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(
        array $configuration,
        string $message = '',
        array $errors = [],
        array $warnings = [],
        array $partialFiles = [],
        array $statistics = []
    ): self {
        return new self(
            self::STATUS_FAILURE,
            $configuration,
            [],
            [],
            $errors,
            $warnings,
            $message ?: 'Scaffold generation failed',
            $statistics,
            $partialFiles
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
     * Check if the result is a partial success.
     */
    public function isPartialSuccess(): bool
    {
        return $this->status === self::STATUS_PARTIAL_SUCCESS;
    }

    /**
     * Check if the result is a failure.
     */
    public function isFailure(): bool
    {
        return $this->status === self::STATUS_FAILURE;
    }

    /**
     * Check if there was any success (full or partial).
     */
    public function hasAnySuccess(): bool
    {
        return $this->isSuccess() || $this->isPartialSuccess();
    }

    /**
     * Check if there are partial files for rollback.
     */
    public function hasPartialSuccess(): bool
    {
        return !empty($this->partialFiles) || $this->isPartialSuccess();
    }

    /**
     * Get the result status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the configuration that was executed.
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
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
     * Get error messages.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get warning messages.
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get the result message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get execution statistics.
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * Get partial files.
     */
    public function getPartialFiles(): array
    {
        return $this->partialFiles;
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
     * Get error count.
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get warning count.
     */
    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Add an error message.
     */
    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Add a warning message.
     */
    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
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
     * Set statistics.
     */
    public function setStatistics(array $statistics): void
    {
        $this->statistics = array_merge($this->statistics, $statistics);
    }

    /**
     * Add a statistic.
     */
    public function addStatistic(string $key, $value): void
    {
        $this->statistics[$key] = $value;
    }

    /**
     * Get a specific statistic.
     */
    public function getStatistic(string $key, $default = null)
    {
        return $this->statistics[$key] ?? $default;
    }

    /**
     * Calculate success rate.
     */
    protected function calculateSuccessRate(): float
    {
        $total = $this->getTotalFileCount();
        
        if ($total === 0) {
            return 0.0;
        }
        
        return ($this->getGeneratedFileCount() / $total) * 100;
    }

    /**
     * Get a summary of the result.
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
            'error_count' => $this->getErrorCount(),
            'warning_count' => $this->getWarningCount(),
            'success_rate' => $this->getStatistic('success_rate'),
            'execution_time' => $this->getStatistic('execution_time'),
            'message' => $this->message,
        ];
    }

    /**
     * Get detailed information.
     */
    public function getDetails(): array
    {
        return [
            'summary' => $this->getSummary(),
            'configuration' => $this->configuration,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'statistics' => $this->statistics,
            'partial_files' => $this->partialFiles,
        ];
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_PARTIAL_SUCCESS => 'Partial Success',
            self::STATUS_FAILURE => 'Failure',
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
            self::STATUS_PARTIAL_SUCCESS => 'yellow',
            self::STATUS_FAILURE => 'red',
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
            self::STATUS_PARTIAL_SUCCESS => '⚠️',
            self::STATUS_FAILURE => '❌',
            default => '❓',
        };
    }

    /**
     * Check if rollback is recommended.
     */
    public function shouldRollback(): bool
    {
        return $this->isFailure() && (!empty($this->generatedFiles) || !empty($this->partialFiles));
    }

    /**
     * Get rollback instructions.
     */
    public function getRollbackInstructions(): array
    {
        if (!$this->shouldRollback()) {
            return [];
        }

        $instructions = [];
        
        if (!empty($this->generatedFiles) || !empty($this->partialFiles)) {
            $instructions[] = 'Run with --rollback to clean up generated files';
        }

        if ($this->isPartialSuccess()) {
            $instructions[] = 'Review and fix the errors, then run the command again';
        }

        return $instructions;
    }

    /**
     * Convert result to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'configuration' => $this->configuration,
            'generated_files' => $this->generatedFiles,
            'skipped_files' => $this->skippedFiles,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'message' => $this->message,
            'statistics' => $this->statistics,
            'partial_files' => $this->partialFiles,
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
            $data['configuration'] ?? [],
            $data['generated_files'] ?? [],
            $data['skipped_files'] ?? [],
            $data['errors'] ?? [],
            $data['warnings'] ?? [],
            $data['message'] ?? '',
            $data['statistics'] ?? [],
            $data['partial_files'] ?? []
        );
    }

    /**
     * Merge with another result.
     */
    public function merge(WorkflowResult $other): self
    {
        // Determine new status
        $newStatus = $this->status;
        if ($other->isFailure() || $this->isFailure()) {
            $newStatus = self::STATUS_FAILURE;
        } elseif ($other->isPartialSuccess() || $this->isPartialSuccess()) {
            $newStatus = self::STATUS_PARTIAL_SUCCESS;
        }

        return new self(
            $newStatus,
            array_merge($this->configuration, $other->configuration),
            array_merge($this->generatedFiles, $other->generatedFiles),
            array_merge($this->skippedFiles, $other->skippedFiles),
            array_merge($this->errors, $other->errors),
            array_merge($this->warnings, $other->warnings),
            $this->message . ($other->message ? "\n" . $other->message : ''),
            array_merge($this->statistics, $other->statistics),
            array_merge($this->partialFiles, $other->partialFiles)
        );
    }

    /**
     * String representation of the result.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s %s: %s (Generated: %d, Skipped: %d, Errors: %d)',
            $this->getStatusEmoji(),
            $this->getStatusLabel(),
            $this->message,
            $this->getGeneratedFileCount(),
            $this->getSkippedFileCount(),
            $this->getErrorCount()
        );
    }
}