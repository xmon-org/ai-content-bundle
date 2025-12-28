<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface for providing the global/default AI image style.
 *
 * Implement this interface in your project to provide custom style resolution.
 * The bundle provides a default implementation (YamlStyleProvider) that reads
 * from YAML configuration.
 *
 * Common use cases:
 * - Store style configuration in a database entity (e.g., Configuracion)
 * - Allow per-entity style overrides
 * - Dynamic style selection based on context
 *
 * Example implementation:
 *
 *     class ConfiguracionStyleProvider implements AiStyleProviderInterface
 *     {
 *         public function __construct(
 *             private readonly ConfiguracionRepository $repository,
 *         ) {}
 *
 *         public function getGlobalStyle(): string
 *         {
 *             $config = $this->repository->getConfiguracion();
 *             return $config?->getBaseStylePreview() ?? '';
 *         }
 *
 *         public function getPriority(): int
 *         {
 *             return 100; // Higher priority than YAML fallback
 *         }
 *     }
 *
 * The provider with the highest priority that returns a non-empty string wins.
 */
#[AutoconfigureTag('xmon_ai_content.style_provider')]
interface AiStyleProviderInterface
{
    /**
     * Get the global/default style for image generation.
     *
     * This is the style portion of the prompt that will be appended to the subject.
     * Example: "sumi-e Japanese ink wash painting style, minimalist composition, black and white"
     *
     * Return an empty string if this provider cannot provide a style.
     */
    public function getGlobalStyle(): string;

    /**
     * Get the priority for this provider.
     *
     * Higher priority providers are tried first.
     * The YAML-based fallback has priority 0.
     * Database-backed providers should use priority 100 or higher.
     */
    public function getPriority(): int;

    /**
     * Check if this provider is available.
     *
     * Return false if the provider's dependencies are not available
     * (e.g., the configuration entity doesn't exist).
     */
    public function isAvailable(): bool;

    /**
     * Get the default image model for automatic generation.
     *
     * This allows database-backed providers to override the YAML default model.
     * Return null to fall back to the next provider or YAML configuration.
     *
     * Example: 'flux', 'gptimage', 'turbo'
     */
    public function getDefaultImageModel(): ?string;
}
