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

    /**
     * Fixed suffix appended to all generated styles (technical restrictions).
     * If null, falls back to xmon_ai_content.style_suffix parameter.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $aiStyleSuffix = null;

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

    public function getAiStyleSuffix(): ?string
    {
        return $this->aiStyleSuffix;
    }

    public function setAiStyleSuffix(?string $suffix): static
    {
        $this->aiStyleSuffix = $suffix;

        return $this;
    }

    // ==========================================
    // STYLE BUILDING
    // ==========================================

    /**
     * Build the complete style prompt from the configuration.
     *
     * Priority:
     * 1. Selected preset (if mode is 'preset' and preset exists)
     * 2. Custom fields (if mode is 'custom' and fields are filled)
     * 3. Configured default preset (from xmon_ai_content.default_preset)
     * 4. First available preset as last resort
     *
     * @param array<string, array{style: string, composition: string, palette: string}> $presets          Available presets (from ImageOptionsService::getPresetsForForm())
     * @param string                                                                    $suffix           Fixed suffix to append
     * @param string|null                                                               $defaultPresetKey Default preset key from bundle config
     */
    public function buildStylePreview(
        array $presets = [],
        string $suffix = '',
        ?string $defaultPresetKey = null,
    ): string {
        $parts = [];

        if ($this->aiStyleMode === 'preset' && $this->aiStylePreset !== null) {
            // Preset mode: use selected preset values
            $presetData = $presets[$this->aiStylePreset] ?? null;
            if ($presetData) {
                $parts[] = $presetData['style'];
                $parts[] = $presetData['composition'];
                $parts[] = $presetData['palette'];
            }
        }

        if (empty($parts) && $this->aiStyleMode === 'custom') {
            // Custom mode: use individual fields if any are set
            if ($this->aiStyleArtistic !== null || $this->aiStyleComposition !== null || $this->aiStylePalette !== null) {
                $parts[] = $this->aiStyleArtistic ?? '';
                $parts[] = $this->aiStyleComposition ?? '';
                $parts[] = $this->aiStylePalette ?? '';
            }
        }

        if (empty($parts) && $defaultPresetKey !== null && isset($presets[$defaultPresetKey])) {
            // Fallback: use configured default preset
            $defaultPreset = $presets[$defaultPresetKey];
            $parts[] = $defaultPreset['style'];
            $parts[] = $defaultPreset['composition'];
            $parts[] = $defaultPreset['palette'];
        }

        if (empty($parts) && !empty($presets)) {
            // Last resort: use first available preset
            $firstPreset = reset($presets);
            if ($firstPreset) {
                $parts[] = $firstPreset['style'];
                $parts[] = $firstPreset['composition'];
                $parts[] = $firstPreset['palette'];
            }
        }

        // Filter empty parts
        $parts = array_filter($parts, static fn ($p) => $p !== '');

        // Add suffix: BD value takes priority, then parameter fallback
        $effectiveSuffix = $this->aiStyleSuffix ?? $suffix;
        if ($effectiveSuffix !== '' && $effectiveSuffix !== null) {
            $parts[] = $effectiveSuffix;
        }

        // Add additional text if provided
        if ($this->aiStyleAdditional !== null && trim($this->aiStyleAdditional) !== '') {
            $parts[] = trim($this->aiStyleAdditional);
        }

        return implode(', ', $parts);
    }
}
