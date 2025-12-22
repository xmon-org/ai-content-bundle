<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for entities that store AI image style configuration.
 *
 * This trait provides fields for storing style configuration in the database:
 * - Mode selection (preset vs custom)
 * - Preset key (for preset mode)
 * - Individual style components (for custom mode)
 * - Additional text modifier
 *
 * Use this trait in your configuration entity (e.g., Configuracion, Settings)
 * and implement AiStyleConfigurableInterface.
 *
 * Example:
 *
 *     #[ORM\Entity]
 *     class Configuracion implements AiStyleConfigurableInterface
 *     {
 *         use AiStyleConfigurableTrait;
 *
 *         // Define your presets, styles, compositions, palettes as constants
 *         public const PRESETS = [...];
 *         public const STYLES = [...];
 *         // etc.
 *     }
 *
 * The trait provides:
 * - getBaseStylePreview(): Combines all style components into a single prompt string
 * - Getters/setters for all configuration fields
 *
 * @see AiStyleConfigurableInterface
 */
trait AiStyleConfigurableTrait
{
    /**
     * Configuration mode: 'preset' uses predefined combinations, 'custom' uses individual fields.
     */
    #[ORM\Column(length: 20, options: ['default' => 'preset'])]
    protected string $aiStyleMode = 'preset';

    /**
     * Selected preset key (when mode is 'preset').
     */
    #[ORM\Column(length: 50, nullable: true)]
    protected ?string $aiStylePreset = null;

    /**
     * Artistic style (when mode is 'custom').
     * Example: "sumi-e Japanese ink wash painting style".
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $aiStyleArtistic = null;

    /**
     * Composition style (when mode is 'custom').
     * Example: "minimalist elegant composition".
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $aiStyleComposition = null;

    /**
     * Color palette (when mode is 'custom').
     * Example: "black white and dark crimson red color palette".
     */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $aiStylePalette = null;

    /**
     * Additional text to append to the style (both modes).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $aiStyleAdditional = null;

    // ==========================================
    // GETTERS AND SETTERS
    // ==========================================

    public function getAiStyleMode(): string
    {
        return $this->aiStyleMode;
    }

    public function setAiStyleMode(string $mode): static
    {
        $this->aiStyleMode = $mode;

        return $this;
    }

    public function getAiStylePreset(): ?string
    {
        return $this->aiStylePreset;
    }

    public function setAiStylePreset(?string $preset): static
    {
        $this->aiStylePreset = $preset;

        return $this;
    }

    public function getAiStyleArtistic(): ?string
    {
        return $this->aiStyleArtistic;
    }

    public function setAiStyleArtistic(?string $style): static
    {
        $this->aiStyleArtistic = $style;

        return $this;
    }

    public function getAiStyleComposition(): ?string
    {
        return $this->aiStyleComposition;
    }

    public function setAiStyleComposition(?string $composition): static
    {
        $this->aiStyleComposition = $composition;

        return $this;
    }

    public function getAiStylePalette(): ?string
    {
        return $this->aiStylePalette;
    }

    public function setAiStylePalette(?string $palette): static
    {
        $this->aiStylePalette = $palette;

        return $this;
    }

    public function getAiStyleAdditional(): ?string
    {
        return $this->aiStyleAdditional;
    }

    public function setAiStyleAdditional(?string $additional): static
    {
        $this->aiStyleAdditional = $additional;

        return $this;
    }

    // ==========================================
    // STYLE BUILDING
    // ==========================================

    /**
     * Build the complete style prompt from the configuration.
     *
     * Override this method to customize how presets are resolved
     * and how the final style string is composed.
     *
     * @param array<string, array{estilo: string, composicion: string, paleta: string}> $presets     Available presets
     * @param string                                                                    $suffix      Fixed suffix to append
     * @param string|null                                                               $artDefault  Default artistic style
     * @param string|null                                                               $compDefault Default composition
     * @param string|null                                                               $palDefault  Default palette
     */
    public function buildStylePreview(
        array $presets = [],
        string $suffix = '',
        ?string $artDefault = null,
        ?string $compDefault = null,
        ?string $palDefault = null,
    ): string {
        $parts = [];

        if ($this->aiStyleMode === 'preset' && $this->aiStylePreset !== null) {
            // Preset mode: use preset values
            $presetData = $presets[$this->aiStylePreset] ?? null;
            if ($presetData) {
                $parts[] = $presetData['estilo'];
                $parts[] = $presetData['composicion'];
                $parts[] = $presetData['paleta'];
            }
        }

        if (empty($parts)) {
            // Custom mode or preset not found: use individual fields
            $parts[] = $this->aiStyleArtistic ?? $artDefault ?? '';
            $parts[] = $this->aiStyleComposition ?? $compDefault ?? '';
            $parts[] = $this->aiStylePalette ?? $palDefault ?? '';
        }

        // Filter empty parts
        $parts = array_filter($parts, static fn ($p) => $p !== '');

        // Add suffix if provided
        if ($suffix !== '') {
            $parts[] = $suffix;
        }

        // Add additional text if provided
        if ($this->aiStyleAdditional !== null && trim($this->aiStyleAdditional) !== '') {
            $parts[] = trim($this->aiStyleAdditional);
        }

        return implode(', ', $parts);
    }
}
