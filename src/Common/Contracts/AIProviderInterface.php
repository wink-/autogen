<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

interface AIProviderInterface
{
    /**
     * Generate code based on the given prompt.
     */
    public function generateCode(string $prompt, array $options = []): string;

    /**
     * Analyze code and provide suggestions.
     */
    public function analyzeCode(string $code, array $options = []): array;

    /**
     * Generate tests for the given code.
     */
    public function generateTests(string $code, array $options = []): string;

    /**
     * Generate documentation for the given code.
     */
    public function generateDocumentation(string $code, array $options = []): string;

    /**
     * Optimize the given code.
     */
    public function optimizeCode(string $code, array $options = []): string;

    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Check if the provider is available.
     */
    public function isAvailable(): bool;

    /**
     * Get the provider configuration.
     */
    public function getConfig(): array;
}