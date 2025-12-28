<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Psr\Log\LoggerInterface;
use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\ImageResult;
use Xmon\AiContentBundle\Model\ModelInfo;
use Xmon\AiContentBundle\Provider\Image\PollinationsImageProvider;

class AiImageService
{
    public function __construct(
        private readonly PollinationsImageProvider $provider,
        private readonly ?TaskConfigService $taskConfigService = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Generate an image using the configured provider.
     *
     * @param string $prompt The text prompt describing the image
     * @param array{
     *     width?: int,
     *     height?: int,
     *     model?: string,
     *     seed?: int,
     *     nologo?: bool,
     *     enhance?: bool,
     *     use_fallback?: bool,
     *     timeout?: int,
     *     retries_per_model?: int,
     *     retry_delay?: int
     * } $options Additional generation options
     *
     * @throws AiProviderException When generation fails
     */
    public function generate(string $prompt, array $options = []): ImageResult
    {
        $this->logger?->info('[AiImageService] Generating image', [
            'prompt_length' => \strlen($prompt),
            'options' => array_keys($options),
        ]);

        return $this->provider->generate($prompt, $options);
    }

    /**
     * Generate an image for a specific task type (IMAGE_GENERATION).
     *
     * Uses the TaskConfigService to determine which model to use based on
     * the task type configuration. Falls back to generate() if no config service.
     *
     * @param string $prompt The text prompt describing the image
     * @param array{
     *     width?: int,
     *     height?: int,
     *     model?: string,
     *     seed?: int,
     *     nologo?: bool,
     *     enhance?: bool,
     *     use_fallback?: bool
     * } $options Additional generation options (model can be overridden here)
     *
     * @throws AiProviderException When model is not allowed for task or generation fails
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
     * Check if the image provider is configured and available.
     */
    public function isConfigured(): bool
    {
        return $this->provider->isAvailable();
    }

    /**
     * Get list of available provider names.
     *
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        return $this->provider->isAvailable() ? [$this->provider->getName()] : [];
    }

    /**
     * Check if a specific provider is available.
     */
    public function isProviderAvailable(string $name): bool
    {
        return $name === $this->provider->getName() && $this->provider->isAvailable();
    }
}
