<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\ModelInfo;

/**
 * Service for managing task-specific model configuration.
 *
 * This service bridges the ModelRegistryService (knows all models) with
 * the user's configuration (which models are allowed for each task).
 */
class TaskConfigService
{
    /**
     * Default configuration if none provided.
     *
     * @var array<string, array{default_model: string, allowed_models: list<string>}>
     */
    private const BUILT_IN_DEFAULTS = [
        'news_content' => [
            'default_model' => 'claude',
            'allowed_models' => ['claude', 'gemini', 'openai', 'gemini-fast', 'mistral'],
        ],
        'image_prompt' => [
            'default_model' => 'gemini-fast',
            'allowed_models' => ['openai-fast', 'gemini-fast', 'mistral'],
        ],
        'image_generation' => [
            'default_model' => 'gptimage',
            'allowed_models' => ['flux', 'gptimage', 'seedream', 'nanobanana', 'turbo'],
        ],
    ];

    /**
     * @param array<string, array{default_model?: string, allowed_models?: list<string>}> $tasksConfig
     */
    public function __construct(
        private readonly ModelRegistryService $modelRegistry,
        private readonly array $tasksConfig = [],
    ) {
    }

    /**
     * Get the default model for a task type.
     */
    public function getDefaultModel(TaskType $taskType): string
    {
        $taskKey = $taskType->value;

        return $this->tasksConfig[$taskKey]['default_model']
            ?? self::BUILT_IN_DEFAULTS[$taskKey]['default_model'];
    }

    /**
     * Get the list of allowed models for a task type.
     *
     * @return list<string>
     */
    public function getAllowedModels(TaskType $taskType): array
    {
        $taskKey = $taskType->value;

        return $this->tasksConfig[$taskKey]['allowed_models']
            ?? self::BUILT_IN_DEFAULTS[$taskKey]['allowed_models'];
    }

    /**
     * Check if a specific model is allowed for a task type.
     */
    public function isModelAllowed(TaskType $taskType, string $model): bool
    {
        return \in_array($model, $this->getAllowedModels($taskType), true);
    }

    /**
     * Validate that a model is allowed for a task type.
     *
     * @throws AiProviderException if the model is not allowed
     */
    public function validateModel(TaskType $taskType, string $model): void
    {
        if (!$this->isModelAllowed($taskType, $model)) {
            throw new AiProviderException(\sprintf('Model "%s" is not allowed for task "%s". Allowed models: %s', $model, $taskType->value, implode(', ', $this->getAllowedModels($taskType))), 'pollinations');
        }
    }

    /**
     * Get ModelInfo for a specific model within a task context.
     *
     * @throws AiProviderException if the model is not allowed for the task
     */
    public function getModelInfo(TaskType $taskType, string $model): ModelInfo
    {
        $this->validateModel($taskType, $model);

        $modelInfo = $this->modelRegistry->getModel($model);

        if ($modelInfo === null) {
            throw new AiProviderException(\sprintf('Model "%s" not found in registry', $model), 'pollinations');
        }

        return $modelInfo;
    }

    /**
     * Get all allowed models with their full ModelInfo for a task.
     *
     * @return array<string, ModelInfo>
     */
    public function getAllowedModelsWithInfo(TaskType $taskType): array
    {
        $result = [];
        $allowedKeys = $this->getAllowedModels($taskType);

        foreach ($allowedKeys as $key) {
            $modelInfo = $this->modelRegistry->getModel($key);
            if ($modelInfo !== null) {
                $result[$key] = $modelInfo;
            }
        }

        return $result;
    }

    /**
     * Get allowed models formatted for UI selects.
     *
     * @return array<string, string> [key => "Name (cost)"]
     */
    public function getAllowedModelsForSelect(TaskType $taskType): array
    {
        $result = [];

        foreach ($this->getAllowedModelsWithInfo($taskType) as $key => $model) {
            $result[$key] = \sprintf('%s (%s)', $model->name, $model->getFormattedCost());
        }

        return $result;
    }

    /**
     * Resolve the model to use for a task.
     *
     * If $requestedModel is null, returns the default.
     * If $requestedModel is specified, validates it and returns it.
     *
     * @throws AiProviderException if requested model is not allowed
     */
    public function resolveModel(TaskType $taskType, ?string $requestedModel = null): string
    {
        if ($requestedModel === null) {
            return $this->getDefaultModel($taskType);
        }

        $this->validateModel($taskType, $requestedModel);

        return $requestedModel;
    }

    /**
     * Get the estimated cost for a task using its default model.
     *
     * @return array{model: string, costPerResponse: float, formattedCost: string}
     */
    public function getDefaultCostEstimate(TaskType $taskType): array
    {
        $defaultModel = $this->getDefaultModel($taskType);
        $modelInfo = $this->modelRegistry->getModel($defaultModel);

        if ($modelInfo === null) {
            return [
                'model' => $defaultModel,
                'costPerResponse' => 0.0,
                'formattedCost' => 'Unknown',
            ];
        }

        return [
            'model' => $defaultModel,
            'costPerResponse' => $modelInfo->getCostPerResponseUSD(),
            'formattedCost' => $modelInfo->getFormattedCost(),
        ];
    }
}
