<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Psr\Log\LoggerInterface;
use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\ModelInfo;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\Text\PollinationsTextProvider;

class AiTextService
{
    public function __construct(
        private readonly PollinationsTextProvider $provider,
        private readonly ?TaskConfigService $taskConfigService = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Generate text using the configured provider.
     *
     * @param string $systemPrompt The system prompt/instructions
     * @param string $userMessage  The user message/content to process
     * @param array{
     *     model?: string,
     *     use_fallback?: bool,
     *     timeout?: int,
     *     retries_per_model?: int,
     *     retry_delay?: int
     * } $options Additional generation options
     *
     * @throws AiProviderException When generation fails
     */
    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult
    {
        $this->logger?->info('[AiTextService] Generating text', [
            'system_prompt_length' => \strlen($systemPrompt),
            'user_message_length' => \strlen($userMessage),
            'options' => array_keys($options),
        ]);

        return $this->provider->generate($systemPrompt, $userMessage, $options);
    }

    /**
     * Generate text for a specific task type.
     *
     * Uses the TaskConfigService to determine which model to use based on
     * the task type configuration. Falls back to generate() if no config service.
     *
     * @param TaskType $taskType     The type of task (NEWS_CONTENT, IMAGE_PROMPT, etc.)
     * @param string   $systemPrompt The system prompt/instructions
     * @param string   $userMessage  The user message/content to process
     * @param array{
     *     model?: string,
     *     use_fallback?: bool
     * } $options Additional generation options (model can be overridden here)
     *
     * @throws AiProviderException When model is not allowed for task or generation fails
     */
    public function generateForTask(
        TaskType $taskType,
        string $systemPrompt,
        string $userMessage,
        array $options = [],
    ): TextResult {
        if ($this->taskConfigService === null) {
            $this->logger?->warning('[AiTextService] TaskConfigService not available, using default generate()');

            return $this->generate($systemPrompt, $userMessage, $options);
        }

        // Resolve the model to use (validates if explicitly requested)
        $model = $this->taskConfigService->resolveModel(
            $taskType,
            $options['model'] ?? null
        );

        $this->logger?->info('[AiTextService] Generating for task', [
            'task' => $taskType->value,
            'model' => $model,
        ]);

        // Merge model into options
        $options['model'] = $model;

        return $this->generate($systemPrompt, $userMessage, $options);
    }

    /**
     * Get the allowed models for a specific task type.
     *
     * @return array<string, ModelInfo>
     */
    public function getAllowedModelsForTask(TaskType $taskType): array
    {
        if ($this->taskConfigService === null) {
            return [];
        }

        return $this->taskConfigService->getAllowedModelsWithInfo($taskType);
    }

    /**
     * Get the default model for a specific task type.
     */
    public function getDefaultModelForTask(TaskType $taskType): ?string
    {
        return $this->taskConfigService?->getDefaultModel($taskType);
    }

    /**
     * Get the allowed models formatted for UI selects.
     *
     * @return array<string, string> [key => "Name (cost)"]
     */
    public function getAllowedModelsForSelect(TaskType $taskType): array
    {
        if ($this->taskConfigService === null) {
            return [];
        }

        return $this->taskConfigService->getAllowedModelsForSelect($taskType);
    }

    /**
     * Check if the text provider is configured and available.
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

    /**
     * Get the provider configuration for debugging.
     *
     * @return array{
     *     model: string,
     *     fallback_models: array<string>,
     *     retries_per_model: int,
     *     retry_delay: int,
     *     timeout: int,
     *     endpoint_mode: string,
     *     has_api_key: bool
     * }
     */
    public function getProviderConfig(): array
    {
        return $this->provider->getConfig();
    }
}
