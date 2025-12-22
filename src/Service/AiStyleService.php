<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Xmon\AiContentBundle\Provider\AiStyleProviderInterface;

/**
 * Service that aggregates style providers and returns the global style.
 *
 * Style providers are sorted by priority (highest first).
 * The first available provider that returns a non-empty style wins.
 *
 * This allows projects to override the default YAML-based style
 * with database-backed or context-aware implementations.
 */
final class AiStyleService
{
    /**
     * @var AiStyleProviderInterface[]
     */
    private array $sortedProviders;

    /**
     * @param iterable<AiStyleProviderInterface> $providers
     */
    public function __construct(
        #[TaggedIterator('xmon_ai_content.style_provider')]
        iterable $providers,
    ) {
        $this->sortedProviders = $this->sortProviders($providers);
    }

    /**
     * Get the global/default style from the highest priority provider.
     */
    public function getGlobalStyle(): string
    {
        foreach ($this->sortedProviders as $provider) {
            if (!$provider->isAvailable()) {
                continue;
            }

            $style = $provider->getGlobalStyle();
            if ($style !== '') {
                return $style;
            }
        }

        return '';
    }

    /**
     * Get all available providers (for debugging).
     *
     * @return array<array{class: string, priority: int, available: bool}>
     */
    public function getProviderInfo(): array
    {
        $info = [];
        foreach ($this->sortedProviders as $provider) {
            $info[] = [
                'class' => $provider::class,
                'priority' => $provider->getPriority(),
                'available' => $provider->isAvailable(),
            ];
        }

        return $info;
    }

    /**
     * Sort providers by priority (highest first).
     *
     * @param iterable<AiStyleProviderInterface> $providers
     *
     * @return AiStyleProviderInterface[]
     */
    private function sortProviders(iterable $providers): array
    {
        $providerArray = [...$providers];

        usort($providerArray, static fn (
            AiStyleProviderInterface $a,
            AiStyleProviderInterface $b,
        ): int => $b->getPriority() <=> $a->getPriority());

        return $providerArray;
    }
}
