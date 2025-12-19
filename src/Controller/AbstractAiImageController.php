<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Xmon\AiContentBundle\Entity\AiImageAwareInterface;
use Xmon\AiContentBundle\Entity\AiImageHistoryInterface;
use Xmon\AiContentBundle\Service\AiImageService;
use Xmon\AiContentBundle\Service\AiTextService;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\MediaStorageService;
use Xmon\AiContentBundle\Service\PromptBuilder;
use Xmon\AiContentBundle\Service\PromptTemplateService;

/**
 * Abstract controller for AI image regeneration.
 *
 * Extend this controller in your project and implement the abstract methods
 * to integrate AI image generation with your entities.
 *
 * Example implementation:
 *
 *     class ArticleImageController extends AbstractAiImageController
 *     {
 *         public function __construct(
 *             AiTextService $textService,
 *             AiImageService $imageService,
 *             ImageOptionsService $imageOptionsService,
 *             PromptBuilder $promptBuilder,
 *             PromptTemplateService $promptTemplateService,
 *             ?MediaStorageService $mediaStorage,
 *             private ArticleRepository $articleRepository,
 *             private ArticleImageHistoryRepository $historyRepository,
 *         ) {
 *             parent::__construct(...);
 *         }
 *
 *         protected function findEntity(int $id): ?AiImageAwareInterface {
 *             return $this->articleRepository->find($id);
 *         }
 *
 *         // ... implement other abstract methods
 *     }
 */
abstract class AbstractAiImageController extends AbstractController
{
    public function __construct(
        protected readonly AiTextService $textService,
        protected readonly AiImageService $imageService,
        protected readonly ImageOptionsService $imageOptionsService,
        protected readonly PromptBuilder $promptBuilder,
        protected readonly PromptTemplateService $promptTemplateService,
        protected readonly ?MediaStorageService $mediaStorage = null,
    ) {
    }

