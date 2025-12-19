<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\PromptBuilder;

/**
 * Sonata Admin Extension for AI image generation.
 *
 * This extension adds the necessary JavaScript and CSS assets for AI
 * image fields to work in Sonata Admin forms.
 *
 * To use this extension, configure it in your services.yaml:
 *
 *     App\Admin\ArticleAdmin:
 *         calls:
 *             - [addExtension, ['@Xmon\AiContentBundle\Admin\AiImageAdminExtension']]
 *
 * Or apply it globally to all admins via Sonata configuration:
 *
 *     sonata_admin:
 *         extensions:
 *             xmon.ai_content.admin.extension:
 *                 admins:
 *                     - App\Admin\ArticleAdmin
 */
class AiImageAdminExtension extends AbstractAdminExtension
{
    public function __construct(
        private readonly ImageOptionsService $imageOptionsService,
        private readonly PromptBuilder $promptBuilder,
    ) {
    }

    /**
     * Configure extra JavaScript files for the admin.
     *
     * @param array<string> $javascripts
     *
     * @return array<string>
     */
    public function configureExtraJavascripts(AdminInterface $admin, array $javascripts): array
    {
        // Add the AI image regenerator JavaScript
        $javascripts[] = 'bundles/xmonaicontent/js/ai-image-regenerator.js';

        return $javascripts;
    }

    /**
     * Configure extra CSS files for the admin.
     *
     * @param array<string> $stylesheets
     *
     * @return array<string>
     */
    public function configureExtraStylesheets(AdminInterface $admin, array $stylesheets): array
    {
        // Add the AI image styles
        $stylesheets[] = 'bundles/xmonaicontent/css/ai-image.css';

        return $stylesheets;
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
