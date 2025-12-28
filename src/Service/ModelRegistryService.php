<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Xmon\AiContentBundle\Enum\ModelTier;
use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Model\ModelInfo;

/**
 * Registry of available AI models with their metadata and costs.
 *
 * This service acts as the single source of truth for model information.
 * Models and pricing are based on Pollinations API (December 2025).
 *
 * To query available models with pricing:
 * - All: curl -H "Authorization: Bearer YOUR_API_KEY" https://gen.pollinations.ai/models
 * - Image: curl -H "Authorization: Bearer YOUR_API_KEY" https://gen.pollinations.ai/image/models
 *
 * Tiers determine access based on your account:
 * - anonymous: Free, no API key required (limited models)
 * - seed: Free with API key from auth.pollinations.ai
 * - flower: Premium tier (all models, paid with pollen credits)
 *
 * Pricing: 1 pollen = $1 USD
 */
class ModelRegistryService
{
    /**
     * Text models available via Pollinations.
     *
     * responsesPerPollen calculated assuming ~1000 tokens/response.
     * tier indicates minimum required access level.
     *
     * @var array<string, array{name: string, tier: string, responsesPerPollen: int, description?: string}>
     */
    private const TEXT_MODELS = [
        // Fast & cheap models
        'nova-micro' => [
            'name' => 'Amazon Nova Micro',
            'tier' => 'seed',
            'responsesPerPollen' => 7142, // Ultra cheap
            'description' => 'Ultra fast & ultra cheap, good for simple tasks.',
        ],
        'openai-fast' => [
            'name' => 'GPT-5 Nano',
            'tier' => 'anonymous',
            'responsesPerPollen' => 2272,
            'description' => 'Ultra fast & affordable, no reasoning.',
        ],
        'mistral' => [
            'name' => 'Mistral Small 3.2 24B',
            'tier' => 'seed',
            'responsesPerPollen' => 2857,
            'description' => 'Efficient & cost-effective, good for backup.',
        ],
        'gemini-fast' => [
            'name' => 'Gemini 2.5 Flash Lite',
            'tier' => 'seed',
            'responsesPerPollen' => 2500,
            'description' => 'Ultra fast & cost-effective, good for prompts.',
        ],
        // Balanced models
        'openai' => [
            'name' => 'GPT-5 Mini',
            'tier' => 'anonymous',
            'responsesPerPollen' => 1666,
            'description' => 'Fast & balanced, good general purpose.',
        ],
        'grok' => [
            'name' => 'xAI Grok 4 Fast',
            'tier' => 'seed',
            'responsesPerPollen' => 2000,
            'description' => 'High speed & real-time.',
        ],
        'deepseek' => [
            'name' => 'DeepSeek V3.2',
            'tier' => 'seed',
            'responsesPerPollen' => 595,
            'description' => 'Efficient reasoning & agentic AI.',
        ],
        'kimi-k2-thinking' => [
            'name' => 'Moonshot Kimi K2',
            'tier' => 'flower',
            'responsesPerPollen' => 400,
            'description' => 'Deep reasoning & tool orchestration.',
        ],
        // Quality models (more expensive)
        'gemini' => [
            'name' => 'Gemini 3 Flash',
            'tier' => 'seed',
            'responsesPerPollen' => 333,
            'description' => 'Pro-grade reasoning at flash speed.',
        ],
        'gemini-search' => [
            'name' => 'Gemini 3 Flash Search',
            'tier' => 'seed',
            'responsesPerPollen' => 333,
            'description' => 'Gemini with Google Search integration.',
        ],
        'perplexity-fast' => [
            'name' => 'Perplexity Sonar',
            'tier' => 'flower',
            'responsesPerPollen' => 1000,
            'description' => 'Fast with web search.',
        ],
        'perplexity-reasoning' => [
            'name' => 'Perplexity Sonar Reasoning',
            'tier' => 'flower',
            'responsesPerPollen' => 200,
            'description' => 'Advanced reasoning with web search.',
        ],
        'claude-fast' => [
            'name' => 'Claude Haiku 4.5',
            'tier' => 'flower',
            'responsesPerPollen' => 200,
            'description' => 'Fast & intelligent Anthropic model.',
        ],
        // Premium models (expensive, highest quality)
        'qwen-coder' => [
            'name' => 'Qwen 2.5 Coder 32B',
            'tier' => 'seed',
            'responsesPerPollen' => 1111,
            'description' => 'Specialized for code generation.',
        ],
        'openai-large' => [
            'name' => 'GPT-5.2',
            'tier' => 'flower',
            'responsesPerPollen' => 71,
            'description' => 'Most powerful & intelligent OpenAI model.',
        ],
        'gemini-large' => [
            'name' => 'Gemini 3 Pro',
            'tier' => 'flower',
            'responsesPerPollen' => 83,
            'description' => 'Most intelligent Google model with 1M context.',
        ],
        'claude' => [
            'name' => 'Claude Sonnet 4.5',
            'tier' => 'flower',
            'responsesPerPollen' => 66,
            'description' => 'Most capable & balanced Anthropic model.',
        ],
        'claude-large' => [
            'name' => 'Claude Opus 4.5',
            'tier' => 'flower',
            'responsesPerPollen' => 40,
            'description' => 'Most intelligent Anthropic model.',
        ],
    ];

