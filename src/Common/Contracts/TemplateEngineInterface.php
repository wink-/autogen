<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

interface TemplateEngineInterface
{
    /**
     * Render a template with the given variables.
     */
    public function render(string $template, array $variables = []): string;

    /**
     * Render a template file with the given variables.
     */
    public function renderFile(string $templatePath, array $variables = []): string;

    /**
     * Check if a template exists.
     */
    public function exists(string $template): bool;

    /**
     * Add a template path.
     */
    public function addPath(string $path): void;

    /**
     * Set template variables globally.
     */
    public function setGlobalVariables(array $variables): void;

    /**
     * Get available templates.
     */
    public function getAvailableTemplates(): array;

    /**
     * Cache a compiled template.
     */
    public function cacheTemplate(string $template, string $compiled): void;

    /**
     * Clear template cache.
     */
    public function clearCache(): void;
}