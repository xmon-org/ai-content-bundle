<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Entity;

/**
 * Trait implementing common AiImageAwareInterface methods.
 *
 * Use this trait in your entity to get default implementations
 * of the imageSubject, imageStyle, and imageModel properties.
 *
 * You still need to:
 * - Define $id property with ORM mapping
 * - Define $featuredImage property with your media type
 * - Implement getFeaturedImage()/setFeaturedImage() for your specific media type
 * - Implement getContentForImageGeneration() for your entity
 *
 * Example:
 *
 *     #[ORM\Entity]
 *     class Article implements AiImageAwareInterface
 *     {
 *         use AiImageAwareTrait;
 *
 *         #[ORM\Id, ORM\GeneratedValue, ORM\Column]
 *         private ?int $id = null;
 *
 *         #[ORM\Column(length: 255)]
 *         private string $title = '';
 *
 *         #[ORM\Column(type: Types::TEXT, nullable: true)]
 *         private ?string $summary = null;
 *
 *         #[ORM\ManyToOne(targetEntity: Media::class)]
 *         private ?Media $featuredImage = null;
 *
 *         public function getFeaturedImage(): ?object { return $this->featuredImage; }
 *         public function setFeaturedImage(?object $image): static {
 *             $this->featuredImage = $image;
 *             return $this;
 *         }
 *
 *         public function getContentForImageGeneration(): string {
 *             return $this->title . ($this->summary ? "\n\n" . $this->summary : '');
 *         }
 *     }
 */
trait AiImageAwareTrait
{
    /**
     * Subject used to generate the current image.
     */
    protected ?string $imageSubject = null;

    /**
     * Style used to generate the current image.
     */
    protected ?string $imageStyle = null;

    /**
     * AI model used to generate the current image.
     */
    protected ?string $imageModel = null;

    public function getImageSubject(): ?string
    {
        return $this->imageSubject;
    }

    public function setImageSubject(?string $subject): static
    {
        $this->imageSubject = $subject;

        return $this;
    }

    public function getImageStyle(): ?string
    {
        return $this->imageStyle;
    }

    public function setImageStyle(?string $style): static
    {
        $this->imageStyle = $style;

        return $this;
    }

    public function getImageModel(): ?string
    {
        return $this->imageModel;
    }

    public function setImageModel(?string $model): static
    {
        $this->imageModel = $model;

        return $this;
    }
}
