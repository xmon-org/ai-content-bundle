<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Xmon\AiContentBundle\Exception\AiProviderException;

/**
 * Builds complete image generation prompts by combining subject with style options.
 *
 * Supports two modes:
 * 1. Preset mode: Use a predefined combination of options
 * 2. Individual mode: Specify each option separately
 *
 * Example output:
 * "aikidoka meditating in dojo, sumi-e Japanese ink wash painting,
 *  generous negative space, monochromatic color scheme, no text, silhouettes"
 */
class PromptBuilder
{
    public function __construct(
        private readonly ImageOptionsService $imageOptions,
    ) {
    }

    /**
     * Build a complete prompt from subject and options.
     *
     * @param string $subject The main subject of the image (e.g., "aikidoka meditating in dojo")
     * @param array{
     *     preset?: string,
     *     style?: string,
     *     composition?: string,
     *     palette?: string,
     *     extras?: string[],
     *     custom_prompt?: string
     * } $options Either a preset key or individual options
     *
     * @throws AiProviderException If preset or option not found
     */
    public function build(string $subject, array $options = []): string
    {
        // Extract custom_prompt before resolving preset (it's not part of presets)
        $customPrompt = isset($options['custom_prompt']) ? trim((string) $options['custom_prompt']) : '';

        // Resolve preset if provided
        if (isset($options['preset'])) {
            $options = $this->resolvePreset($options['preset'], $options);
        }

        // Start with subject
        $parts = [trim($subject)];

        // Add style
        if (!empty($options['style'])) {
            $prompt = $this->imageOptions->getStylePrompt($options['style']);
            if ($prompt === null) {
                throw new AiProviderException(sprintf('Unknown style: %s', $options['style']));
            }
            $parts[] = $prompt;
        }

        // Add composition
        if (!empty($options['composition'])) {
            $prompt = $this->imageOptions->getCompositionPrompt($options['composition']);
            if ($prompt === null) {
                throw new AiProviderException(sprintf('Unknown composition: %s', $options['composition']));
            }
            $parts[] = $prompt;
        }

        // Add palette
        if (!empty($options['palette'])) {
            $prompt = $this->imageOptions->getPalettePrompt($options['palette']);
            if ($prompt === null) {
                throw new AiProviderException(sprintf('Unknown palette: %s', $options['palette']));
            }
            $parts[] = $prompt;
        }

        // Add extras (predefined modifiers)
        if (!empty($options['extras']) && is_array($options['extras'])) {
            foreach ($options['extras'] as $extraKey) {
                $prompt = $this->imageOptions->getExtraPrompt($extraKey);
                if ($prompt === null) {
                    throw new AiProviderException(sprintf('Unknown extra: %s', $extraKey));
                }
                $parts[] = $prompt;
            }
        }

        // Add custom prompt (free text, appended at the end)
        if ($customPrompt !== '') {
            $parts[] = $customPrompt;
        }

        return implode(', ', $parts);
    }

    /**
     * Build a prompt using a preset.
     *
     * Convenience method for preset-only usage.
     *
     * @throws AiProviderException If preset not found
     */
    public function buildWithPreset(string $subject, string $presetKey): string
    {
        return $this->build($subject, ['preset' => $presetKey]);
    }

    /**
     * Build a prompt with individual options.
     *
     * Convenience method for individual options usage.
     *
     * @param string[] $extras
     * @param string|null $customPrompt Free text to append at the end
     *
     * @throws AiProviderException If any option not found
     */
    public function buildWithOptions(
        string $subject,
        ?string $style = null,
        ?string $composition = null,
        ?string $palette = null,
        array $extras = [],
        ?string $customPrompt = null,
    ): string {
        return $this->build($subject, [
            'style' => $style,
            'composition' => $composition,
            'palette' => $palette,
            'extras' => $extras,
            'custom_prompt' => $customPrompt,
        ]);
    }

    /**
     * Get the ImageOptionsService for direct access.
     */
    public function getImageOptions(): ImageOptionsService
    {
        return $this->imageOptions;
    }

    /**
     * Build only the style portion (without subject) from a preset.
     *
     * Useful for previewing what style will be applied.
     *
     * @throws AiProviderException If preset not found
     */
    public function buildFromPreset(string $presetKey): string
    {
        $preset = $this->imageOptions->getPreset($presetKey);

        if ($preset === null) {
            throw new AiProviderException(sprintf('Unknown preset: %s', $presetKey));
        }

        return $this->buildStyleOnly([
            'style' => $preset['style'],
            'composition' => $preset['composition'],
            'palette' => $preset['palette'],
            'extras' => $preset['extras'],
        ]);
    }

    /**
     * Build only the style portion (without subject) from individual options.
     *
     * @param array{style?: ?string, composition?: ?string, palette?: ?string, extras?: string[], custom_prompt?: string} $options
     */
    public function buildStyleOnly(array $options): string
    {
        $parts = [];

        // Add style
        if (!empty($options['style'])) {
            $prompt = $this->imageOptions->getStylePrompt($options['style']);
            if ($prompt !== null) {
                $parts[] = $prompt;
            }
        }

        // Add composition
        if (!empty($options['composition'])) {
            $prompt = $this->imageOptions->getCompositionPrompt($options['composition']);
            if ($prompt !== null) {
                $parts[] = $prompt;
            }
        }

        // Add palette
        if (!empty($options['palette'])) {
            $prompt = $this->imageOptions->getPalettePrompt($options['palette']);
            if ($prompt !== null) {
                $parts[] = $prompt;
            }
        }

        // Add extras
        if (!empty($options['extras']) && is_array($options['extras'])) {
            foreach ($options['extras'] as $extraKey) {
                $prompt = $this->imageOptions->getExtraPrompt($extraKey);
                if ($prompt !== null) {
                    $parts[] = $prompt;
                }
            }
        }

        // Add custom prompt
        if (isset($options['custom_prompt']) && trim((string) $options['custom_prompt']) !== '') {
            $parts[] = trim((string) $options['custom_prompt']);
        }

        return implode(', ', $parts);
    }

    /**
     * Build the global/default style.
     *
     * Returns an empty string if no default preset is configured,
     * or the style from the first available preset.
     */
    public function buildGlobalStyle(): string
    {
        // Use first preset as default if available
        $presets = $this->imageOptions->getPresets();
        if (!empty($presets)) {
            $firstPresetKey = array_key_first($presets);

            return $this->buildFromPreset($firstPresetKey);
        }

        return '';
    }

    /**
     * Resolve a preset to individual options.
     *
     * If individual options are also provided, they override the preset values.
     *
     * @param array<string, mixed> $overrides Additional options that override preset
     *
     * @return array{style?: string, composition?: string, palette?: string, extras?: string[]}
     *
     * @throws AiProviderException If preset not found
     */
    private function resolvePreset(string $presetKey, array $overrides = []): array
    {
        $preset = $this->imageOptions->getPreset($presetKey);

        if ($preset === null) {
            throw new AiProviderException(sprintf('Unknown preset: %s', $presetKey));
        }

        // Start with preset values
        $resolved = [
            'style' => $preset['style'],
            'composition' => $preset['composition'],
            'palette' => $preset['palette'],
            'extras' => $preset['extras'],
        ];

        // Override with individual options if provided (except 'preset' key)
        foreach (['style', 'composition', 'palette', 'extras'] as $key) {
            if (isset($overrides[$key]) && !empty($overrides[$key])) {
                $resolved[$key] = $overrides[$key];
            }
        }

        return $resolved;
    }
}
