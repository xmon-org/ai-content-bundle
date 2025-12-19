<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Model;

/**
 * Immutable DTO representing the result of an image generation.
 */
final readonly class ImageResult
{
    public function __construct(
        private string $bytes,
        private string $mimeType,
        private string $provider,
        private int $width,
        private int $height,
        private ?string $model = null,
        private ?int $seed = null,
        private ?string $prompt = null,
    ) {
    }

    /**
     * Get the raw image bytes.
     */
    public function getBytes(): string
    {
        return $this->bytes;
    }

    /**
     * Get the MIME type (e.g., 'image/png', 'image/jpeg').
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the provider name that generated the image.
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get the image width in pixels.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get the image height in pixels.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Get the model used for generation (if available).
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * Get the seed used for generation (if available).
     */
    public function getSeed(): ?int
    {
        return $this->seed;
    }

    /**
     * Get the prompt used for generation.
     */
    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    /**
     * Get the file extension based on MIME type.
     */
    public function getExtension(): string
    {
        return match ($this->mimeType) {
            'image/png' => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'bin',
        };
    }

    /**
     * Get the image as a base64 encoded string.
     */
    public function toBase64(): string
    {
        return base64_encode($this->bytes);
    }

    /**
     * Get the image as a data URI.
     */
    public function toDataUri(): string
    {
        return 'data:'.$this->mimeType.';base64,'.$this->toBase64();
    }
}
