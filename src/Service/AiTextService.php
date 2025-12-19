<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Psr\Log\LoggerInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\TextProviderInterface;

class AiTextService
{
    /**
     * @param iterable<TextProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly ?LoggerInterface $logger = null,
        private readonly int $retries = 2,
        private readonly int $retryDelay = 3,
    ) {
    }

    /**
     * Generate text using available providers with fallback
     *
     * @param string $systemPrompt The system prompt/instructions
     * @param string $userMessage The user message/content to process
     * @param array{
     *     model?: string,
     *     temperature?: float,
     *     max_tokens?: int,
     *     provider?: string
     * } $options Additional generation options
     *
     * @throws AiProviderException When all providers fail
     */
    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult
    {
        $preferredProvider = $options['provider'] ?? null;
        $errors = [];

        // If a specific provider is requested, try only that one
        if ($preferredProvider !== null) {
            $provider = $this->findProvider($preferredProvider);
            if ($provider === null) {
                throw new AiProviderException(
                    message: sprintf('Provider "%s" not found', $preferredProvider),
                    provider: $preferredProvider
                );
            }

            return $this->generateWithRetries($provider, $systemPrompt, $userMessage, $options);
        }

        // Try each available provider in order of priority
        foreach ($this->providers as $provider) {
            if (!$provider->isAvailable()) {
                $this->logger?->debug('[AiTextService] Provider not available, skipping', [
                    'provider' => $provider->getName(),
                ]);
                continue;
            }

            try {
                $this->logger?->info('[AiTextService] Trying provider', [
                    'provider' => $provider->getName(),
                ]);

                return $this->generateWithRetries($provider, $systemPrompt, $userMessage, $options);
            } catch (AiProviderException $e) {
                $this->logger?->warning('[AiTextService] Provider failed, trying next', [
                    'provider' => $provider->getName(),
                    'error' => $e->getMessage(),
                ]);
                $errors[$provider->getName()] = $e->getMessage();
            }
        }

        // All providers failed
        $errorDetails = implode('; ', array_map(
            fn(string $provider, string $error) => sprintf('%s: %s', $provider, $error),
            array_keys($errors),
            array_values($errors)
        ));

        throw new AiProviderException(
            message: sprintf('All text providers failed: %s', $errorDetails ?: 'No providers available'),
            provider: 'all'
        );
    }

    /**
     * Get list of available provider names
     *
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        $available = [];
        foreach ($this->providers as $provider) {
            if ($provider->isAvailable()) {
                $available[] = $provider->getName();
            }
        }
        return $available;
    }

    /**
     * Check if a specific provider is available
     */
    public function isProviderAvailable(string $name): bool
    {
        $provider = $this->findProvider($name);
        return $provider !== null && $provider->isAvailable();
    }

    /**
     * Find a provider by name
     */
    private function findProvider(string $name): ?TextProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $name) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * Generate with retry logic
     */
    private function generateWithRetries(
        TextProviderInterface $provider,
        string $systemPrompt,
        string $userMessage,
        array $options
    ): TextResult {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retries; $attempt++) {
            try {
                return $provider->generate($systemPrompt, $userMessage, $options);
            } catch (AiProviderException $e) {
                $lastException = $e;

                // Don't retry on 4xx errors (client errors)
                if ($e->getHttpStatusCode() !== null && $e->getHttpStatusCode() >= 400 && $e->getHttpStatusCode() < 500) {
                    throw $e;
                }

                $this->logger?->warning('[AiTextService] Attempt failed, retrying', [
                    'provider' => $provider->getName(),
                    'attempt' => $attempt,
                    'max_attempts' => $this->retries,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->retries) {
                    sleep($this->retryDelay);
                }
            }
        }

        throw $lastException ?? new AiProviderException(
            message: 'Generation failed after retries',
            provider: $provider->getName()
        );
    }
}
