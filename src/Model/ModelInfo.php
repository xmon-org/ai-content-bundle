<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Model;

use Xmon\AiContentBundle\Enum\ModelTier;

/**
 * Immutable DTO containing model information including costs.
 *
 * Cost reference: 1 pollen = $1 USD
 * The responsesPerPollen indicates how many responses you get per $1.
 * Value of 0 means the model is free (unlimited responses).
 */
readonly class ModelInfo
{
    public function __construct(
        /**
         * Model identifier (e.g., 'openai', 'flux').
         */
        public string $key,
        /**
         * Human-readable name (e.g., 'GPT-4.1 Nano').
         */
        public string $name,
        /**
         * Type of model: 'text' or 'image'.
         */
        public string $type,
        /**
         * Approximate responses per 1 pollen ($1 USD).
         * Higher = cheaper. 0 = free (unlimited).
         */
        public int $responsesPerPollen,
        /**
         * Access tier required for this model.
         */
        public ModelTier $tier = ModelTier::ANONYMOUS,
        /**
         * Optional description of the model capabilities.
         */
        public ?string $description = null,
    ) {
    }

    /**
     * Get cost per response in pollens.
     *
     * Example: 330 responses/pollen = 0.003 pollen/response
     */
    public function getCostPerResponse(): float
    {
        if ($this->responsesPerPollen <= 0) {
            return 0.0;
        }

        return 1.0 / $this->responsesPerPollen;
    }

    /**
     * Get cost per response in USD.
     *
     * Since 1 pollen = $1, this equals getCostPerResponse().
     */
    public function getCostPerResponseUSD(): float
    {
        return $this->getCostPerResponse();
    }

    /**
     * Get formatted cost string for UI display.
     *
     * Example: "~330 per pollen" or "~160 per pollen"
     */
    public function getFormattedCost(): string
    {
        if ($this->responsesPerPollen >= 1000) {
            return \sprintf('~%sK per pollen', number_format($this->responsesPerPollen / 1000, 1));
        }

        return \sprintf('~%d per pollen', $this->responsesPerPollen);
    }

    /**
     * Check if this is a free model.
     *
     * Free models have responsesPerPollen = 0 (unlimited) or tier = anonymous.
     */
    public function isFree(): bool
    {
        return $this->responsesPerPollen === 0 || $this->tier === ModelTier::ANONYMOUS;
    }

    /**
     * Check if this model requires an API key.
     */
    public function requiresApiKey(): bool
    {
        return $this->tier->requiresApiKey();
    }

    /**
     * Check if this is a text model.
     */
    public function isTextModel(): bool
    {
        return $this->type === 'text';
    }

    /**
     * Check if this is an image model.
     */
    public function isImageModel(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Convert to array for JSON serialization or Twig templates.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'type' => $this->type,
            'responsesPerPollen' => $this->responsesPerPollen,
            'tier' => $this->tier->value,
            'tierLabel' => $this->tier->getLabel(),
            'description' => $this->description,
            'costPerResponse' => $this->getCostPerResponse(),
            'formattedCost' => $this->getFormattedCost(),
            'isFree' => $this->isFree(),
            'requiresApiKey' => $this->requiresApiKey(),
        ];
    }
}
