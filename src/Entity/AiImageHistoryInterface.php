<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Entity;

/**
 * Interface for AI-generated image history entities.
 *
 * Implement this interface in your entity to enable image history features
 * with the AiImageFieldType and regeneration UI.
 *
 * Example implementation:
 *
 *     #[ORM\Entity]
 *     class ArticleImageHistory implements AiImageHistoryInterface
 *     {
 *         #[ORM\ManyToOne(targetEntity: Article::class)]
 *         private ?Article $article = null;
 *
 *         #[ORM\ManyToOne(targetEntity: Media::class)]
 *         private ?Media $image = null;
 *
 *         // ... implement all interface methods
 *     }
 */
interface AiImageHistoryInterface
{
    /**
     * Get the unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the subject (WHAT to generate).
     * Example: "A serene Japanese dojo with morning light"
     */
    public function getSubject(): ?string;

    /**
     * Set the subject.
     */
    public function setSubject(?string $subject): static;

    /**
     * Get the style (HOW to generate).
     * Example: "sumi-e style, monochrome, atmospheric"
     */
    public function getStyle(): ?string;

    /**
     * Set the style.
     */
    public function setStyle(?string $style): static;

    /**
     * Get the AI model used for generation.
     * Example: "flux", "dall-e-3"
     */
    public function getModel(): ?string;

    /**
     * Set the AI model.
     */
    public function setModel(?string $model): static;

    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Set the creation timestamp.
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): static;

    /**
     * Get the generated image.
     *
     * The return type is `object` to allow flexibility with different
     * media storage implementations (SonataMedia, VichUploader, custom).
     */
    public function getImage(): ?object;

    /**
     * Set the generated image.
     */
    public function setImage(?object $image): static;

    /**
     * Get the full prompt (subject + style combined).
     * Useful for display purposes.
     */
    public function getFullPrompt(): ?string;
}
