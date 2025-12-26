<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Enum;

/**
 * Pollinations access tier for models.
 *
 * Models are available based on account tier:
 * - anonymous: Free, no API key required
 * - seed: Requires API key (free tier with key)
 * - flower: Premium tier (paid)
 */
enum ModelTier: string
{
    case ANONYMOUS = 'anonymous';
    case SEED = 'seed';
    case FLOWER = 'flower';

    /**
     * Get human-readable label for the tier.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ANONYMOUS => 'Free (no key)',
            self::SEED => 'Free (API key)',
            self::FLOWER => 'Premium',
        };
    }

    /**
     * Check if this tier requires an API key.
     */
    public function requiresApiKey(): bool
    {
        return $this !== self::ANONYMOUS;
    }

    /**
     * Check if this is a paid tier.
     */
    public function isPaid(): bool
    {
        return $this === self::FLOWER;
    }
}
