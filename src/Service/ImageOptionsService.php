<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

/**
 * Service for managing image generation options.
 *
 * Provides access to styles, compositions, palettes, extras and presets
 * configured via YAML. Used by PromptBuilder to construct prompts
 * and by Admin UI to populate select fields.
 */
class ImageOptionsService
{
    /**
     * @param array<string, array{label: string, prompt: string}>                                                          $styles
     * @param array<string, array{label: string, prompt: string}>                                                          $compositions
     * @param array<string, array{label: string, prompt: string}>                                                          $palettes
     * @param array<string, array{label: string, prompt: string}>                                                          $extras
     * @param array<string, array{name: string, style: ?string, composition: ?string, palette: ?string, extras: string[]}> $presets
     */
    public function __construct(
        private readonly array $styles,
        private readonly array $compositions,
        private readonly array $palettes,
        private readonly array $extras,
        private readonly array $presets,
    ) {
    }

    // ==========================================
    // GETTERS FOR UI (label lists for selects)
    // ==========================================

    /**
     * Get all available styles as key => label pairs.
     *
     * @return array<string, string>
     */
    public function getStyles(): array
    {
        return $this->extractLabels($this->styles);
    }

    /**
     * Get all available compositions as key => label pairs.
     *
     * @return array<string, string>
     */
    public function getCompositions(): array
    {
        return $this->extractLabels($this->compositions);
    }

    /**
     * Get all available palettes as key => label pairs.
     *
     * @return array<string, string>
     */
    public function getPalettes(): array
    {
        return $this->extractLabels($this->palettes);
    }

    /**
     * Get all available extras as key => label pairs.
     *
     * @return array<string, string>
     */
    public function getExtras(): array
    {
        return $this->extractLabels($this->extras);
    }

    /**
     * Get all available presets as key => name pairs.
     *
     * @return array<string, string>
     */
    public function getPresets(): array
    {
        $result = [];
        foreach ($this->presets as $key => $preset) {
            $result[$key] = $preset['name'];
        }

        return $result;
    }

    // ==========================================
    // GETTERS FOR PROMPT FRAGMENTS
    // ==========================================

    /**
     * Get the prompt fragment for a style.
     */
    public function getStylePrompt(string $key): ?string
    {
        return $this->styles[$key]['prompt'] ?? null;
    }

    /**
     * Get the prompt fragment for a composition.
     */
    public function getCompositionPrompt(string $key): ?string
    {
        return $this->compositions[$key]['prompt'] ?? null;
    }

    /**
     * Get the prompt fragment for a palette.
     */
    public function getPalettePrompt(string $key): ?string
    {
        return $this->palettes[$key]['prompt'] ?? null;
    }

    /**
     * Get the prompt fragment for an extra.
     */
    public function getExtraPrompt(string $key): ?string
    {
        return $this->extras[$key]['prompt'] ?? null;
    }

    // ==========================================
    // PRESET RESOLUTION
    // ==========================================

    /**
     * Get a preset by key.
     *
     * @return array{name: string, style: ?string, composition: ?string, palette: ?string, extras: string[]}|null
     */
    public function getPreset(string $key): ?array
    {
        return $this->presets[$key] ?? null;
    }

    /**
     * Check if a preset exists.
     */
    public function hasPreset(string $key): bool
    {
        return isset($this->presets[$key]);
    }

    /**
     * Check if a style exists.
     */
    public function hasStyle(string $key): bool
    {
        return isset($this->styles[$key]);
    }

    /**
     * Check if a composition exists.
     */
    public function hasComposition(string $key): bool
    {
        return isset($this->compositions[$key]);
    }

    /**
     * Check if a palette exists.
     */
    public function hasPalette(string $key): bool
    {
        return isset($this->palettes[$key]);
    }

    /**
     * Check if an extra exists.
     */
    public function hasExtra(string $key): bool
    {
        return isset($this->extras[$key]);
    }

    // ==========================================
    // RAW DATA ACCESS (for advanced use cases)
    // ==========================================

    /**
     * Get all styles with full data (label + prompt).
     *
     * @return array<string, array{label: string, prompt: string}>
     */
    public function getAllStylesData(): array
    {
        return $this->styles;
    }

    /**
     * Get all compositions with full data (label + prompt).
     *
     * @return array<string, array{label: string, prompt: string}>
     */
    public function getAllCompositionsData(): array
    {
        return $this->compositions;
    }

    /**
     * Get all palettes with full data (label + prompt).
     *
     * @return array<string, array{label: string, prompt: string}>
     */
    public function getAllPalettesData(): array
    {
        return $this->palettes;
    }

    /**
     * Get all extras with full data (label + prompt).
     *
     * @return array<string, array{label: string, prompt: string}>
     */
    public function getAllExtrasData(): array
    {
        return $this->extras;
    }

    /**
     * Get raw style data including prompt.
     *
     * @return array{label: string, prompt: string}|null
     */
    public function getStyleData(string $key): ?array
    {
        return $this->styles[$key] ?? null;
    }

    /**
     * Get raw composition data including prompt.
     *
     * @return array{label: string, prompt: string}|null
     */
    public function getCompositionData(string $key): ?array
    {
        return $this->compositions[$key] ?? null;
    }

    /**
     * Get raw palette data including prompt.
     *
     * @return array{label: string, prompt: string}|null
     */
    public function getPaletteData(string $key): ?array
    {
        return $this->palettes[$key] ?? null;
    }

    /**
     * Get raw extra data including prompt.
     *
     * @return array{label: string, prompt: string}|null
     */
    public function getExtraData(string $key): ?array
    {
        return $this->extras[$key] ?? null;
    }

    // ==========================================
    // PRIVATE HELPERS
    // ==========================================

    /**
     * Extract labels from an options array.
     *
     * @param array<string, array{label: string, prompt: string}> $options
     *
     * @return array<string, string>
     */
    private function extractLabels(array $options): array
    {
        $result = [];
        foreach ($options as $key => $option) {
            $result[$key] = $option['label'];
        }

        return $result;
    }
}
