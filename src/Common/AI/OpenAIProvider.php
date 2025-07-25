<?php

declare(strict_types=1);

namespace AutoGen\Common\AI;

use AutoGen\Common\Exceptions\AIProviderException;

class OpenAIProvider extends BaseAIProvider
{
    protected string $name = 'openai';

    /**
     * Generate code based on the given prompt.
     */
    public function generateCode(string $prompt, array $options = []): string
    {
        $this->validateNotEmpty($prompt, 'prompt');

        $data = [
            'model' => $this->getConfigValue('model', 'gpt-4-turbo'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful PHP code generator. Generate clean, well-documented, and PSR-12 compliant code.'
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($prompt, $options)
                ]
            ],
            'temperature' => $this->getConfigValue('temperature', 0.2),
            'max_tokens' => $this->getConfigValue('max_tokens', 4096),
        ];

        $response = $this->makeRequest('/chat/completions', $data);
        
        return $this->cleanGeneratedCode(
            $this->extractCode($response['choices'][0]['message']['content'] ?? '')
        );
    }

    /**
     * Analyze code and provide suggestions.
     */
    public function analyzeCode(string $code, array $options = []): array
    {
        $this->validateNotEmpty($code, 'code');

        $prompt = "Analyze the following PHP code and provide suggestions for improvements:\n\n{$code}";
        
        $data = [
            'model' => $this->getConfigValue('model', 'gpt-4-turbo'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a PHP code analysis expert. Provide detailed analysis including code quality, security, performance, and maintainability suggestions.'
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($prompt, $options)
                ]
            ],
            'temperature' => $this->getConfigValue('temperature', 0.2),
        ];

        $response = $this->makeRequest('/chat/completions', $data);
        
        // Parse the response and return structured analysis
        return $this->parseAnalysisResponse($response['choices'][0]['message']['content'] ?? '');
    }

    /**
     * Generate tests for the given code.
     */
    public function generateTests(string $code, array $options = []): string
    {
        $this->validateNotEmpty($code, 'code');

        $prompt = "Generate comprehensive PHPUnit tests for the following code:\n\n{$code}";
        
        $data = [
            'model' => $this->getConfigValue('model', 'gpt-4-turbo'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a PHP testing expert. Generate comprehensive PHPUnit tests with good coverage, edge cases, and clear test names.'
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($prompt, $options)
                ]
            ],
            'temperature' => $this->getConfigValue('temperature', 0.2),
        ];

        $response = $this->makeRequest('/chat/completions', $data);
        
        return $this->cleanGeneratedCode(
            $this->extractCode($response['choices'][0]['message']['content'] ?? '')
        );
    }

    /**
     * Generate documentation for the given code.
     */
    public function generateDocumentation(string $code, array $options = []): string
    {
        $this->validateNotEmpty($code, 'code');

        $prompt = "Generate comprehensive documentation for the following PHP code:\n\n{$code}";
        
        $data = [
            'model' => $this->getConfigValue('model', 'gpt-4-turbo'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a technical documentation expert. Generate clear, comprehensive documentation with examples.'
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($prompt, $options)
                ]
            ],
            'temperature' => $this->getConfigValue('temperature', 0.2),
        ];

        $response = $this->makeRequest('/chat/completions', $data);
        
        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Optimize the given code.
     */
    public function optimizeCode(string $code, array $options = []): string
    {
        $this->validateNotEmpty($code, 'code');

        $prompt = "Optimize the following PHP code for performance, readability, and maintainability:\n\n{$code}";
        
        $data = [
            'model' => $this->getConfigValue('model', 'gpt-4-turbo'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a PHP optimization expert. Improve code performance, readability, and maintainability while preserving functionality.'
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($prompt, $options)
                ]
            ],
            'temperature' => $this->getConfigValue('temperature', 0.2),
        ];

        $response = $this->makeRequest('/chat/completions', $data);
        
        return $this->cleanGeneratedCode(
            $this->extractCode($response['choices'][0]['message']['content'] ?? '')
        );
    }

    /**
     * Validate the provider configuration.
     */
    protected function validateConfiguration(): void
    {
        $this->validateNotEmpty($this->getConfigValue('api_key', ''), 'api_key');
    }

    /**
     * Check if required credentials are present.
     */
    protected function hasRequiredCredentials(): bool
    {
        return !empty($this->getConfigValue('api_key'));
    }

    /**
     * Make an API request to OpenAI.
     */
    protected function makeRequest(string $endpoint, array $data = []): array
    {
        // This would use the OpenAI PHP client in a real implementation
        // For now, this is a placeholder that would be implemented with the actual API calls
        throw new AIProviderException('OpenAI API implementation not yet complete');
    }

    /**
     * Parse analysis response into structured data.
     */
    protected function parseAnalysisResponse(string $response): array
    {
        // Parse the AI response and structure it
        // This would extract suggestions, issues, metrics, etc.
        return [
            'suggestions' => [],
            'issues' => [],
            'metrics' => [],
            'raw_response' => $response,
        ];
    }
}