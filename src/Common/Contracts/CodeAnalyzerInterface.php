<?php

declare(strict_types=1);

namespace AutoGen\Common\Contracts;

interface CodeAnalyzerInterface
{
    /**
     * Analyze a file and return analysis results.
     */
    public function analyzeFile(string $filePath): array;

    /**
     * Analyze code string and return analysis results.
     */
    public function analyzeCode(string $code, string $fileType = 'php'): array;

    /**
     * Analyze a directory and return analysis results.
     */
    public function analyzeDirectory(string $directoryPath): array;

    /**
     * Get code metrics for the given code.
     */
    public function getMetrics(string $code): array;

    /**
     * Check code quality and return issues.
     */
    public function checkQuality(string $code): array;

    /**
     * Detect code smells and anti-patterns.
     */
    public function detectCodeSmells(string $code): array;

    /**
     * Get complexity analysis for the given code.
     */
    public function getComplexityAnalysis(string $code): array;

    /**
     * Check PSR compliance.
     */
    public function checkPSRCompliance(string $code): array;
}