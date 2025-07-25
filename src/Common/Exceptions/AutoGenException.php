<?php

declare(strict_types=1);

namespace AutoGen\Common\Exceptions;

use Exception;

class AutoGenException extends Exception
{
    /**
     * Additional context data.
     */
    protected array $context = [];

    /**
     * Create a new AutoGen exception.
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the exception context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set the exception context.
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Add context data.
     */
    public function addContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * Convert exception to array.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ];
    }
}