    /**
     * Generate a subject (image description) for an entity.
     *
     * Expects POST request.
     * Returns JSON: { success: bool, prompt?: string, model?: string, error?: string }
     */
    protected function doGenerateSubject(int $id): JsonResponse
    {
        set_time_limit(60);

        $entity = $this->findEntity($id);
        if (!$entity) {
            return $this->errorResponse('Entity not found', Response::HTTP_NOT_FOUND);
        }

        if (!$this->textService->isConfigured()) {
            return $this->errorResponse('AI text service is not configured', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            // Get the template for image subject generation
            $template = $this->promptTemplateService->get('image_subject');

            // Generate the subject using the template
            $result = $this->textService->generate(
                systemPrompt: $template['system'] ?? 'You are a helpful assistant that creates image descriptions.',
                userMessage: \sprintf(
                    $template['user'] ?? 'Create a visual description for: %s',
                    $entity->getContentForImageGeneration()
                ),
            );

            return new JsonResponse([
                'success' => true,
                'prompt' => $result->getText(),
                'model' => $result->getModel(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error: '.$e->getMessage());
        }
    }

    /**
     * Regenerate an image for an entity.
     *
     * Expects POST request with:
     * - prompt: string (subject)
     * - styleMode: 'global' | 'preset' | 'custom'
     * - stylePreset?: string (when mode = preset)
     * - styleStyle?, styleComposition?, stylePalette?, styleExtra?: string (when mode = custom)
     *
     * Returns JSON with generated image info.
     */
    protected function doRegenerateImage(int $id, Request $request): JsonResponse
    {
        set_time_limit(180);

        $entity = $this->findEntity($id);
        if (!$entity) {
            return $this->errorResponse('Entity not found', Response::HTTP_NOT_FOUND);
        }

        $subject = $request->request->get('prompt');
        if (empty($subject)) {
            return $this->errorResponse('Subject cannot be empty', Response::HTTP_BAD_REQUEST);
        }

        if (!$this->imageService->isConfigured()) {
            return $this->errorResponse('AI image service is not configured', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            // Resolve style based on mode
            $styleMode = $request->request->get('styleMode', 'global');
            $baseStyle = $this->resolveStyle($styleMode, $request);

            // Build full prompt: subject + style
            $fullPrompt = trim($subject).($baseStyle ? ', '.$baseStyle : '');

            // Generate the image
            $imageResult = $this->imageService->generate($fullPrompt);

            // Store the image (if MediaStorageService is available)
            $media = null;
            if ($this->mediaStorage) {
                $media = $this->mediaStorage->store(
                    $imageResult,
                    $this->getMediaContext(),
                    $this->generateFilename($entity)
                );
            }

            // Create history entry
            $historyItem = $this->createHistoryItem($entity, $media, trim($subject), $baseStyle, $imageResult->getProvider());

            // Get image URL for response
            $imageUrl = $media ? $this->getMediaUrl($media) : $imageResult->toDataUri();

            return new JsonResponse([
                'success' => true,
                'message' => 'Image regenerated successfully',
                'mediaId' => $media ? $this->getMediaId($media) : null,
                'imageUrl' => $imageUrl,
                'historialId' => $historyItem?->getId(),
                'subject' => trim($subject),
                'style' => $baseStyle,
                'promptUsed' => $fullPrompt,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error: '.$e->getMessage());
        }
    }

    /**
     * Use an image from history as the featured image.
     */
    protected function doUseHistoryImage(int $entityId, int $historyId): JsonResponse
    {
        $entity = $this->findEntity($entityId);
        if (!$entity) {
            return $this->errorResponse('Entity not found', Response::HTTP_NOT_FOUND);
        }

        $historyItem = $this->findHistoryItem($historyId);
        if (!$historyItem || !$this->historyBelongsToEntity($historyItem, $entity)) {
            return $this->errorResponse('History item not found', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->applyHistoryToEntity($entity, $historyItem);

            return new JsonResponse([
                'success' => true,
                'message' => 'Image updated successfully',
                'mediaId' => $this->getMediaId($historyItem->getImage()),
                'imageUrl' => $this->getMediaUrl($historyItem->getImage()),
                'subject' => $historyItem->getSubject(),
                'style' => $historyItem->getStyle(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error: '.$e->getMessage());
        }
    }

    /**
     * Delete an image from history.
     */
    protected function doDeleteHistoryImage(int $entityId, int $historyId): JsonResponse
    {
        $entity = $this->findEntity($entityId);
        if (!$entity) {
            return $this->errorResponse('Entity not found', Response::HTTP_NOT_FOUND);
        }

        $historyItem = $this->findHistoryItem($historyId);
        if (!$historyItem || !$this->historyBelongsToEntity($historyItem, $entity)) {
            return $this->errorResponse('History item not found', Response::HTTP_NOT_FOUND);
        }

        // Check if it's the current image
        $currentImage = $entity->getFeaturedImage();
        if ($currentImage && $this->getMediaId($currentImage) === $this->getMediaId($historyItem->getImage())) {
            return $this->errorResponse(
                'Cannot delete the image currently in use. Select another image first.',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->deleteHistoryItem($historyItem);

            return new JsonResponse([
                'success' => true,
                'message' => 'Image deleted from history',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error: '.$e->getMessage());
        }
    }

    /**
     * Resolve the style string based on the mode selected.
     */
    protected function resolveStyle(string $styleMode, Request $request): string
    {
        return match ($styleMode) {
            'preset' => $this->resolvePresetStyle($request),
            'custom' => $this->resolveCustomStyle($request),
            default => $this->resolveGlobalStyle(),
        };
    }

    /**
     * Resolve style from a preset.
     */
    protected function resolvePresetStyle(Request $request): string
    {
        $presetKey = $request->request->get('stylePreset', '');
        if (!empty($presetKey) && $this->imageOptionsService->hasPreset($presetKey)) {
            return $this->promptBuilder->buildFromPreset($presetKey);
        }

        return $this->resolveGlobalStyle();
    }

    /**
     * Resolve style from individual custom options.
     */
    protected function resolveCustomStyle(Request $request): string
    {
        return $this->promptBuilder->buildStyleOnly([
            'style' => $request->request->get('styleStyle'),
            'composition' => $request->request->get('styleComposition'),
            'palette' => $request->request->get('stylePalette'),
            'custom_prompt' => $request->request->get('styleExtra'),
        ]);
    }

    /**
     * Resolve the global/default style.
     */
    protected function resolveGlobalStyle(): string
    {
        return $this->promptBuilder->buildGlobalStyle();
    }

    /**
     * Create a JSON error response.
     */
    protected function errorResponse(string $message, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => $message,
        ], $status);
    }

    /**
     * Generate a filename for the image.
     */
    protected function generateFilename(AiImageAwareInterface $entity): string
    {
        return \sprintf('ai-image-%d-%s', $entity->getId(), date('YmdHis'));
    }

    // ==========================================
    // ABSTRACT METHODS - Must be implemented
    // ==========================================

    /**
     * Find the entity by ID.
     */
    abstract protected function findEntity(int $id): ?AiImageAwareInterface;

    /**
     * Create a new history item for the entity.
     *
     * @param object|null $media The stored media object (SonataMedia, etc.)
     */
    abstract protected function createHistoryItem(
        AiImageAwareInterface $entity,
        ?object $media,
        string $subject,
        string $style,
        string $model,
    ): ?AiImageHistoryInterface;

    /**
     * Find a history item by ID.
     */
    abstract protected function findHistoryItem(int $id): ?AiImageHistoryInterface;

    /**
     * Check if a history item belongs to the entity.
     */
    abstract protected function historyBelongsToEntity(AiImageHistoryInterface $history, AiImageAwareInterface $entity): bool;

    /**
     * Apply history item data to the entity (set as featured image).
     */
    abstract protected function applyHistoryToEntity(AiImageAwareInterface $entity, AiImageHistoryInterface $history): void;

    /**
     * Delete a history item and its associated media.
     */
    abstract protected function deleteHistoryItem(AiImageHistoryInterface $history): void;

    /**
     * Get the URL of a media object.
     */
    abstract protected function getMediaUrl(object $media): string;

    /**
     * Get the ID of a media object.
     */
    abstract protected function getMediaId(object $media): int|string;

    /**
     * Get the media context for storage.
     */
    abstract protected function getMediaContext(): string;
}
