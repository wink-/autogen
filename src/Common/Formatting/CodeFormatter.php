<?php

declare(strict_types=1);

namespace AutoGen\Common\Formatting;

use AutoGen\Common\Contracts\CodeFormatterInterface;
use AutoGen\Common\Traits\HasConfiguration;
use AutoGen\Common\Traits\ValidatesInput;

class CodeFormatter implements CodeFormatterInterface
{
    use HasConfiguration;
    use ValidatesInput;

    /**
     * Create a new code formatter instance.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig(array_merge($this->getDefaultConfig(), $config));
    }

    /**
     * Format PHP code according to the configured style.
     */
    public function formatPhp(string $code): string
    {
        $this->validateNotEmpty($code, 'code');

        // Remove extra whitespace
        $code = $this->normalizeWhitespace($code);
        
        // Fix indentation
        $code = $this->fixIndentation($code);
        
        // Fix line endings
        $code = $this->fixLineEndings($code);
        
        // Add final newline if configured
        if ($this->getConfigValue('final_newline', true)) {
            $code = rtrim($code) . "\n";
        }

        return $code;
    }

    /**
     * Format JavaScript/TypeScript code.
     */
    public function formatJavaScript(string $code): string
    {
        $this->validateNotEmpty($code, 'code');
        
        // Basic JavaScript formatting
        // In a real implementation, this would use a proper JS formatter
        return $this->normalizeWhitespace($code);
    }

    /**
     * Format JSON code.
     */
    public function formatJson(string $code): string
    {
        $this->validateNotEmpty($code, 'code');
        
        $decoded = json_decode($code, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $code; // Return original if invalid JSON
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Format code based on file extension.
     */
    public function formatByExtension(string $code, string $extension): string
    {
        $this->validateNotEmpty($code, 'code');
        $this->validateNotEmpty($extension, 'extension');

        return match (strtolower($extension)) {
            'php' => $this->formatPhp($code),
            'js', 'ts' => $this->formatJavaScript($code),
            'json' => $this->formatJson($code),
            default => $code,
        };
    }

    /**
     * Fix code style issues.
     */
    public function fixStyle(string $code, array $rules = []): string
    {
        $this->validateNotEmpty($code, 'code');

        $effectiveRules = array_merge($this->getConfigValue('rules', []), $rules);
        
        // Apply various style fixes based on rules
        foreach ($effectiveRules as $rule => $enabled) {
            if ($enabled) {
                $code = $this->applyStyleRule($code, $rule);
            }
        }

        return $code;
    }

    /**
     * Check if code meets style standards.
     */
    public function checkStyle(string $code): array
    {
        $this->validateNotEmpty($code, 'code');

        $issues = [];

        // Check line length
        $maxLength = $this->getConfigValue('line_length', 120);
        $lines = explode("\n", $code);
        
        foreach ($lines as $lineNumber => $line) {
            if (strlen($line) > $maxLength) {
                $issues[] = [
                    'line' => $lineNumber + 1,
                    'type' => 'line_length',
                    'message' => "Line exceeds {$maxLength} characters",
                ];
            }
        }

        // Check indentation
        $indentType = $this->getConfigValue('indent_type', 'space');
        $incorrectIndent = $indentType === 'space' ? "\t" : '    ';
        
        foreach ($lines as $lineNumber => $line) {
            if (str_starts_with($line, $incorrectIndent)) {
                $issues[] = [
                    'line' => $lineNumber + 1,
                    'type' => 'indentation',
                    'message' => "Incorrect indentation type (expected {$indentType})",
                ];
            }
        }

        // Check trailing whitespace
        if ($this->getConfigValue('trim_trailing_whitespace', true)) {
            foreach ($lines as $lineNumber => $line) {
                if (preg_match('/\s+$/', $line)) {
                    $issues[] = [
                        'line' => $lineNumber + 1,
                        'type' => 'trailing_whitespace',
                        'message' => 'Trailing whitespace found',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Set formatting rules.
     */
    public function setRules(array $rules): void
    {
        $this->setConfigValue('rules', array_merge($this->getConfigValue('rules', []), $rules));
    }

    /**
     * Get the default configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'style' => 'psr12',
            'line_length' => 120,
            'indent_size' => 4,
            'indent_type' => 'space',
            'line_ending' => 'lf',
            'final_newline' => true,
            'trim_trailing_whitespace' => true,
            'rules' => [
                'normalize_whitespace' => true,
                'fix_indentation' => true,
                'fix_line_endings' => true,
                'remove_trailing_whitespace' => true,
            ],
        ];
    }

    /**
     * Normalize whitespace in code.
     */
    protected function normalizeWhitespace(string $code): string
    {
        // Remove trailing whitespace from each line
        $lines = explode("\n", $code);
        $lines = array_map('rtrim', $lines);
        
        // Remove excessive blank lines (more than 2 consecutive)
        $normalized = [];
        $blankCount = 0;
        
        foreach ($lines as $line) {
            if (trim($line) === '') {
                $blankCount++;
                if ($blankCount <= 2) {
                    $normalized[] = $line;
                }
            } else {
                $blankCount = 0;
                $normalized[] = $line;
            }
        }

        return implode("\n", $normalized);
    }

    /**
     * Fix indentation in code.
     */
    protected function fixIndentation(string $code): string
    {
        $lines = explode("\n", $code);
        $indentSize = $this->getConfigValue('indent_size', 4);
        $indentType = $this->getConfigValue('indent_type', 'space');
        $indentChar = $indentType === 'space' ? ' ' : "\t";
        $indentUnit = $indentType === 'space' ? str_repeat(' ', $indentSize) : "\t";
        
        $indentLevel = 0;
        $fixedLines = [];

        foreach ($lines as $line) {
            $trimmedLine = ltrim($line);
            
            if (empty($trimmedLine)) {
                $fixedLines[] = '';
                continue;
            }

            // Adjust indent level based on braces
            if (preg_match('/^\s*}/', $trimmedLine)) {
                $indentLevel = max(0, $indentLevel - 1);
            }

            // Apply correct indentation
            $fixedLines[] = str_repeat($indentUnit, $indentLevel) . $trimmedLine;

            // Increase indent level after opening brace
            if (preg_match('/{\s*$/', $trimmedLine)) {
                $indentLevel++;
            }
        }

        return implode("\n", $fixedLines);
    }

    /**
     * Fix line endings in code.
     */
    protected function fixLineEndings(string $code): string
    {
        $lineEnding = $this->getConfigValue('line_ending', 'lf');
        
        // Normalize to LF first
        $code = str_replace(["\r\n", "\r"], "\n", $code);
        
        // Convert to desired line ending
        return match ($lineEnding) {
            'crlf' => str_replace("\n", "\r\n", $code),
            'cr' => str_replace("\n", "\r", $code),
            default => $code, // lf
        };
    }

    /**
     * Apply a specific style rule.
     */
    protected function applyStyleRule(string $code, string $rule): string
    {
        return match ($rule) {
            'normalize_whitespace' => $this->normalizeWhitespace($code),
            'fix_indentation' => $this->fixIndentation($code),
            'fix_line_endings' => $this->fixLineEndings($code),
            'remove_trailing_whitespace' => preg_replace('/[ \t]+$/m', '', $code),
            default => $code,
        };
    }
}