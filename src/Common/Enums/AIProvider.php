<?php

declare(strict_types=1);

namespace AutoGen\Common\Enums;

/**
 * AI Provider Enum
 * 
 * Represents supported AI providers for code generation.
 */
enum AIProvider: string
{
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';
    case Local = 'local';

    /**
     * Get the display label for the AI provider.
     */
    public function label(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI GPT',
            self::Anthropic => 'Anthropic Claude',
            self::Gemini => 'Google Gemini',
            self::Local => 'Local LLM',
        };
    }

    /**
     * Get the description for the AI provider.
     */
    public function description(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI GPT models for code generation and analysis',
            self::Anthropic => 'Anthropic Claude models for intelligent code generation',
            self::Gemini => 'Google Gemini models for advanced code assistance',
            self::Local => 'Local LLM instance (Ollama, LM Studio, etc.)',
        };
    }

    /**
     * Get the default model for this provider.
     */
    public function defaultModel(): string
    {
        return match ($this) {
            self::OpenAI => 'gpt-4',
            self::Anthropic => 'claude-3-sonnet-20240229',
            self::Gemini => 'gemini-pro',
            self::Local => 'llama2',
        };
    }

    /**
     * Get the API endpoint for this provider.
     */
    public function apiEndpoint(): ?string
    {
        return match ($this) {
            self::OpenAI => 'https://api.openai.com/v1',
            self::Anthropic => 'https://api.anthropic.com/v1',
            self::Gemini => 'https://generativelanguage.googleapis.com/v1',
            self::Local => null, // Configured per installation
        };
    }

    /**
     * Get required environment variables for this provider.
     */
    public function requiredEnvVars(): array
    {
        return match ($this) {
            self::OpenAI => ['OPENAI_API_KEY'],
            self::Anthropic => ['ANTHROPIC_API_KEY'],
            self::Gemini => ['GEMINI_API_KEY'],
            self::Local => ['LOCAL_LLM_ENDPOINT'],
        };
    }

    /**
     * Get optional environment variables for this provider.
     */
    public function optionalEnvVars(): array
    {
        return match ($this) {
            self::OpenAI => ['OPENAI_MODEL', 'OPENAI_ORGANIZATION'],
            self::Anthropic => ['ANTHROPIC_MODEL'],
            self::Gemini => ['GEMINI_MODEL'],
            self::Local => ['LOCAL_LLM_MODEL', 'LOCAL_LLM_API_KEY'],
        };
    }

    /**
     * Get the provider implementation class.
     */
    public function implementationClass(): string
    {
        return match ($this) {
            self::OpenAI => 'AutoGen\\Common\\AI\\OpenAIProvider',
            self::Anthropic => 'AutoGen\\Common\\AI\\AnthropicProvider',
            self::Gemini => 'AutoGen\\Common\\AI\\GoogleGeminiProvider',
            self::Local => 'AutoGen\\Common\\AI\\LocalLLMProvider',
        };
    }

    /**
     * Check if this provider supports streaming responses.
     */
    public function supportsStreaming(): bool
    {
        return match ($this) {
            self::OpenAI => true,
            self::Anthropic => true,
            self::Gemini => false,
            self::Local => true,
        };
    }

    /**
     * Check if this provider supports function calling.
     */
    public function supportsFunctionCalling(): bool
    {
        return match ($this) {
            self::OpenAI => true,
            self::Anthropic => true,
            self::Gemini => true,
            self::Local => false,
        };
    }

    /**
     * Check if this provider requires internet connection.
     */
    public function requiresInternet(): bool
    {
        return match ($this) {
            self::Local => false,
            default => true,
        };
    }

    /**
     * Get typical response time category.
     */
    public function responseTimeCategory(): string
    {
        return match ($this) {
            self::OpenAI => 'fast',
            self::Anthropic => 'medium',
            self::Gemini => 'fast',
            self::Local => 'variable',
        };
    }

    /**
     * Get cost category (relative).
     */
    public function costCategory(): string
    {
        return match ($this) {
            self::OpenAI => 'medium',
            self::Anthropic => 'medium',
            self::Gemini => 'low',
            self::Local => 'free',
        };
    }

    /**
     * Get default configuration for this provider.
     */
    public function defaultConfig(): array
    {
        return match ($this) {
            self::OpenAI => [
                'max_tokens' => 2000,
                'temperature' => 0.3,
                'top_p' => 1.0,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0,
            ],
            self::Anthropic => [
                'max_tokens' => 2000,
                'temperature' => 0.3,
                'top_p' => 1.0,
                'top_k' => 40,
            ],
            self::Gemini => [
                'max_tokens' => 2000,
                'temperature' => 0.3,
                'top_p' => 1.0,
                'top_k' => 40,
                'safety_settings' => [],
            ],
            self::Local => [
                'max_tokens' => 2000,
                'temperature' => 0.3,
                'top_p' => 1.0,
                'context_length' => 4096,
            ],
        };
    }

    /**
     * Get all available providers as an array.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all providers as label => value pairs.
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn($carry, $case) => $carry + [$case->label() => $case->value],
            []
        );
    }

    /**
     * Get providers that support streaming.
     */
    public static function withStreaming(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsStreaming());
    }

    /**
     * Get providers that support function calling.
     */
    public static function withFunctionCalling(): array
    {
        return array_filter(self::cases(), fn($case) => $case->supportsFunctionCalling());
    }

    /**
     * Get providers that work offline.
     */
    public static function offline(): array
    {
        return array_filter(self::cases(), fn($case) => !$case->requiresInternet());
    }

    /**
     * Get providers by cost category.
     */
    public static function byCost(string $category): array
    {
        return array_filter(self::cases(), fn($case) => $case->costCategory() === $category);
    }
}