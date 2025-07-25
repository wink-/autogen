<?php

declare(strict_types=1);

namespace AutoGen\Common\AI;

use AutoGen\Common\Contracts\AIProviderInterface;
use AutoGen\Common\Exceptions\AIProviderException;
use AutoGen\Common\Traits\HasConfiguration;
use Illuminate\Support\Manager;

class AIProviderManager extends Manager implements AIProviderInterface
{
    use HasConfiguration;

    /**
     * Create a new AI provider manager instance.
     */
    public function __construct(array $config = [])
    {
        parent::__construct(app());
        $this->setConfig($config);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->getConfigValue('default', 'openai');
    }

    /**
     * Create the OpenAI driver.
     */
    protected function createOpenaiDriver(): AIProviderInterface
    {
        return new OpenAIProvider(
            $this->getConfigValue('providers.openai', [])
        );
    }

    /**
     * Create the Anthropic driver.
     */
    protected function createAnthropicDriver(): AIProviderInterface
    {
        return new AnthropicProvider(
            $this->getConfigValue('providers.anthropic', [])
        );
    }

    /**
     * Create the Google Gemini driver.
     */
    protected function createGoogleGeminiDriver(): AIProviderInterface
    {
        return new GoogleGeminiProvider(
            $this->getConfigValue('providers.google-gemini', [])
        );
    }

    /**
     * Create the Local LLM driver.
     */
    protected function createLocalLlmDriver(): AIProviderInterface
    {
        return new LocalLLMProvider(
            $this->getConfigValue('providers.local-llm', [])
        );
    }

    /**
     * Generate code using the default provider.
     */
    public function generateCode(string $prompt, array $options = []): string
    {
        return $this->driver()->generateCode($prompt, $options);
    }

    /**
     * Analyze code using the default provider.
     */
    public function analyzeCode(string $code, array $options = []): array
    {
        return $this->driver()->analyzeCode($code, $options);
    }

    /**
     * Generate tests using the default provider.
     */
    public function generateTests(string $code, array $options = []): string
    {
        return $this->driver()->generateTests($code, $options);
    }

    /**
     * Generate documentation using the default provider.
     */
    public function generateDocumentation(string $code, array $options = []): string
    {
        return $this->driver()->generateDocumentation($code, $options);
    }

    /**
     * Optimize code using the default provider.
     */
    public function optimizeCode(string $code, array $options = []): string
    {
        return $this->driver()->optimizeCode($code, $options);
    }

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return $this->driver()->getName();
    }

    /**
     * Check if the provider is available.
     */
    public function isAvailable(): bool
    {
        try {
            return $this->driver()->isAvailable();
        } catch (AIProviderException) {
            return false;
        }
    }

    /**
     * Execute a request with fallback support.
     */
    public function executeWithFallback(callable $callback): mixed
    {
        $retryAttempts = $this->getConfigValue('retry_attempts', 3);
        $retryDelay = $this->getConfigValue('retry_delay', 1000);
        $fallbackProvider = $this->getConfigValue('fallback_provider');

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                return $callback($this->driver());
            } catch (AIProviderException $e) {
                if ($attempt === $retryAttempts) {
                    if ($fallbackProvider && $fallbackProvider !== $this->getDefaultDriver()) {
                        try {
                            return $callback($this->driver($fallbackProvider));
                        } catch (AIProviderException) {
                            throw $e;
                        }
                    }
                    throw $e;
                }

                if ($retryDelay > 0) {
                    usleep($retryDelay * 1000);
                }
            }
        }

        throw new AIProviderException('Maximum retry attempts exceeded');
    }
}