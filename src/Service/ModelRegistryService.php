<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Model\ModelInfo;

/**
 * Registry of available AI models with their metadata and costs.
 *
 * This service acts as the single source of truth for model information.
 * Cost data is based on Pollinations pricing (December 2025).
 *
 * Reference: 1 pollen = $1 USD
 */
class ModelRegistryService
{
    /**
     * Text models available via Pollinations.
     *
     * @var array<string, array{name: string, responsesPerPollen: int, description?: string}>
     */
    private const TEXT_MODELS = [
        'claude' => [
            'name' => 'Claude Sonnet 4.5',
            'responsesPerPollen' => 330,
            'description' => 'High quality text generation with excellent instruction following.',
        ],
        'gemini' => [
            'name' => 'Gemini 3 Flash',
            'responsesPerPollen' => 1600,
            'description' => 'Fast, balanced performance for general tasks.',
        ],
        'openai' => [
            'name' => 'GPT-5 Mini',
            'responsesPerPollen' => 8000,
            'description' => 'Versatile model for various text generation tasks.',
        ],
        'gemini-fast' => [
            'name' => 'Gemini 2.5 Flash Lite',
            'responsesPerPollen' => 12000,
            'description' => 'Ultra-fast, very cost-effective for simple tasks.',
        ],
        'openai-fast' => [
            'name' => 'GPT-5 Nano',
            'responsesPerPollen' => 11000,
            'description' => 'Ultra-fast, optimized for quick responses.',
        ],
        'mistral' => [
            'name' => 'Mistral Small',
            'responsesPerPollen' => 13000,
            'description' => 'Efficient open model, good for backup.',
        ],
    ];

    /**
     * Image models available via Pollinations.
     *
     * @var array<string, array{name: string, responsesPerPollen: int, description?: string}>
     */
    private const IMAGE_MODELS = [
        'gptimage' => [
            'name' => 'OpenAI Image 1 Mini',
            'responsesPerPollen' => 160,
            'description' => 'Best prompt understanding, good for detailed descriptions like aikido/hakama.',
        ],
        'seedream' => [
            'name' => 'ByteDance ARK 2K',
            'responsesPerPollen' => 35,
            'description' => 'High quality 2K resolution, good for Asian content.',
        ],
        'nanobanana' => [
            'name' => 'Gemini Image',
            'responsesPerPollen' => 25,
            'description' => 'Supports reference images, context-aware.',
        ],
        'flux' => [
            'name' => 'Flux (free)',
            'responsesPerPollen' => 8300,
            'description' => 'Fast, high quality, free tier.',
        ],
        'turbo' => [
            'name' => 'Turbo (free)',
            'responsesPerPollen' => 3300,
            'description' => 'Ultra-fast generation, free tier.',
        ],
    ];

    /**
     * Get a specific text model by key.
     */
    public function getTextModel(string $key): ?ModelInfo
    {
        if (!isset(self::TEXT_MODELS[$key])) {
            return null;
        }

        $data = self::TEXT_MODELS[$key];

        return new ModelInfo(
            key: $key,
            name: $data['name'],
            type: 'text',
            responsesPerPollen: $data['responsesPerPollen'],
            description: $data['description'],
        );
    }

    /**
     * Get a specific image model by key.
     */
    public function getImageModel(string $key): ?ModelInfo
    {
        if (!isset(self::IMAGE_MODELS[$key])) {
            return null;
        }

        $data = self::IMAGE_MODELS[$key];

        return new ModelInfo(
            key: $key,
            name: $data['name'],
            type: 'image',
            responsesPerPollen: $data['responsesPerPollen'],
            description: $data['description'],
        );
    }

    /**
     * Get a model by key (searches both text and image).
     */
    public function getModel(string $key): ?ModelInfo
    {
        return $this->getTextModel($key) ?? $this->getImageModel($key);
    }

    /**
     * Get all text models.
     *
     * @return array<string, ModelInfo>
     */
    public function getAllTextModels(): array
    {
        $models = [];
        foreach (array_keys(self::TEXT_MODELS) as $key) {
            $model = $this->getTextModel($key);
            if ($model !== null) {
                $models[$key] = $model;
            }
        }

        return $models;
    }

    /**
     * Get all image models.
     *
     * @return array<string, ModelInfo>
     */
    public function getAllImageModels(): array
    {
        $models = [];
        foreach (array_keys(self::IMAGE_MODELS) as $key) {
            $model = $this->getImageModel($key);
            if ($model !== null) {
                $models[$key] = $model;
            }
        }

        return $models;
    }

    /**
     * Get all available text model keys.
     *
     * @return list<string>
     */
    public function getTextModelKeys(): array
    {
        return array_keys(self::TEXT_MODELS);
    }

    /**
     * Get all available image model keys.
     *
     * @return list<string>
     */
    public function getImageModelKeys(): array
    {
        return array_keys(self::IMAGE_MODELS);
    }

    /**
     * Check if a text model key is valid.
     */
    public function isValidTextModel(string $key): bool
    {
        return isset(self::TEXT_MODELS[$key]);
    }

    /**
     * Check if an image model key is valid.
     */
    public function isValidImageModel(string $key): bool
    {
        return isset(self::IMAGE_MODELS[$key]);
    }

    /**
     * Get models appropriate for a specific task type.
     *
     * Returns the full list of models for the task's category (text or image).
     * Use TaskConfigService to get the allowed subset for a specific project.
     *
     * @return array<string, ModelInfo>
     */
    public function getModelsForTaskType(TaskType $taskType): array
    {
        return $taskType->isTextTask()
            ? $this->getAllTextModels()
            : $this->getAllImageModels();
    }

    /**
     * Get models for UI dropdowns with formatted labels.
     *
     * @return array<string, string> [key => "Name (cost info)"]
     */
    public function getTextModelsForSelect(): array
    {
        $result = [];
        foreach ($this->getAllTextModels() as $key => $model) {
            $result[$key] = \sprintf('%s (%s)', $model->name, $model->getFormattedCost());
        }

        return $result;
    }

    /**
     * Get models for UI dropdowns with formatted labels.
     *
     * @return array<string, string> [key => "Name (cost info)"]
     */
    public function getImageModelsForSelect(): array
    {
        $result = [];
        foreach ($this->getAllImageModels() as $key => $model) {
            $result[$key] = \sprintf('%s (%s)', $model->name, $model->getFormattedCost());
        }

        return $result;
    }
}
