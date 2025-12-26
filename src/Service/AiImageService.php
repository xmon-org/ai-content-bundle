<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Psr\Log\LoggerInterface;
use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\ImageResult;
use Xmon\AiContentBundle\Model\ModelInfo;
use Xmon\AiContentBundle\Provider\ImageProviderInterface;

class AiImageService
{
    /**
     * @param iterable<ImageProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly ?TaskConfigService $taskConfigService = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly int $retries = 3,
        private readonly int $retryDelay = 5,
    ) {
    }

    /**
     * Generate an image using available providers with fallback.
     *
     * @param string $prompt The text prompt describing the image
     * @param array{
     *     width?: int,
     *     height?: int,
     *     model?: string,
     *     seed?: int,
     *     nologo?: bool,
     *     enhance?: bool,
     *     provider?: string
     * } $options Additional generation options
     *
     * @throws AiProviderException When all providers fail
     */
    public function generate(string $prompt, array $options = []): ImageResult
    {
        $preferredProvider = $options['provider'] ?? null;
        $errors = [];

        // If a specific provider is requested, try only that one
        if ($preferredProvider !== null) {
            $provider = $this->findProvider($preferredProvider);
            if ($provider === null) {
                throw new AiProviderException(\sprintf('Provider "%s" not found', $preferredProvider), $preferredProvider);
            }

            return $this->generateWithRetries($provider, $prompt, $options);
        }

        // Try each available provider in order
        foreach ($this->providers as $provider) {
            if (!$provider->isAvailable()) {
                $this->logger?->debug('[AiImageService] Provider not available, skipping', [
                    'provider' => $provider->getName(),
                ]);
                continue;
            }

            try {
                $this->logger?->info('[AiImageService] Trying provider', [
                    'provider' => $provider->getName(),
                ]);

                return $this->generateWithRetries($provider, $prompt, $options);
            } catch (AiProviderException $e) {
                $this->logger?->warning('[AiImageService] Provider failed, trying next', [
                    'provider' => $provider->getName(),
                    'error' => $e->getMessage(),
                ]);
                $errors[$provider->getName()] = $e->getMessage();
            }
        }

        // All providers failed
        $errorDetails = implode('; ', array_map(
            fn (string $provider, string $error) => \sprintf('%s: %s', $provider, $error),
            array_keys($errors),
            array_values($errors)
        ));

        throw new AiProviderException(\sprintf('All image providers failed: %s', $errorDetails ?: 'No providers available'), 'all');
    }

    /**
     * Generate an image for a specific task type (IMAGE_GENERATION).
     *
     * Uses the TaskConfigService to determine which model to use based on
     * the task type configuration. Falls back to generate() if no config service.
     *
     * @param string $prompt  The text prompt describing the image
     * @param array{
     *     width?: int,
     *     height?: int,
     *     model?: string,
     *     seed?: int,
     *     nologo?: bool,
     *     enhance?: bool,
     *     provider?: string
     * } $options Additional generation options (model can be overridden here)
     *
     * @throws AiProviderException When model is not allowed for task or all providers fail
     */
    public function generateForTask(string $prompt, array $options = []): ImageResult
    {
        if ($this->taskConfigService === null) {
            $this->logger?->warning('[AiImageService] TaskConfigService not available, using default generate()');

            return $this->generate($prompt, $options);
        }

        // For images, we always use IMAGE_GENERATION task type
        $taskType = TaskType::IMAGE_GENERATION;

        // Resolve the model to use (validates if explicitly requested)
        $model = $this->taskConfigService->resolveModel(
            $taskType,
            $options['model'] ?? null
        );

        $this->logger?->info('[AiImageService] Generating for task', [
            'task' => $taskType->value,
            'model' => $model,
        ]);

        // Merge model into options
        $options['model'] = $model;

        return $this->generate($prompt, $options);
    }

    /**
     * Get the allowed models for image generation.
     *
     * @return array<string, ModelInfo>
     */
    public function getAllowedModelsForTask(): array
    {
        if ($this->taskConfigService === null) {
            return [];
        }

        return $this->taskConfigService->getAllowedModelsWithInfo(TaskType::IMAGE_GENERATION);
    }

    /**
     * Get the default model for image generation.
     */
    public function getDefaultModel(): ?string
    {
        return $this->taskConfigService?->getDefaultModel(TaskType::IMAGE_GENERATION);
    }

    /**
     * Get the allowed models formatted for UI selects.
     *
     * @return array<string, string> [key => "Name (cost)"]
     */
    public function getAllowedModelsForSelect(): array
    {
        if ($this->taskConfigService === null) {
            return [];
        }

        return $this->taskConfigService->getAllowedModelsForSelect(TaskType::IMAGE_GENERATION);
    }

    /**
     * Check if at least one image provider is configured and available.
     */
    public function isConfigured(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of available provider names.
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
     * Check if a specific provider is available.
     */
    public function isProviderAvailable(string $name): bool
    {
        $provider = $this->findProvider($name);

        return $provider !== null && $provider->isAvailable();
    }

    /**
     * Find a provider by name.
     */
    private function findProvider(string $name): ?ImageProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $name) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Generate with retry logic.
     */
    private function generateWithRetries(
        ImageProviderInterface $provider,
        string $prompt,
        array $options,
    ): ImageResult {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retries; ++$attempt) {
            try {
                return $provider->generate($prompt, $options);
            } catch (AiProviderException $e) {
                $lastException = $e;

                // Don't retry on 4xx errors (client errors)
                if ($e->getHttpStatusCode() !== null && $e->getHttpStatusCode() >= 400 && $e->getHttpStatusCode() < 500) {
                    throw $e;
                }

                $this->logger?->warning('[AiImageService] Attempt failed, retrying', [
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

        throw $lastException ?? new AiProviderException('Generation failed after retries', $provider->getName());
    }
}