    /**
     * Image models available via Pollinations.
     *
     * responsesPerPollen indicates images per $1.
     * tier indicates minimum required access level.
     *
     * @var array<string, array{name: string, tier: string, responsesPerPollen: int, description?: string}>
     */
    private const IMAGE_MODELS = [
        // Free/cheap models (anonymous tier)
        'flux' => [
            'name' => 'Flux',
            'tier' => 'anonymous',
            'responsesPerPollen' => 8333,
            'description' => 'Fast & high-quality, good default choice.',
        ],
        'turbo' => [
            'name' => 'Turbo',
            'tier' => 'anonymous',
            'responsesPerPollen' => 3333,
            'description' => 'Ultra-fast generation for quick previews.',
        ],
        'zimage' => [
            'name' => 'Z-Image-Turbo',
            'tier' => 'seed',
            'responsesPerPollen' => 5000,
            'description' => 'Fast 6B parameter model (alpha).',
        ],
        // Mid-tier models (seed tier, affordable)
        'nanobanana' => [
            'name' => 'NanoBanana (Gemini 2.5 Flash)',
            'tier' => 'seed',
            'responsesPerPollen' => 25, // ~$0.04/image - expensive
            'description' => 'Gemini-based, supports reference images.',
        ],
        'nanobanana-pro' => [
            'name' => 'NanoBanana Pro (Gemini 3 Pro)',
            'tier' => 'flower',
            'responsesPerPollen' => 12, // ~$0.08/image - very expensive
            'description' => '4K resolution with thinking capabilities.',
        ],
        'gptimage' => [
            'name' => 'GPT Image 1 Mini',
            'tier' => 'flower',
            'responsesPerPollen' => 160, // ~$0.006/image
            'description' => 'OpenAI image model, excellent prompt understanding.',
        ],
        'gptimage-large' => [
            'name' => 'GPT Image 1.5',
            'tier' => 'flower',
            'responsesPerPollen' => 80, // ~$0.0125/image
            'description' => 'Advanced OpenAI image model.',
        ],
        // Premium models (expensive)
        'seedream' => [
            'name' => 'Seedream 4.0 (ByteDance ARK)',
            'tier' => 'flower',
            'responsesPerPollen' => 33,
            'description' => 'High quality, better for complex scenes.',
        ],
        'seedream-pro' => [
            'name' => 'Seedream 4.5 Pro (4K)',
            'tier' => 'flower',
            'responsesPerPollen' => 25,
            'description' => '4K resolution, multi-image support.',
        ],
        'kontext' => [
            'name' => 'Kontext',
            'tier' => 'flower',
            'responsesPerPollen' => 25,
            'description' => 'Context-aware image generation.',
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
            tier: ModelTier::from($data['tier']),
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
            tier: ModelTier::from($data['tier']),
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
