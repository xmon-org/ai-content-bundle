<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\TextResult;

/**
 * Interface for text generation providers.
 *
 * Any class implementing this interface will be automatically registered
 * as a text provider and included in the fallback chain.
 *
 * To create a custom provider in your project:
 * 1. Create a class implementing this interface
 * 2. The provider will be auto-discovered (thanks to AutoconfigureTag)
 * 3. Configure priority via getPriority() method
 */
#[AutoconfigureTag('xmon_ai_content.text_provider')]
interface TextProviderInterface
{
    /**
     * Get the provider name (e.g., 'gemini', 'openrouter', 'pollinations')
     */
    public function getName(): string;

    /**
     * Check if the provider is available (has required credentials, etc.)
     */
    public function isAvailable(): bool;

    /**
     * Get the priority for fallback ordering (higher = tried first)
     */
    public function getPriority(): int;

    /**
     * Generate text from a prompt
     *
     * @param string $systemPrompt The system prompt/instructions
     * @param string $userMessage The user message/content to process
     * @param array{
     *     model?: string,
     *     temperature?: float,
     *     max_tokens?: int
     * } $options Additional generation options
     *
     * @throws AiProviderException When text generation fails
     */
    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult;
}
