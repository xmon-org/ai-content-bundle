# Admin Integration

Integrate AI image generation with Sonata Admin.

## Overview

This bundle provides Form Types and an Admin Extension to add AI image regeneration capabilities to your Sonata Admin panels.

## Requirements

- Sonata Admin Bundle 5.x
- Sonata Media Bundle (optional, for image storage)

## Setup

### 1. Add Form Theme

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - '@XmonAiContent/form/fields.html.twig'
```

### 2. Implement Interfaces (Optional)

For full history support, implement these interfaces:

**Entity with AI Image:**

```php
use Xmon\AiContentBundle\Entity\AiImageAwareInterface;
use Xmon\AiContentBundle\Entity\AiImageAwareTrait;

#[ORM\Entity]
class Article implements AiImageAwareInterface
{
    use AiImageAwareTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    private ?Media $featuredImage = null;

    // Implement abstract methods
    public function getId(): ?int { return $this->id; }

    public function getFeaturedImage(): ?object { return $this->featuredImage; }

    public function setFeaturedImage(?object $image): static {
        $this->featuredImage = $image;
        return $this;
    }

    public function getContentForImageGeneration(): string {
        return $this->title . ($this->summary ? "\n\n" . $this->summary : '');
    }
}
```

**Image History Entity:**

```php
use Xmon\AiContentBundle\Entity\AiImageHistoryInterface;
use Xmon\AiContentBundle\Entity\AiImageHistoryTrait;

#[ORM\Entity]
class ArticleImageHistory implements AiImageHistoryInterface
{
    use AiImageHistoryTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    private ?Article $article = null;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    private ?Media $image = null;

    public function __construct()
    {
        $this->initializeCreatedAt();
    }

    public function getId(): ?int { return $this->id; }

    public function getImage(): ?object { return $this->image; }

    public function setImage(?object $image): static {
        $this->image = $image;
        return $this;
    }

    // Getters/setters for article...
}
```

### 3. Create Controller

Extend the abstract controller:

```php
use Xmon\AiContentBundle\Controller\AbstractAiImageController;

class ArticleImageController extends AbstractAiImageController
{
    public function __construct(
        AiTextService $textService,
        AiImageService $imageService,
        ImageOptionsService $imageOptionsService,
        PromptBuilder $promptBuilder,
        PromptTemplateService $promptTemplateService,
        ?MediaStorageService $mediaStorage,
        private ArticleRepository $articleRepository,
        private ArticleImageHistoryRepository $historyRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct($textService, $imageService, $imageOptionsService, $promptBuilder, $promptTemplateService, $mediaStorage);
    }

    #[Route('/admin/article/{id}/generate-subject', name: 'admin_article_generate_subject', methods: ['POST'])]
    public function generateSubject(int $id): JsonResponse
    {
        return $this->doGenerateSubject($id);
    }

    #[Route('/admin/article/{id}/regenerate-image', name: 'admin_article_regenerate_image', methods: ['POST'])]
    public function regenerateImage(int $id, Request $request): JsonResponse
    {
        return $this->doRegenerateImage($id, $request);
    }

    // Implement abstract methods...
    protected function findEntity(int $id): ?AiImageAwareInterface
    {
        return $this->articleRepository->find($id);
    }

    protected function getMediaContext(): string
    {
        return 'articles';
    }

    // ... other abstract methods
}
```

### 4. Configure Admin

```php
use Xmon\AiContentBundle\Form\AiTextFieldType;
use Xmon\AiContentBundle\Form\AiImageFieldType;

class ArticleAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $entity = $this->getSubject();

        $form
            ->with('Content')
                ->add('title', TextType::class)
                ->add('content', TextareaType::class)
            ->end()
            ->with('Image')
                ->add('featuredImage', \Sonata\MediaBundle\Form\Type\MediaType::class, [
                    'context' => 'articles',
                    'provider' => 'sonata.media.provider.image',
                ])
                // AI regeneration widget
                ->add('aiRegeneration', AiImageFieldType::class, [
                    'label' => false,
                    'mapped' => false,
                    'ai_regenerate_endpoint' => $entity?->getId()
                        ? $this->generateObjectUrl('admin_article_regenerate_image', $entity)
                        : null,
                    'ai_generate_subject_endpoint' => $entity?->getId()
                        ? $this->generateObjectUrl('admin_article_generate_subject', $entity)
                        : null,
                    'ai_current_image_url' => $this->getCurrentImageUrl($entity),
                    'ai_subject' => $entity?->getImageSubject(),
                ])
            ->end()
            ->with('SEO')
                // AI text generation for subject
                ->add('imageSubject', AiTextFieldType::class, [
                    'label' => 'Image Subject',
                    'ai_endpoint' => $entity?->getId()
                        ? $this->generateObjectUrl('admin_article_generate_subject', $entity)
                        : null,
                    'ai_button_label' => 'Generate with AI',
                ])
            ->end();
    }
}
```

## Form Types

### AiTextFieldType

A textarea with an AI generation button.

**Options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `ai_endpoint` | string\|null | null | AJAX endpoint URL |
| `ai_button_label` | string | 'Generate with AI' | Button text |
| `ai_method` | string | 'POST' | HTTP method |
| `ai_source_fields` | array | [] | Field names to include as context |
| `ai_confirm_overwrite` | bool | true | Confirm before replacing content |

### AiImageFieldType

An image field with AI regeneration modal.

**Options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `ai_regenerate_endpoint` | string\|null | null | Image generation endpoint |
| `ai_generate_subject_endpoint` | string\|null | null | Subject generation endpoint |
| `ai_current_image_url` | string\|null | null | Current image URL |
| `ai_subject` | string | '' | Current subject |
| `ai_history` | array | [] | Image history data |
| `ai_show_style_selector` | bool | true | Show style tabs |
| `ai_show_history` | bool | true | Show image history |

### StyleSelectorType

A compound field for selecting image style.

**Options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `default_mode` | string | 'global' | Default mode: global, preset, custom |
| `show_preview` | bool | true | Show style preview |

## Admin Extension

Apply the extension to auto-include CSS/JS:

```yaml
# config/services.yaml
services:
    App\Admin\ArticleAdmin:
        calls:
            - [addExtension, ['@Xmon\AiContentBundle\Admin\AiImageAdminExtension']]
```

The extension provides helper methods:

```php
$this->getExtension(AiImageAdminExtension::class)->getPresetChoices();
$this->getExtension(AiImageAdminExtension::class)->getStyleChoices();
$this->getExtension(AiImageAdminExtension::class)->getGlobalStylePreview();
```

## CSS/JS Assets

The bundle includes CSS and JS assets in `Resources/public/`.

After installation, run:

```bash
bin/console assets:install
```

The extension auto-includes these assets when applied to an admin.

## Related

- [Styles & Presets](styles-presets.md) - Configure image styles
- [Image Generation](image-generation.md) - Generate images programmatically
