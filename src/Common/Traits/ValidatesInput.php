<?php

declare(strict_types=1);

namespace AutoGen\Common\Traits;

use InvalidArgumentException;

trait ValidatesInput
{
    /**
     * Validate that a string is not empty.
     */
    protected function validateNotEmpty(string $value, string $fieldName): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$fieldName} cannot be empty");
        }
    }

    /**
     * Validate that an array is not empty.
     */
    protected function validateArrayNotEmpty(array $value, string $fieldName): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException("{$fieldName} cannot be empty");
        }
    }

    /**
     * Validate that a value is in allowed values.
     */
    protected function validateInArray(mixed $value, array $allowed, string $fieldName): void
    {
        if (!in_array($value, $allowed, true)) {
            $allowedValues = implode(', ', $allowed);
            throw new InvalidArgumentException(
                "{$fieldName} must be one of: {$allowedValues}. Got: {$value}"
            );
        }
    }

    /**
     * Validate required fields in array.
     */
    protected function validateRequiredFields(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                throw new InvalidArgumentException("Required field '{$field}' is missing");
            }
        }
    }

    /**
     * Validate PHP class name format.
     */
    protected function validateClassName(string $className): void
    {
        if (!preg_match('/^[A-Z][a-zA-Z0-9_]*$/', $className)) {
            throw new InvalidArgumentException(
                "Invalid class name format: {$className}. Must start with uppercase letter and contain only alphanumeric characters and underscores."
            );
        }
    }

    /**
     * Validate PHP namespace format.
     */
    protected function validateNamespace(string $namespace): void
    {
        if (!preg_match('/^[A-Z][a-zA-Z0-9_\\\\]*[a-zA-Z0-9_]$/', $namespace)) {
            throw new InvalidArgumentException(
                "Invalid namespace format: {$namespace}"
            );
        }
    }

    /**
     * Validate file path format.
     */
    protected function validateFilePath(string $path): void
    {
        if (str_contains($path, '..')) {
            throw new InvalidArgumentException(
                "Invalid file path: {$path}. Path traversal not allowed."
            );
        }
    }

    /**
     * Validate that string matches pattern.
     */
    protected function validatePattern(string $value, string $pattern, string $fieldName): void
    {
        if (!preg_match($pattern, $value)) {
            throw new InvalidArgumentException(
                "{$fieldName} does not match required pattern: {$pattern}"
            );
        }
    }

    /**
     * Validate string length.
     */
    protected function validateLength(string $value, int $min, int $max, string $fieldName): void
    {
        $length = strlen($value);
        
        if ($length < $min) {
            throw new InvalidArgumentException(
                "{$fieldName} must be at least {$min} characters long. Got {$length}."
            );
        }
        
        if ($length > $max) {
            throw new InvalidArgumentException(
                "{$fieldName} must be no more than {$max} characters long. Got {$length}."
            );
        }
    }

    /**
     * Validate numeric range.
     */
    protected function validateRange(int|float $value, int|float $min, int|float $max, string $fieldName): void
    {
        if ($value < $min || $value > $max) {
            throw new InvalidArgumentException(
                "{$fieldName} must be between {$min} and {$max}. Got {$value}."
            );
        }
    }
}