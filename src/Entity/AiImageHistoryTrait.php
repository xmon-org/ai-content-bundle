<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Entity;

/**
 * Trait implementing common AiImageHistoryInterface methods.
 *
 * Use this trait in your entity to get default implementations
 * of the subject, style, model, and createdAt properties.
 *
 * You still need to:
 * - Define $id property with ORM mapping
 * - Define $image property with your media type
 * - Implement getImage()/setImage() for your specific media type
 * - Define the parent entity relation (e.g., $article, $product)
 *
 * Example:
 *
 *     #[ORM\Entity]
 *     class ArticleImageHistory implements AiImageHistoryInterface
 *     {
 *         use AiImageHistoryTrait;
 *
 *         #[ORM\Id, ORM\GeneratedValue, ORM\Column]
 *         private ?int $id = null;
 *
 *         #[ORM\ManyToOne(targetEntity: Article::class)]
 *         private ?Article $article = null;
 *
 *         #[ORM\ManyToOne(targetEntity: Media::class)]
 *         private ?Media $image = null;
 *
 *         public function getImage(): ?object { return $this->image; }
 *         public function setImage(?object $image): static {
 *             $this->image = $image;
 *             return $this;
 *         }
 *     }
 */
trait AiImageHistoryTrait
{
    /**
     * Subject of the image: WHAT to generate.
     */
    protected ?string $subject = null;

    /**
     * Style of the image: HOW to generate.
     */
    protected ?string $style = null;

    /**
     * AI model used for generation.
     */
    protected ?string $model = null;

    /**
     * Creation timestamp.
     */
    protected ?\DateTimeInterface $createdAt = null;

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getStyle(): ?string
    {
        return $this->style;
    }

    public function setStyle(?string $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the full prompt (subject + style combined).
     */
    public function getFullPrompt(): ?string
    {
        if (!$this->subject) {
            return null;
        }

        if (!$this->style) {
            return $this->subject;
        }

        return $this->subject . ', ' . $this->style;
    }

    /**
     * Initialize createdAt on construction.
     * Call this in your entity's constructor.
     */
    protected function initializeCreatedAt(): void
    {
        $this->createdAt = new \DateTime();
    }
}
