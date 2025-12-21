<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Service;

use Psr\Log\LoggerInterface;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Xmon\AiContentBundle\Model\ImageResult;

class MediaStorageService
{
    public function __construct(
        private readonly MediaManagerInterface $mediaManager,
        private readonly ?LoggerInterface $logger = null,
        private readonly string $defaultContext = 'default',
        private readonly string $providerName = 'sonata.media.provider.image',
    ) {
    }

    /**
     * Save an ImageResult to SonataMedia.
     *
     * @param ImageResult $imageResult The generated image
     * @param string|null $filename    Optional filename (auto-generated if null)
     * @param string|null $context     SonataMedia context (uses default if null)
     *
     * @return MediaInterface The created media entity
     */
    public function save(
        ImageResult $imageResult,
        ?string $filename = null,
        ?string $context = null,
    ): MediaInterface {
        $context = $context ?? $this->defaultContext;
        $filename = $filename ?? $this->generateFilename($imageResult);

        $this->logger?->info('[MediaStorage] Saving image to SonataMedia', [
            'context' => $context,
            'filename' => $filename,
            'size' => \strlen($imageResult->getBytes()),
            'mime_type' => $imageResult->getMimeType(),
        ]);

        // Create temp file from bytes
        $tempFile = $this->createTempFile($imageResult);

        try {
            // Create Sonata Media entity
            $media = $this->mediaManager->create();
            $media->setBinaryContent(new File($tempFile));
            $media->setContext($context);
            $media->setProviderName($this->providerName);
            $media->setName($filename);
            $media->setEnabled(true);

            // Save using MediaManager
            $this->mediaManager->save($media);

            $this->logger?->info('[MediaStorage] Image saved successfully', [
                'media_id' => $media->getId(),
            ]);

            return $media;
        } finally {
            // Clean up temp file
            @unlink($tempFile);
        }
    }

    /**
     * Generate a filename from ImageResult metadata.
     */
    private function generateFilename(ImageResult $imageResult): string
    {
        $provider = $imageResult->getProvider();
        $timestamp = date('Ymd-His');
        $extension = $imageResult->getExtension();

        return \sprintf('ai-%s-%s.%s', $provider, $timestamp, $extension);
    }

    /**
     * Create a temporary file from ImageResult bytes.
     */
    private function createTempFile(ImageResult $imageResult): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ai_image_');
        $extension = $imageResult->getExtension();
        $tempFilePath = $tempFile.'.'.$extension;

        rename($tempFile, $tempFilePath);
        file_put_contents($tempFilePath, $imageResult->getBytes());

        return $tempFilePath;
    }
}
