<?php

declare(strict_types=1);

namespace AutoGen\Common\AI;

use AutoGen\Common\Exceptions\AIProviderException;

class LocalLLMProvider extends BaseAIProvider
{
    protected string $name = 'local-llm';

    public function generateCode(string $prompt, array $options = []): string
    {
        throw new AIProviderException('Local LLM provider implementation not yet complete');
    }

    public function analyzeCode(string $code, array $options = []): array
    {
        throw new AIProviderException('Local LLM provider implementation not yet complete');
    }

    public function generateTests(string $code, array $options = []): string
    {
        throw new AIProviderException('Local LLM provider implementation not yet complete');
    }

    public function generateDocumentation(string $code, array $options = []): string
    {
        throw new AIProviderException('Local LLM provider implementation not yet complete');
    }

    public function optimizeCode(string $code, array $options = []): string
    {
        throw new AIProviderException('Local LLM provider implementation not yet complete');
    }

    protected function validateConfiguration(): void
    {
        $this->validateNotEmpty($this->getConfigValue('endpoint', ''), 'endpoint');
    }

    protected function hasRequiredCredentials(): bool
    {
        return !empty($this->getConfigValue('endpoint'));
    }

    protected function makeRequest(string $endpoint, array $data = []): array
    {
        throw new AIProviderException('Local LLM API implementation not yet complete');
    }
}