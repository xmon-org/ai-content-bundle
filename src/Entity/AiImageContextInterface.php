<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Entity;

/**
 * Optional interface for entities that want to expose context information
 * in the AI image generation page.
 *
 * This is OPTIONAL - entities implementing only AiImageAwareInterface will continue
 * to work with the default behavior (showing only the title).
 *
 * When implemented, the bundle will display a Context Banner with additional
 * information about the entity being edited, improving user orientation.
 *
 * Example implementation for a News entity:
 *
 *     class Noticia implements AiImageAwareInterface, AiImageContextInterface
 *     {
 *         public function getAiImageContext(): array
 *         {
 *             return [
 *                 'Resumen' => $this->resumen ? substr($this->resumen, 0, 100) . '...' : null,
 *                 'Autor' => $this->autor,
 *                 'Fecha' => $this->fechaPublicacion?->format('d/m/Y'),
 *                 'Estado' => match($this->status) {
 *                     'published' => '✅ Publicada',
 *                     'draft' => '📝 Borrador',
 *                     default => $this->status,
 *                 },
 *             ];
 *         }
 *     }
 *
 * Example implementation for a Product entity:
 *
 *     class Product implements AiImageAwareInterface, AiImageContextInterface
 *     {
 *         public function getAiImageContext(): array
 *         {
 *             return [
 *                 'SKU' => $this->sku,
 *                 'Category' => $this->category?->getName(),
 *                 'Price' => number_format($this->price, 2) . ' €',
 *                 'Stock' => $this->stock > 0 ? '✅ In stock' : '❌ Out of stock',
 *             ];
 *         }
 *     }
 */
interface AiImageContextInterface
{
    /**
     * Get context information to display in the AI image generation page.
     *
     * Returns an associative array where:
     * - Key: Label to display (e.g., "Summary", "Author", "Status")
     * - Value: The value to show (string, int, null)
     *
     * Null or empty values will be filtered out automatically in the template.
     *
     * Tips:
     * - Keep labels short (1-2 words)
     * - Truncate long text values (e.g., summaries)
     * - Use emoji for status indicators for quick visual recognition
     * - Return only the most relevant fields (3-5 max)
     *
     * @return array<string, string|int|null>
     */
    public function getAiImageContext(): array;
}
