<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

interface CodeFormatterInterface
{
    /**
     * Format PHP code according to the configured style.
     */
    public function formatPhp(string $code): string;

    /**
     * Format JavaScript/TypeScript code.
     */
    public function formatJavaScript(string $code): string;

    /**
     * Format JSON code.
     */
    public function formatJson(string $code): string;

    /**
     * Format code based on file extension.
     */
    public function formatByExtension(string $code, string $extension): string;

    /**
     * Fix code style issues.
     */
    public function fixStyle(string $code, array $rules = []): string;

    /**
     * Check if code meets style standards.
     */
    public function checkStyle(string $code): array;

    /**
     * Get the formatting configuration.
     */
    public function getConfig(): array;

    /**
     * Set formatting rules.
     */
    public function setRules(array $rules): void;
}