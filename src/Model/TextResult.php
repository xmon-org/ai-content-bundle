<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Model;

/**
 * Immutable DTO representing the result of a text generation.
 */
final readonly class TextResult
{
    public function __construct(
        private string $text,
        private string $provider,
        private ?string $model = null,
        private ?int $promptTokens = null,
        private ?int $completionTokens = null,
        private ?string $finishReason = null,
    ) {
    }

    /**
     * Get the generated text.
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Get the provider name that generated the text.
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get the model used for generation (if available).
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * Get the number of tokens in the prompt (if available).
     */
    public function getPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    /**
     * Get the number of tokens in the completion (if available).
     */
    public function getCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }

    /**
     * Get the total number of tokens used.
     */
    public function getTotalTokens(): ?int
    {
        if ($this->promptTokens === null || $this->completionTokens === null) {
            return null;
        }

        return $this->promptTokens + $this->completionTokens;
    }

    /**
     * Get the finish reason (e.g., 'stop', 'length', 'content_filter').
     */
    public function getFinishReason(): ?string
    {
        return $this->finishReason;
    }

    /**
     * Get the text trimmed of whitespace.
     */
    public function getTextTrimmed(): string
    {
        return trim($this->text);
    }

    /**
     * Check if the generation completed normally.
     */
    public function isComplete(): bool
    {
        return $this->finishReason === null || $this->finishReason === 'stop';
    }
}
