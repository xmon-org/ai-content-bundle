<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Style;

use Xmon\AiContentBundle\Provider\AiStyleProviderInterface;
use Xmon\AiContentBundle\Service\PromptBuilder;

/**
 * Default style provider that uses YAML configuration.
 *
 * This provider reads from the bundle's presets configuration (xmon_ai_content.presets)
 * and returns the first preset's style as the global default.
 *
 * Priority: 0 (lowest - serves as fallback)
 *
 * Other providers (e.g., database-backed) should use higher priority
 * to override this default behavior.
 */
final class YamlStyleProvider implements AiStyleProviderInterface
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder,
    ) {
    }

    public function getGlobalStyle(): string
    {
        return $this->promptBuilder->buildGlobalStyle();
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getDefaultImageModel(): ?string
    {
        // YAML provider returns null to fall back to service configuration
        // (xmon_ai_content.tasks.image_generation.default_model)
        // This allows database-backed providers to override.
        return null;
    }

    public function getAiDebugMode(): ?bool
    {
        // YAML provider returns null - debug mode is always configured via BD or env
        // This allows database-backed providers to control debug mode.
        return null;
    }
}
