<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Xmon\AiContentBundle\Entity\AiImageAwareInterface;
use Xmon\AiContentBundle\Entity\AiImageContextInterface;
use Xmon\AiContentBundle\Entity\AiImageHistoryInterface;
use Xmon\AiContentBundle\Entity\AiPromptVariablesInterface;
use Xmon\AiContentBundle\Service\AiImageService;
use Xmon\AiContentBundle\Service\AiStyleService;
use Xmon\AiContentBundle\Service\AiTextService;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\MediaStorageService;
use Xmon\AiContentBundle\Service\PromptBuilder;
use Xmon\AiContentBundle\Service\PromptTemplateService;

/**
 * Abstract controller for AI image regeneration.
 *
 * Provides two usage modes:
 *
 * 1. **Dedicated Page Mode**: Full-featured page with subject input, style selector,
 *    preview comparison, and image history. Use `doRenderPage()` for rendering.
 *
 * 2. **API Mode**: JSON endpoints for AJAX operations. Use `doGenerateSubject()`,
 *    `doRegenerateImage()`, `doUseHistoryImage()`, and `doDeleteHistoryImage()`.
 *
 * Extend this controller in your project and implement the abstract methods
 * to integrate AI image generation with your entities.
 *
 * **Recommended**: Use the `AiImageRoutesTrait` to automatically get all routes.
 * This eliminates boilerplate and ensures new endpoints are available immediately.
 *
 * Example implementation (with trait - recommended):
 *
 *     #[Route('/admin/article/{id}/ai-image', name: 'admin_article_ai_image')]
 *     class ArticleImageController extends AbstractAiImageController
 *     {
 *         use AiImageRoutesTrait; // All routes defined automatically!
 *
 *         public function __construct(
 *             AiTextService $textService,
 *             // ... other dependencies
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
 *
 * Example implementation (manual routes):
 *
 *     #[Route('/admin/article/{id}/ai-image', name: 'admin_article_ai_image')]
 *     class ArticleImageController extends AbstractAiImageController
 *     {
 *         #[Route('', name: '_page', methods: ['GET'])]
 *         public function page(int $id): Response {
 *             return $this->doRenderPage($id);
 *         }
 *
 *         #[Route('/generate-subject', name: '_generate_subject', methods: ['POST'])]
 *         public function generateSubject(int $id): JsonResponse {
 *             return $this->doGenerateSubject($id);
 *         }
 *
 *         // ... define routes for other endpoints
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
        protected readonly ?AiStyleService $styleService = null,
        protected readonly int $maxHistoryImages = 5,
    ) {
    }

    /**
     * Get the maximum number of images to keep in history.
     *
     * Override this method to get the value dynamically (e.g., from a database entity).
     *
     * By default, uses the value from bundle configuration (xmon_ai_content.history.max_images)
     * or the fallback value of 5.
     *
     * Example override:
     *     protected function getMaxHistoryImages(): int
     *     {
     *         $config = $this->configRepository->getConfiguration();
     *         return $config?->getMaxHistoryImages() ?? parent::getMaxHistoryImages();
     *     }
     */
    protected function getMaxHistoryImages(): int
    {
        return $this->maxHistoryImages;
    }

    // ==========================================
    // PAGE RENDERING
    // ==========================================

    /**
     * Render the dedicated AI image generation page.
     *
     * This is the main entry point for the full-featured page mode.
     * Override `getTemplate()` to use a custom template.
     *
     * @param int    $id      Entity ID
     * @param string $subject Optional pre-filled subject (from query string)
     */
    protected function doRenderPage(int $id, string $subject = ''): Response
    {
        $entity = $this->findEntity($id);
        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        // Get current image info
        $currentImageUrl = null;
        $currentImageId = null;
        $currentImage = $entity->getFeaturedImage();
        if ($currentImage) {
            $currentImageId = $this->getMediaId($currentImage);
            $currentImageUrl = $this->getMediaUrl($currentImage);
        }

        // Use provided subject or get from entity
        if (empty($subject)) {
            $subject = $entity->getImageSubject() ?? '';
        }

        // Get history with formatted data for template
        $historyData = $this->getFormattedHistory($entity, $currentImageId);
        $historyCount = \count($historyData);
        $historyLimit = $this->getMaxHistoryImages();

        // Prepare style options for the template
        // Use resolveGlobalStyle() so subclasses can override (e.g., read from database)
        $globalStylePreview = $this->resolveGlobalStyle();

        return $this->render($this->getTemplate(), [
            // Entity data
            'entity' => $entity,
            'entityTitle' => $this->getEntityTitle($entity),
            'entityContext' => $this->getEntityContext($entity),

            // Current image
            'currentImageUrl' => $currentImageUrl,
            'currentImageId' => $currentImageId,

            // Subject
            'subject' => $subject,
            'currentStyle' => $entity->getImageStyle(),

            // History
            'history' => $historyData,
            'historyCount' => $historyCount,
            'historyLimit' => $historyLimit,
            'isAtLimit' => $historyCount >= $historyLimit,

            // Style options (for selects - labels only)
            'presets' => $this->getPresetsForTemplate(),
            'styles' => $this->imageOptionsService->getStyles(),
            'compositions' => $this->imageOptionsService->getCompositions(),
            'palettes' => $this->imageOptionsService->getPalettes(),
            'extras' => $this->imageOptionsService->getExtras(),

            // Full style data (for JavaScript preview - includes prompts)
            'stylesData' => $this->imageOptionsService->getAllStylesData(),
            'compositionsData' => $this->imageOptionsService->getAllCompositionsData(),
            'palettesData' => $this->imageOptionsService->getAllPalettesData(),

            // Preview data
            'globalStylePreview' => $globalStylePreview,

            // Service availability flags
            'textServiceConfigured' => $this->textService->isConfigured(),
            'imageServiceConfigured' => $this->imageService->isConfigured(),

            // Routes for AJAX calls (to be implemented by child)
            'routes' => $this->getRoutes($id),

            // Navigation
            'backUrl' => $this->getBackUrl($entity),
            'listUrl' => $this->getListUrl($entity),
        ]);
    }

    /**
     * Get presets formatted for the template (with preview).
     *
     * @return array<string, array{name: string, preview: string}>
     */
    protected function getPresetsForTemplate(): array
    {
        $presets = [];
        foreach ($this->imageOptionsService->getPresets() as $key => $name) {
            $presets[$key] = [
                'name' => $name,
                'preview' => $this->promptBuilder->buildFromPreset($key),
            ];
        }

        return $presets;
    }

    /**
     * Get formatted history data for the template.
     *
     * @return array<int, array{id: int, imageUrl: string, mediaId: int|string, subject: string, style: string, model: string, createdAt: string, isActual: bool}>
     */
    protected function getFormattedHistory(AiImageAwareInterface $entity, int|string|null $currentImageId): array
    {
        $history = $this->getEntityHistory($entity);
        $formatted = [];

        foreach ($history as $item) {
            $mediaId = $this->getMediaId($item->getImage());
            $formatted[] = [
                'id' => $item->getId(),
                'imageUrl' => $this->getMediaUrl($item->getImage()),
                'mediaId' => $mediaId,
                'subject' => $item->getSubject(),
                'style' => $item->getStyle(),
                'model' => $item->getModel(),
                'createdAt' => $item->getCreatedAt()->format('d/m/Y H:i'),
                'isActual' => $currentImageId !== null && $mediaId === $currentImageId,
            ];
        }

        return $formatted;
    }

    /**
     * Check if text service is available (for "Generate subject" button).
     */
    public function isTextServiceConfigured(): bool
    {
        return $this->textService->isConfigured();
    }

    /**
     * Check if image service is available.
     */
    public function isImageServiceConfigured(): bool
    {
        return $this->imageService->isConfigured();
    }

    // ==========================================
    // API ENDPOINTS
    // ==========================================

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
            // Build variables for the template
            // If entity implements AiPromptVariablesInterface, use its variables
            // Otherwise, fall back to just {content}
            if ($entity instanceof AiPromptVariablesInterface) {
                $variables = $entity->getPromptVariables();
            } else {
                $variables = ['content' => $entity->getContentForImageGeneration()];
            }

            // Render the template with variables (replaces {placeholder} syntax)
            $prompts = $this->promptTemplateService->render('image_subject', $variables);

            // Generate the subject using the rendered template
            $result = $this->textService->generate(
                systemPrompt: $prompts['system'],
                userMessage: $prompts['user'],
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
                $media = $this->mediaStorage->save(
                    $imageResult,
                    $this->generateFilename($entity),
                    $this->getMediaContext()
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
     * Batch delete multiple history images.
     *
     * Expects POST with form data: ids[] = [1, 2, 3]
     *
     * Returns JSON with:
     * - success: bool
     * - deletedCount: int
     * - errors: array of error messages
     * - remainingCount: int (current history count after deletion)
     */
    protected function doBatchDeleteHistoryImages(int $entityId, Request $request): JsonResponse
    {
        $entity = $this->findEntity($entityId);
        if (!$entity) {
            return $this->errorResponse('Entity not found', Response::HTTP_NOT_FOUND);
        }

        $ids = $request->request->all('ids');
        if (empty($ids) || !\is_array($ids)) {
            return $this->errorResponse('Invalid request: ids array required', Response::HTTP_BAD_REQUEST);
        }

        $currentImage = $entity->getFeaturedImage();
        $currentImageId = $currentImage ? $this->getMediaId($currentImage) : null;

        $deletedCount = 0;
        $errors = [];

        foreach ($ids as $historyId) {
            $historyItem = $this->findHistoryItem((int) $historyId);

            if (!$historyItem || !$this->historyBelongsToEntity($historyItem, $entity)) {
                $errors[] = "History item {$historyId} not found";
                continue;
            }

            // Cannot delete current image
            if ($currentImageId !== null && $this->getMediaId($historyItem->getImage()) === $currentImageId) {
                $errors[] = 'Cannot delete current image';
                continue;
            }

            try {
                $this->deleteHistoryItem($historyItem);
                ++$deletedCount;
            } catch (\Exception $e) {
                $errors[] = "Error deleting {$historyId}: ".$e->getMessage();
            }
        }

        return new JsonResponse([
            'success' => $deletedCount > 0,
            'message' => "Deleted {$deletedCount} image(s)",
            'deletedCount' => $deletedCount,
            'errors' => $errors,
            'remainingCount' => \count($this->getEntityHistory($entity)),
        ]);
    }

    /**
     * Get current history status.
     *
     * Returns JSON with:
     * - count: Current number of images in history
     * - limit: Maximum allowed images
     * - isAtLimit: Boolean
     * - canDeleteCount: Number of images that can be deleted (excluding current)
     */
    protected function doGetHistoryStatus(int $entityId): JsonResponse
    {
        $entity = $this->findEntity($entityId);
        if (!$entity) {
            return $this->errorResponse('Entity not found', Response::HTTP_NOT_FOUND);
        }

        $history = $this->getEntityHistory($entity);
        $count = \count($history);
        $limit = $this->getMaxHistoryImages();

        $currentImage = $entity->getFeaturedImage();
        $currentImageId = $currentImage ? $this->getMediaId($currentImage) : null;

        // Count images that can be deleted (not current)
        $canDeleteCount = 0;
        foreach ($history as $item) {
            if ($currentImageId === null || $this->getMediaId($item->getImage()) !== $currentImageId) {
                ++$canDeleteCount;
            }
        }

        return new JsonResponse([
            'success' => true,
            'count' => $count,
            'limit' => $limit,
            'isAtLimit' => $count >= $limit,
            'canDeleteCount' => $canDeleteCount,
        ]);
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
     *
     * Uses the AiStyleService to get the style from registered providers.
     * This allows database-backed providers to override the default YAML config.
     *
     * Override this method for custom style resolution logic.
     */
    protected function resolveGlobalStyle(): string
    {
        // Use style service if available (supports multiple providers)
        if ($this->styleService !== null) {
            $style = $this->styleService->getGlobalStyle();
            if ($style !== '') {
                return $style;
            }
        }

        // Fallback to PromptBuilder (uses first preset from YAML)
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

    /**
     * Get the image history for an entity.
     *
     * @return AiImageHistoryInterface[]
     */
    abstract protected function getEntityHistory(AiImageAwareInterface $entity): array;

    /**
     * Get a display title for the entity.
     *
     * Used in the page header and breadcrumbs.
     * Example: return $entity->getTitle();
     */
    abstract protected function getEntityTitle(AiImageAwareInterface $entity): string;

    /**
     * Get context information for the entity.
     *
     * If the entity implements AiImageContextInterface, returns its context.
     * Otherwise, returns an empty array.
     *
     * This is used to display additional information in the Context Banner
     * on the AI image generation page.
     *
     * @return array<string, string|int|null>
     */
    protected function getEntityContext(AiImageAwareInterface $entity): array
    {
        if ($entity instanceof AiImageContextInterface) {
            return array_filter(
                $entity->getAiImageContext(),
                static fn ($value) => $value !== null && $value !== ''
            );
        }

        return [];
    }

    /**
     * Get the URL to go back to the entity edit page.
     *
     * Example: return $this->generateUrl('admin_app_article_edit', ['id' => $entity->getId()]);
     */
    abstract protected function getBackUrl(AiImageAwareInterface $entity): string;

    /**
     * Get the URL to go to the entity list page.
     *
     * Example: return $this->generateUrl('admin_app_article_list');
     */
    abstract protected function getListUrl(AiImageAwareInterface $entity): string;

    /**
     * Get the routes for AJAX operations.
     *
     * By default, auto-detects routes from the controller's #[Route] attribute.
     * This works automatically when using AiImageRoutesTrait.
     *
     * Override this method if you need custom route names or parameters.
     *
     * Returns an associative array with keys:
     * - generateSubject: Route for generating subject via AI
     * - regenerateImage: Route for generating/regenerating image
     * - useHistory: Route template for using a history item (use HISTORY_ID as placeholder)
     * - deleteHistory: Route template for deleting a history item (use HISTORY_ID as placeholder)
     * - batchDeleteHistory: Route for batch deletion of history images
     * - historyStatus: Route to get current history status
     *
     * @return array{generateSubject: string, regenerateImage: string, useHistory: string, deleteHistory: string, batchDeleteHistory: string, historyStatus: string}
     */
    protected function getRoutes(int $entityId): array
    {
        $baseName = $this->getRouteBaseName();

        return [
            'generateSubject' => $this->generateUrl("{$baseName}_generate_subject", ['id' => $entityId]),
            'regenerateImage' => $this->generateUrl("{$baseName}_regenerate", ['id' => $entityId]),
            'useHistory' => $this->generateUrl("{$baseName}_use_history", [
                'id' => $entityId,
                'historyId' => 'HISTORY_ID',
            ]),
            'deleteHistory' => $this->generateUrl("{$baseName}_delete_history", [
                'id' => $entityId,
                'historyId' => 'HISTORY_ID',
            ]),
            'batchDeleteHistory' => $this->generateUrl("{$baseName}_batch_delete", ['id' => $entityId]),
            'historyStatus' => $this->generateUrl("{$baseName}_history_status", ['id' => $entityId]),
        ];
    }

    /**
     * Get the base route name from the controller's #[Route] attribute.
     *
     * This extracts the 'name' parameter from the class-level Route attribute.
     * For example, if the controller has:
     *     #[Route('/admin/article/{id}/ai-image', name: 'admin_article_ai_image')]
     *
     * This method returns: 'admin_article_ai_image'
     *
     * @throws \LogicException If the controller doesn't have a Route attribute with a name
     */
    protected function getRouteBaseName(): string
    {
        $reflection = new \ReflectionClass($this);
        $attributes = $reflection->getAttributes(Route::class);

        if (empty($attributes)) {
            throw new \LogicException(\sprintf('Controller %s must have a #[Route] attribute with a name parameter, or override getRoutes() method.', static::class));
        }

        $routeAttribute = $attributes[0]->newInstance();

        if (empty($routeAttribute->getName())) {
            throw new \LogicException(\sprintf('The #[Route] attribute on controller %s must have a name parameter, or override getRoutes() method.', static::class));
        }

        return $routeAttribute->getName();
    }

    /**
     * Get the Twig template to use for the page.
     *
     * Override this method to use a custom template.
     * The default template is provided by the bundle.
     */
    protected function getTemplate(): string
    {
        return '@XmonAiContent/admin/ai_image_page.html.twig';
    }
}
