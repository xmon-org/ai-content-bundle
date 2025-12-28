<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Entity;

/**
 * Interface for entities that store AI image style configuration.
 *
 * Implement this interface in your configuration entity (e.g., Configuracion, Settings)
 * to enable database-backed style configuration.
 *
 * Use with AiStyleConfigurableTrait for a default implementation.
 *
 * @see AiStyleConfigurableTrait
 */
interface AiStyleConfigurableInterface
{
    public const MODE_PRESET = 'preset';
    public const MODE_CUSTOM = 'custom';

    /**
     * Get the configuration mode ('preset' or 'custom').
     */
    public function getAiStyleMode(): string;

    /**
     * Set the configuration mode.
     */
    public function setAiStyleMode(string $mode): static;

    /**
     * Get the selected preset key.
     */
    public function getAiStylePreset(): ?string;

    /**
     * Set the selected preset key.
     */
    public function setAiStylePreset(?string $preset): static;

    /**
     * Get the artistic style (for custom mode).
     */
    public function getAiStyleArtistic(): ?string;

    /**
     * Set the artistic style.
     */
    public function setAiStyleArtistic(?string $style): static;

    /**
     * Get the composition style (for custom mode).
     */
    public function getAiStyleComposition(): ?string;

    /**
     * Set the composition style.
     */
    public function setAiStyleComposition(?string $composition): static;

    /**
     * Get the color palette (for custom mode).
     */
    public function getAiStylePalette(): ?string;

    /**
     * Set the color palette.
     */
    public function setAiStylePalette(?string $palette): static;

    /**
     * Get additional text to append.
     */
    public function getAiStyleAdditional(): ?string;

    /**
     * Set additional text.
     */
    public function setAiStyleAdditional(?string $additional): static;

    /**
     * Get the fixed suffix for all styles.
     * If null, falls back to xmon_ai_content.style_suffix parameter.
     */
    public function getAiStyleSuffix(): ?string;

    /**
     * Set the fixed suffix.
     */
    public function setAiStyleSuffix(?string $suffix): static;

    /**
     * Get the default image model for AI generation.
     * If null, falls back to xmon_ai_content.image_generation.default_model.
     */
    public function getAiImageModel(): ?string;

    /**
     * Set the default image model.
     */
    public function setAiImageModel(?string $model): static;
}
