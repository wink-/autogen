<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

interface GeneratorInterface
{
    /**
     * Generate content based on the given context.
     */
    public function generate(array $context): string;

    /**
     * Set the AI provider for generation.
     */
    public function setAIProvider(AIProviderInterface $provider): void;

    /**
     * Set the template engine for generation.
     */
    public function setTemplateEngine(TemplateEngineInterface $engine): void;

    /**
     * Get the generator name.
     */
    public function getName(): string;

    /**
     * Get supported output formats.
     */
    public function getSupportedFormats(): array;

    /**
     * Validate the generation context.
     */
    public function validateContext(array $context): bool;

    /**
     * Get generation configuration.
     */
    public function getConfig(): array;
}