<?php

declare(strict_types=1);

namespace AutoGen\Common\AI;

use AutoGen\Common\Contracts\AIProviderInterface;
use AutoGen\Common\Traits\HasConfiguration;
use AutoGen\Common\Traits\ValidatesInput;

abstract class BaseAIProvider implements AIProviderInterface
{
    use HasConfiguration;
    use ValidatesInput;

    /**
     * The provider name.
     */
    protected string $name;

    /**
     * Create a new AI provider instance.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->validateConfiguration();
    }

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the provider is available.
     */
    public function isAvailable(): bool
    {
        return $this->hasRequiredCredentials();
    }

    /**
     * Validate the provider configuration.
     */
    abstract protected function validateConfiguration(): void;

    /**
     * Check if required credentials are present.
     */
    abstract protected function hasRequiredCredentials(): bool;

    /**
     * Make an API request to the provider.
     */
    abstract protected function makeRequest(string $endpoint, array $data = []): array;

    /**
     * Build the prompt for the given task.
     */
    protected function buildPrompt(string $basePrompt, array $context = []): string
    {
        $prompt = $basePrompt;

        if (!empty($context)) {
            $contextStr = $this->formatContext($context);
            $prompt .= "\n\nContext:\n" . $contextStr;
        }

        return $prompt;
    }

    /**
     * Format context data for the prompt.
     */
    protected function formatContext(array $context): string
    {
        $formatted = [];
        
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
            }
            
            $formatted[] = "{$key}: {$value}";
        }

        return implode("\n", $formatted);
    }

    /**
     * Clean and validate generated code.
     */
    protected function cleanGeneratedCode(string $code): string
    {
        // Remove markdown code blocks if present
        $code = preg_replace('/^```(?:php)?\n?/', '', $code);
        $code = preg_replace('/\n?```$/', '', $code);

        // Remove opening PHP tag if not needed
        if (!str_starts_with(trim($code), '<?php')) {
            $code = "<?php\n\n" . $code;
        }

        // Ensure proper indentation
        $lines = explode("\n", $code);
        $indentLevel = 0;
        $indentSize = 4;
        $formattedLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            if (empty($trimmedLine)) {
                $formattedLines[] = '';
                continue;
            }

            // Adjust indent level based on braces
            if (str_contains($trimmedLine, '}')) {
                $indentLevel = max(0, $indentLevel - 1);
            }

            $formattedLines[] = str_repeat(' ', $indentLevel * $indentSize) . $trimmedLine;

            if (str_contains($trimmedLine, '{')) {
                $indentLevel++;
            }
        }

        return implode("\n", $formattedLines);
    }

    /**
     * Extract code from AI response.
     */
    protected function extractCode(string $response): string
    {
        // Look for code blocks
        if (preg_match('/```(?:php)?\n?(.*?)\n?```/s', $response, $matches)) {
            return $matches[1];
        }

        // If no code blocks, return the entire response
        return $response;
    }

    /**
     * Handle API errors.
     */
    protected function handleApiError(string $error, int $statusCode = 0): void
    {
        throw new \AutoGen\Common\Exceptions\AIProviderException(
            "API Error from {$this->getName()}: {$error}",
            $statusCode
        );
    }
}