<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Entity;

/**
 * Interface for entities that have AI-generated images.
 *
 * Implement this interface in entities that can have AI-generated
 * featured images (articles, products, pages, etc.).
 *
 * Example implementation:
 *
 *     #[ORM\Entity]
 *     class Article implements AiImageAwareInterface
 *     {
 *         #[ORM\Column(type: Types::TEXT, nullable: true)]
 *         private ?string $imageSubject = null;
 *
 *         #[ORM\Column(type: Types::TEXT, nullable: true)]
 *         private ?string $imageStyle = null;
 *
 *         #[ORM\Column(length: 100, nullable: true)]
 *         private ?string $imageModel = null;
 *
 *         #[ORM\ManyToOne(targetEntity: Media::class)]
 *         private ?Media $featuredImage = null;
 *
 *         // ... implement all interface methods
 *     }
 */
interface AiImageAwareInterface
{
    /**
     * Get the unique identifier.
     */
    public function getId(): ?int;

    /**
     * Get the subject used to generate the current image.
     */
    public function getImageSubject(): ?string;

    /**
     * Set the image subject.
     */
    public function setImageSubject(?string $subject): static;

    /**
     * Get the style used to generate the current image.
     */
    public function getImageStyle(): ?string;

    /**
     * Set the image style.
     */
    public function setImageStyle(?string $style): static;

    /**
     * Get the AI model used to generate the current image.
     */
    public function getImageModel(): ?string;

    /**
     * Set the AI model.
     */
    public function setImageModel(?string $model): static;

    /**
     * Get the featured/main image.
     *
     * The return type is `object` to allow flexibility with different
     * media storage implementations (SonataMedia, VichUploader, custom).
     */
    public function getFeaturedImage(): ?object;

    /**
     * Set the featured/main image.
     */
    public function setFeaturedImage(?object $image): static;

    /**
     * Get content for generating the image subject with AI.
     * Usually returns title or title + summary.
     */
    public function getContentForImageGeneration(): string;
}
