<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\PromptBuilder;

/**
 * Sonata Admin Extension for AI image generation.
 *
 * This extension provides helper methods for AI image generation in Sonata Admin.
 * JavaScript and CSS assets must be configured separately in sonata_admin.yaml:
 *
 *     sonata_admin:
 *         assets:
 *             extra_javascripts:
 *                 - bundles/xmonaicontent/js/ai-image-regenerator.js
 *             extra_stylesheets:
 *                 - bundles/xmonaicontent/css/ai-image.css
 *         extensions:
 *             Xmon\AiContentBundle\Admin\AiImageAdminExtension:
 *                 admins:
 *                     - admin.noticia
 */
class AiImageAdminExtension extends AbstractAdminExtension
{
    public function __construct(
        private readonly ImageOptionsService $imageOptionsService,
        private readonly PromptBuilder $promptBuilder,
    ) {
    }

    /**
     * Get the ImageOptionsService for use in Admin classes.
     */
    public function getImageOptionsService(): ImageOptionsService
    {
        return $this->imageOptionsService;
    }

    /**
     * Get the PromptBuilder for use in Admin classes.
     */
    public function getPromptBuilder(): PromptBuilder
    {
        return $this->promptBuilder;
    }

    /**
     * Get all presets for select fields.
     *
     * @return array<string, string> Key => Label pairs
     */
    public function getPresetChoices(): array
    {
        return array_flip($this->imageOptionsService->getPresets());
    }

    /**
     * Get all styles for select fields.
     *
     * @return array<string, string> Key => Label pairs
     */
    public function getStyleChoices(): array
    {
        return array_flip($this->imageOptionsService->getStyles());
    }

    /**
     * Get all compositions for select fields.
     *
     * @return array<string, string> Key => Label pairs
     */
    public function getCompositionChoices(): array
    {
        return array_flip($this->imageOptionsService->getCompositions());
    }

    /**
     * Get all palettes for select fields.
     *
     * @return array<string, string> Key => Label pairs
     */
    public function getPaletteChoices(): array
    {
        return array_flip($this->imageOptionsService->getPalettes());
    }

    /**
     * Get the global style preview string.
     */
    public function getGlobalStylePreview(): string
    {
        return $this->promptBuilder->buildGlobalStyle();
    }

    /**
     * Get all presets data for JavaScript.
     *
     * @return array<string, array{name: string, preview: string}>
     */
    public function getPresetsData(): array
    {
        $result = [];
        foreach ($this->imageOptionsService->getPresets() as $key => $name) {
            $result[$key] = [
                'name' => $name,
                'preview' => $this->promptBuilder->buildFromPreset($key),
            ];
        }

        return $result;
    }
}
