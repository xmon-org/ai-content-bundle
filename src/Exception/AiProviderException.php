<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Exception;

class AiProviderException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $provider = null,
        private readonly ?int $httpStatusCode = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the provider name that caused the exception.
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Get the HTTP status code if available.
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Create exception for HTTP error.
     */
    public static function httpError(string $provider, int $statusCode, string $message = ''): self
    {
        $defaultMessage = match ($statusCode) {
            400 => 'Bad request',
            401 => 'Unauthorized - check API key',
            403 => 'Forbidden - access denied',
            404 => 'Not found',
            429 => 'Rate limit exceeded',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
            504 => 'Gateway timeout',
            default => 'HTTP error',
        };

        return new self(
            message: \sprintf('[%s] %s (HTTP %d)%s', $provider, $defaultMessage, $statusCode, $message ? ': '.$message : ''),
            provider: $provider,
            httpStatusCode: $statusCode,
        );
    }

    /**
     * Create exception for timeout.
     */
    public static function timeout(string $provider, int $timeout): self
    {
        return new self(
            message: \sprintf('[%s] Request timed out after %d seconds', $provider, $timeout),
            provider: $provider,
        );
    }

    /**
     * Create exception for connection error.
     */
    public static function connectionError(string $provider, string $details = ''): self
    {
        return new self(
            message: \sprintf('[%s] Connection error%s', $provider, $details ? ': '.$details : ''),
            provider: $provider,
        );
    }

    /**
     * Create exception for invalid response.
     */
    public static function invalidResponse(string $provider, string $details = ''): self
    {
        return new self(
            message: \sprintf('[%s] Invalid response from API%s', $provider, $details ? ': '.$details : ''),
            provider: $provider,
        );
    }
}
