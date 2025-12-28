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
     * @param array<string, array{label: string, prompt: string, group?: ?string}>                                         $styles
     * @param array<string, array{label: string, prompt: string, group?: ?string}>                                         $compositions
     * @param array<string, array{label: string, prompt: string, group?: ?string}>                                         $palettes
     * @param array<string, array{label: string, prompt: string, group?: ?string}>                                         $extras
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

    /**
     * Get all presets with full data.
     *
     * @return array<string, array{name: string, style: ?string, composition: ?string, palette: ?string, extras: string[]}>
     */
    public function getAllPresetsData(): array
    {
        return $this->presets;
    }

    // ==========================================
    // GETTERS FOR UI (grouped for ChoiceType optgroup)
    // ==========================================

    /**
     * Get all available styles grouped for Symfony ChoiceType optgroup.
     *
     * Items with the same 'group' value are grouped together.
     * Items without a group are placed under 'General'.
     *
     * @param string $defaultGroup Group name for items without explicit group
     *
     * @return array<string, array<string, string>> Format: ['Group' => ['label' => 'key', ...], ...]
     */
    public function getStylesGrouped(string $defaultGroup = 'General'): array
    {
        return $this->formatGrouped($this->styles, $defaultGroup);
    }

    /**
     * Get all available compositions grouped for Symfony ChoiceType optgroup.
     *
     * @param string $defaultGroup Group name for items without explicit group
     *
     * @return array<string, array<string, string>>
     */
    public function getCompositionsGrouped(string $defaultGroup = 'General'): array
    {
        return $this->formatGrouped($this->compositions, $defaultGroup);
    }

    /**
     * Get all available palettes grouped for Symfony ChoiceType optgroup.
     *
     * @param string $defaultGroup Group name for items without explicit group
     *
     * @return array<string, array<string, string>>
     */
    public function getPalettesGrouped(string $defaultGroup = 'General'): array
    {
        return $this->formatGrouped($this->palettes, $defaultGroup);
    }

    /**
     * Get all available extras grouped for Symfony ChoiceType optgroup.
     *
     * @param string $defaultGroup Group name for items without explicit group
     *
     * @return array<string, array<string, string>>
     */
    public function getExtrasGrouped(string $defaultGroup = 'General'): array
    {
        return $this->formatGrouped($this->extras, $defaultGroup);
    }

    // ==========================================
    // GETTERS FOR UI (grouped by key for HTML selects)
    // ==========================================

    /**
     * Get all available styles grouped by key for HTML select optgroup.
     *
     * Unlike getStylesGrouped() which returns label => prompt (for ChoiceType),
     * this returns key => label format suitable for HTML select elements
     * where the option value needs to be the key.
     *
     * @param string $defaultGroup Group name for items without explicit group
     *
     * @return array<string, array<string, string>> Format: ['Group' => ['key' => 'label', ...], ...]
     */
    public function getStylesGroupedByKey(string $defaultGroup = 'General'): array
    {
        return $this->formatGroupedByKey($this->styles, $defaultGroup);
    }

    /**
     * Get all available compositions grouped by key for HTML select optgroup.
     *
     * @param string $defaultGroup Group name for items without explicit group
     *
     * @return array<string, array<string, string>>
     */
    public function getCompositionsGroupedByKey(string $defaultGroup = 'General'): array
    {
        return $this->formatGroupedByKey($this->compositions, $defaultGroup);
    }

    /**
     * Get all available palettes grouped by key for HTML select optgroup.
     *
     * @param string $defaultGroup Group name for items without explicit group
     *
     * @return array<string, array<string, string>>
     */
    public function getPalettesGroupedByKey(string $defaultGroup = 'General'): array
    {
        return $this->formatGroupedByKey($this->palettes, $defaultGroup);
    }

    /**
     * Get all available extras grouped by key for HTML select optgroup.
     *
     * @param string $defaultGroup Group name for items without explicit group
     *
     * @return array<string, array<string, string>>
     */
    public function getExtrasGroupedByKey(string $defaultGroup = 'General'): array
    {
        return $this->formatGroupedByKey($this->extras, $defaultGroup);
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
     * Get all styles with full data (label + prompt + optional group).
     *
     * @return array<string, array{label: string, prompt: string, group?: ?string}>
     */
    public function getAllStylesData(): array
    {
        return $this->styles;
    }

    /**
     * Get all compositions with full data (label + prompt + optional group).
     *
     * @return array<string, array{label: string, prompt: string, group?: ?string}>
     */
    public function getAllCompositionsData(): array
    {
        return $this->compositions;
    }

    /**
     * Get all palettes with full data (label + prompt + optional group).
     *
     * @return array<string, array{label: string, prompt: string, group?: ?string}>
     */
    public function getAllPalettesData(): array
    {
        return $this->palettes;
    }

    /**
     * Get all extras with full data (label + prompt + optional group).
     *
     * @return array<string, array{label: string, prompt: string, group?: ?string}>
     */
    public function getAllExtrasData(): array
    {
        return $this->extras;
    }

    /**
     * Get raw style data including prompt and optional group.
     *
     * @return array{label: string, prompt: string, group?: ?string}|null
     */
    public function getStyleData(string $key): ?array
    {
        return $this->styles[$key] ?? null;
    }

    /**
     * Get raw composition data including prompt and optional group.
     *
     * @return array{label: string, prompt: string, group?: ?string}|null
     */
    public function getCompositionData(string $key): ?array
    {
        return $this->compositions[$key] ?? null;
    }

    /**
     * Get raw palette data including prompt and optional group.
     *
     * @return array{label: string, prompt: string, group?: ?string}|null
     */
    public function getPaletteData(string $key): ?array
    {
        return $this->palettes[$key] ?? null;
    }

    /**
     * Get raw extra data including prompt and optional group.
     *
     * @return array{label: string, prompt: string, group?: ?string}|null
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
     * @param array<string, array{label: string, prompt: string, group?: ?string}> $options
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

    /**
     * Format options array for Symfony ChoiceType with optgroup support.
     *
     * Groups options by their 'group' field. Options without a group
     * are placed under the default group.
     *
     * Returns format compatible with Symfony ChoiceType optgroup:
     * ['Group Name' => ['Label' => 'prompt', ...], ...]
     *
     * The value stored by ChoiceType is the PROMPT, not the key.
     * This ensures backward compatibility with existing data.
     *
     * @param array<string, array{label: string, prompt: string, group?: ?string}> $options
     *
     * @return array<string, array<string, string>> Format: ['Group' => ['label' => 'prompt', ...], ...]
     */
    private function formatGrouped(array $options, string $defaultGroup): array
    {
        $grouped = [];

        foreach ($options as $key => $option) {
            $group = $option['group'] ?? '';
            if ('' === $group) {
                $group = $defaultGroup;
            }

            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            // Format: label => prompt (stores actual prompt in entity)
            $grouped[$group][$option['label']] = $option['prompt'];
        }

        return $grouped;
    }

    /**
     * Format options array for HTML select with optgroup support.
     *
     * Groups options by their 'group' field. Options without a group
     * are placed under the default group.
     *
     * Unlike formatGrouped(), this preserves the KEY as the value,
     * suitable for HTML select elements where option value is the key.
     *
     * @param array<string, array{label: string, prompt: string, group?: ?string}> $options
     *
     * @return array<string, array<string, string>> Format: ['Group' => ['key' => 'label', ...], ...]
     */
    private function formatGroupedByKey(array $options, string $defaultGroup): array
    {
        $grouped = [];

        foreach ($options as $key => $option) {
            $group = $option['group'] ?? '';
            if ('' === $group) {
                $group = $defaultGroup;
            }

            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            // Format: key => label (stores key in select value)
            $grouped[$group][$key] = $option['label'];
        }

        return $grouped;
    }

    // ==========================================
    // FORM INTEGRATION HELPERS
    // ==========================================

    /**
     * Get presets formatted for AiStyleConfigType form.
     *
     * Returns presets with resolved style/composition/palette prompts
     * in the format expected by the form and the trait's buildStylePreview().
     *
     * @return array<string, array{name: string, description: ?string, style: string, composition: string, palette: string}>
     */
    public function getPresetsForForm(): array
    {
        $result = [];

        foreach ($this->presets as $key => $preset) {
            // Resolve style/composition/palette keys to actual prompts
            $stylePrompt = '';
            if (!empty($preset['style'])) {
                $stylePrompt = $this->getStylePrompt($preset['style']) ?? $preset['style'];
            }

            $compositionPrompt = '';
            if (!empty($preset['composition'])) {
                $compositionPrompt = $this->getCompositionPrompt($preset['composition']) ?? $preset['composition'];
            }

            $palettePrompt = '';
            if (!empty($preset['palette'])) {
                $palettePrompt = $this->getPalettePrompt($preset['palette']) ?? $preset['palette'];
            }

            $result[$key] = [
                'name' => $preset['name'],
                'description' => $preset['description'] ?? null,
                'style' => $stylePrompt,
                'composition' => $compositionPrompt,
                'palette' => $palettePrompt,
            ];
        }

        return $result;
    }

    /**
     * Get preset choices for ChoiceType (label => key format).
     *
     * @return array<string, string>
     */
    public function getPresetChoices(): array
    {
        $choices = [];
        foreach ($this->presets as $key => $preset) {
            $choices[$preset['name']] = $key;
        }

        return $choices;
    }

    /**
     * Get a resolved preset by key (with prompts, not keys).
     *
     * @return array{name: string, description: ?string, style: string, composition: string, palette: string}|null
     */
    public function getResolvedPreset(string $key): ?array
    {
        $preset = $this->presets[$key] ?? null;
        if ($preset === null) {
            return null;
        }

        return [
            'name' => $preset['name'],
            'description' => $preset['description'] ?? null,
            'style' => $this->getStylePrompt($preset['style'] ?? '') ?? '',
            'composition' => $this->getCompositionPrompt($preset['composition'] ?? '') ?? '',
            'palette' => $this->getPalettePrompt($preset['palette'] ?? '') ?? '',
        ];
    }

    /**
     * Build a complete style string from a preset key.
     *
     * @param string      $presetKey      The preset key
     * @param string      $suffix         Fixed suffix to append (e.g., "no text, professional quality")
     * @param string|null $additionalText Optional additional text to append
     *
     * @return string|null The complete style string, or null if preset not found
     */
    public function buildStyleFromPreset(string $presetKey, string $suffix = '', ?string $additionalText = null): ?string
    {
        $resolved = $this->getResolvedPreset($presetKey);
        if ($resolved === null) {
            return null;
        }

        $parts = array_filter([
            $resolved['style'],
            $resolved['composition'],
            $resolved['palette'],
        ], static fn ($p) => $p !== '');

        if ($suffix !== '') {
            $parts[] = $suffix;
        }

        if ($additionalText !== null && trim($additionalText) !== '') {
            $parts[] = trim($additionalText);
        }

        return implode(', ', $parts);
    }
}
