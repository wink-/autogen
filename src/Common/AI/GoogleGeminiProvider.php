<?php

declare(strict_types=1);

namespace AutoGen\Common\AI;

use AutoGen\Common\Exceptions\AIProviderException;

class GoogleGeminiProvider extends BaseAIProvider
{
    protected string $name = 'google-gemini';

    public function generateCode(string $prompt, array $options = []): string
    {
        throw new AIProviderException('Google Gemini provider implementation not yet complete');
    }

    public function analyzeCode(string $code, array $options = []): array
    {
        throw new AIProviderException('Google Gemini provider implementation not yet complete');
    }

    public function generateTests(string $code, array $options = []): string
    {
        throw new AIProviderException('Google Gemini provider implementation not yet complete');
    }

    public function generateDocumentation(string $code, array $options = []): string
    {
        throw new AIProviderException('Google Gemini provider implementation not yet complete');
    }

    public function optimizeCode(string $code, array $options = []): string
    {
        throw new AIProviderException('Google Gemini provider implementation not yet complete');
    }

    protected function validateConfiguration(): void
    {
        $this->validateNotEmpty($this->getConfigValue('api_key', ''), 'api_key');
    }

    protected function hasRequiredCredentials(): bool
    {
        return !empty($this->getConfigValue('api_key'));
    }

    protected function makeRequest(string $endpoint, array $data = []): array
    {
        throw new AIProviderException('Google Gemini API implementation not yet complete');
    }
}