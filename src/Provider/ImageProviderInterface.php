<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider;

use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\ImageResult;

interface ImageProviderInterface
{
    /**
     * Get the provider name (e.g., 'pollinations', 'together').
     */
    public function getName(): string;

    /**
     * Check if the provider is available (has required credentials, etc.).
     */
    public function isAvailable(): bool;

    /**
     * Generate an image from a prompt.
     *
     * @param string $prompt The text prompt describing the image
     * @param array{
     *     width?: int,
     *     height?: int,
     *     model?: string,
     *     seed?: int,
     *     nologo?: bool,
     *     enhance?: bool
     * } $options Additional generation options
     *
     * @throws AiProviderException When image generation fails
     */
    public function generate(string $prompt, array $options = []): ImageResult;
}
