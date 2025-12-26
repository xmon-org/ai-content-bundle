<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Enum;

/**
 * Defines the types of AI generation tasks.
 *
 * Each task type has specific model requirements and defaults:
 * - NEWS_CONTENT: Complex text generation requiring deep understanding
 * - IMAGE_PROMPT: Quick text generation for image descriptions
 * - IMAGE_GENERATION: Image generation with visual coherence
 */
enum TaskType: string
{
    case NEWS_CONTENT = 'news_content';
    case IMAGE_PROMPT = 'image_prompt';
    case IMAGE_GENERATION = 'image_generation';

    /**
     * Check if this is a text generation task.
     */
    public function isTextTask(): bool
    {
        return match ($this) {
            self::NEWS_CONTENT, self::IMAGE_PROMPT => true,
            self::IMAGE_GENERATION => false,
        };
    }

    /**
     * Check if this is an image generation task.
     */
    public function isImageTask(): bool
    {
        return $this === self::IMAGE_GENERATION;
    }

    /**
     * Get a human-readable label for this task type.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::NEWS_CONTENT => 'News Content Generation',
            self::IMAGE_PROMPT => 'Image Prompt Generation',
            self::IMAGE_GENERATION => 'Image Generation',
        };
    }

    /**
     * Get a description of what this task type is used for.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::NEWS_CONTENT => 'Generate news articles from RSS feed content. Requires deep understanding and good reformulation.',
            self::IMAGE_PROMPT => 'Generate visual descriptions from text content. Requires synthesis and visual description skills.',
            self::IMAGE_GENERATION => 'Generate images from prompts. Requires visual coherence and prompt fidelity.',
        };
    }
}
