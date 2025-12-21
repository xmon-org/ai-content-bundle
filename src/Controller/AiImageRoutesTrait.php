<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Trait providing standard routes for AI image generation controllers.
 *
 * This trait eliminates boilerplate by providing all the route definitions
 * that delegate to the AbstractAiImageController's do*() methods.
 *
 * IMPORTANT: The using controller MUST have a route prefix, for example:
 *
 *     #[Route('/admin/article/{id}/ai-image', name: 'admin_article_ai_image')]
 *     class ArticleAiImageController extends AbstractAiImageController
 *     {
 *         use AiImageRoutesTrait;
 *         // Only implement abstract methods - routes are handled by the trait
 *     }
 *
 * The trait provides these endpoints (relative to the class prefix):
 *
 * | Method | Path                        | Name suffix        | Description                    |
 * |--------|-----------------------------|--------------------|--------------------------------|
 * | GET    | ''                          | _page              | Render the AI image page       |
 * | POST   | /generate-subject           | _generate_subject  | Generate subject via AI        |
 * | POST   | /regenerate                 | _regenerate        | Generate/regenerate image      |
 * | POST   | /history/{historyId}/use    | _use_history       | Use history image as featured  |
 * | DELETE | /history/{historyId}        | _delete_history    | Delete single history image    |
 * | POST   | /history/batch-delete       | _batch_delete      | Batch delete history images    |
 * | GET    | /history/status             | _history_status    | Get history count and limit    |
 *
 * Example: If the controller has:
 *     #[Route('/admin/noticia/{id}/ai-image', name: 'admin_noticia_ai_image')]
 *
 * Then routes will be:
 *     - admin_noticia_ai_image_page -> /admin/noticia/{id}/ai-image
 *     - admin_noticia_ai_image_regenerate -> /admin/noticia/{id}/ai-image/regenerate
 *     - etc.
 *
 * Override: If you need custom behavior for a specific route, simply define
 * the method in your controller - it will override the trait's method.
 */
trait AiImageRoutesTrait
{
    /**
     * Render the AI image generation page.
     */
    #[Route('', name: '_page', methods: ['GET'])]
    public function page(int $id, Request $request): Response
    {
        $subject = $request->query->get('subject', '');

        return $this->doRenderPage($id, $subject);
    }

    /**
     * Generate a subject description using AI.
     */
    #[Route('/generate-subject', name: '_generate_subject', methods: ['POST'])]
    public function generateSubject(int $id): JsonResponse
    {
        return $this->doGenerateSubject($id);
    }

    /**
     * Regenerate the image using AI.
     */
    #[Route('/regenerate', name: '_regenerate', methods: ['POST'])]
    public function regenerate(int $id, Request $request): JsonResponse
    {
        return $this->doRegenerateImage($id, $request);
    }

    /**
     * Use an image from history as the featured image.
     */
    #[Route('/history/{historyId}/use', name: '_use_history', methods: ['POST'])]
    public function useHistory(int $id, int $historyId): JsonResponse
    {
        return $this->doUseHistoryImage($id, $historyId);
    }

    /**
     * Delete an image from history.
     */
    #[Route('/history/{historyId}', name: '_delete_history', methods: ['DELETE'])]
    public function deleteHistory(int $id, int $historyId): JsonResponse
    {
        return $this->doDeleteHistoryImage($id, $historyId);
    }

    /**
     * Batch delete images from history.
     *
     * Expects POST with form data: ids[] = [1, 2, 3]
     */
    #[Route('/history/batch-delete', name: '_batch_delete', methods: ['POST'])]
    public function batchDeleteHistory(int $id, Request $request): JsonResponse
    {
        return $this->doBatchDeleteHistoryImages($id, $request);
    }

    /**
     * Get current history status (count, limit, etc.).
     */
    #[Route('/history/status', name: '_history_status', methods: ['GET'])]
    public function historyStatus(int $id): JsonResponse
    {
        return $this->doGetHistoryStatus($id);
    }
}
