# Admin Integration

Integrate AI image generation with Sonata Admin.

## Overview

This bundle provides Form Types and an Admin Extension to add AI image regeneration capabilities to your Sonata Admin panels.

## Requirements

- Sonata Admin Bundle ^4.0
- Sonata Media Bundle ^4.0 (optional, for image storage)

## Setup

### 1. Install Assets

The bundle includes CSS and JavaScript files that must be published to your `public/bundles/` directory.

```bash
# Standard installation
bin/console assets:install --symlink
```

This creates a symlink at `public/bundles/xmonaicontent/` pointing to the bundle's assets.

### 2. Configure Sonata Admin Assets

Sonata Admin 4.x requires manual asset registration via YAML configuration:

```yaml
# config/packages/sonata_admin.yaml
sonata_admin:
    # ... other config

    assets:
        extra_javascripts:
            - bundles/xmonaicontent/js/ai-image-regenerator.js
        extra_stylesheets:
            - bundles/xmonaicontent/css/ai-image.css
```

> **Note:** There is no automatic asset loading via Admin Extensions in Sonata Admin 4.x. Assets must be configured in YAML.

### 3. Add Form Theme

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - '@XmonAiContent/form/fields.html.twig'
```

### 4. Implement Interfaces (Optional)

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

**Entity with Context Banner (Optional):**

Implement `AiImageContextInterface` to display additional entity information in the AI image generation page header:

```php
use Xmon\AiContentBundle\Entity\AiImageAwareInterface;
use Xmon\AiContentBundle\Entity\AiImageContextInterface;

#[ORM\Entity]
class Article implements AiImageAwareInterface, AiImageContextInterface
{
    // ... AiImageAwareInterface implementation ...

    /**
     * Provide context info for the AI image page header.
     */
    public function getAiImageContext(): array
    {
        return [
            'Summary' => $this->summary ? mb_substr($this->summary, 0, 100) . '...' : null,
            'Author' => $this->author,
            'Date' => $this->publishedAt?->format('d/m/Y'),
            'Status' => match($this->status) {
                'published' => '✅ Published',
                'draft' => '📝 Draft',
                default => $this->status,
            },
        ];
    }
}
```

The Context Banner displays below the page title, showing key entity information at a glance. Null or empty values are automatically filtered out.

**Tips for Context:**
- Keep labels short (1-2 words)
- Truncate long text values
- Use emoji for status indicators
- Return only the most relevant fields (3-5 max)

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

### 5. Create Database Migration

After implementing the interfaces, create a migration for the new fields:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

## Dedicated Image Generation Page (Recommended)

The most powerful feature of this bundle is the **dedicated image generation page**. It provides a full-featured interface for AI image generation with:

- Subject input with AI-powered description generation
- Style selector (global/preset/custom)
- Side-by-side image comparison (current vs new)
- Complete image history with reuse and delete actions
- Real-time generation timer

### Controller Implementation

Create a controller that extends `AbstractAiImageController` and uses `AiImageRoutesTrait`:

```php
<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\ArticleImageHistory;
use App\Repository\ArticleRepository;
use App\Repository\ArticleImageHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\MediaBundle\Provider\Pool;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Xmon\AiContentBundle\Controller\AbstractAiImageController;
use Xmon\AiContentBundle\Controller\AiImageRoutesTrait;
use Xmon\AiContentBundle\Entity\AiImageAwareInterface;
use Xmon\AiContentBundle\Entity\AiImageHistoryInterface;
use Xmon\AiContentBundle\Service\AiImageService;
use Xmon\AiContentBundle\Service\AiTextService;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\MediaStorageService;
use Xmon\AiContentBundle\Service\PromptBuilder;
use Xmon\AiContentBundle\Service\PromptTemplateService;

/**
 * All routes are provided automatically by AiImageRoutesTrait:
 * - GET  /admin/article/{id}/ai-image              -> page
 * - POST /admin/article/{id}/ai-image/generate-subject
 * - POST /admin/article/{id}/ai-image/regenerate
 * - POST /admin/article/{id}/ai-image/history/{historyId}/use
 * - DELETE /admin/article/{id}/ai-image/history/{historyId}
 * - POST /admin/article/{id}/ai-image/history/batch-delete
 * - GET  /admin/article/{id}/ai-image/history/status
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/article/{id}/ai-image', name: 'admin_article_ai_image')]
class ArticleAiImageController extends AbstractAiImageController
{
    use AiImageRoutesTrait; // All routes defined automatically!

    public function __construct(
        AiTextService $textService,
        AiImageService $imageService,
        ImageOptionsService $imageOptionsService,
        PromptBuilder $promptBuilder,
        PromptTemplateService $promptTemplateService,
        ?MediaStorageService $mediaStorage,
        private readonly ArticleRepository $articleRepository,
        private readonly ArticleImageHistoryRepository $historyRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Pool $mediaPool,
    ) {
        parent::__construct(
            $textService,
            $imageService,
            $imageOptionsService,
            $promptBuilder,
            $promptTemplateService,
            $mediaStorage
        );
    }

    // ==========================================
    // ABSTRACT METHOD IMPLEMENTATIONS
    // ==========================================

    protected function findEntity(int $id): ?AiImageAwareInterface
    {
        return $this->articleRepository->find($id);
    }

    protected function getEntityHistory(AiImageAwareInterface $entity): array
    {
        return $this->historyRepository->findBy(
            ['article' => $entity],
            ['createdAt' => 'DESC']
        );
    }

    protected function getEntityTitle(AiImageAwareInterface $entity): string
    {
        return $entity->getTitle();
    }

    protected function getBackUrl(AiImageAwareInterface $entity): string
    {
        return $this->generateUrl('admin_app_article_edit', ['id' => $entity->getId()]);
    }

    protected function getListUrl(AiImageAwareInterface $entity): string
    {
        return $this->generateUrl('admin_app_article_list');
    }

    protected function createHistoryItem(
        AiImageAwareInterface $entity,
        ?object $media,
        string $subject,
        string $style,
        string $model,
    ): ?AiImageHistoryInterface {
        $historyItem = new ArticleImageHistory();
        $historyItem->setArticle($entity);
        $historyItem->setImage($media);
        $historyItem->setSubject($subject);
        $historyItem->setStyle($style);
        $historyItem->setModel($model);

        $this->entityManager->persist($historyItem);
        $this->entityManager->flush();

        return $historyItem;
    }

    protected function findHistoryItem(int $id): ?AiImageHistoryInterface
    {
        return $this->historyRepository->find($id);
    }

    protected function historyBelongsToEntity(AiImageHistoryInterface $history, AiImageAwareInterface $entity): bool
    {
        return $history->getArticle()->getId() === $entity->getId();
    }

    protected function applyHistoryToEntity(AiImageAwareInterface $entity, AiImageHistoryInterface $history): void
    {
        $entity->setFeaturedImage($history->getImage());
        $entity->setImageSubject($history->getSubject());
        $entity->setImageStyle($history->getStyle());
        $entity->setImageModel($history->getModel());

        $this->entityManager->flush();
    }

    protected function deleteHistoryItem(AiImageHistoryInterface $history): void
    {
        $media = $history->getImage();

        $this->entityManager->remove($history);
        $this->entityManager->flush();

        if ($media) {
            $this->entityManager->remove($media);
            $this->entityManager->flush();
        }
    }

    protected function getMediaUrl(object $media): string
    {
        $provider = $this->mediaPool->getProvider($media->getProviderName());
        return $provider->generatePublicUrl($media, 'default_medium');
    }

    protected function getMediaId(object $media): int|string
    {
        return $media->getId();
    }

    protected function getMediaContext(): string
    {
        return 'articles';
    }
}
```

### AiImageRoutesTrait

The `AiImageRoutesTrait` provides all routes automatically, eliminating boilerplate. It defines:

| Method | Path | Description |
|--------|------|-------------|
| GET | `''` | Render the AI image page |
| POST | `/generate-subject` | Generate subject with AI |
| POST | `/regenerate` | Generate new image |
| POST | `/history/{historyId}/use` | Use history image |
| DELETE | `/history/{historyId}` | Delete history image |
| POST | `/history/batch-delete` | Batch delete images |
| GET | `/history/status` | Get history count/limit |

Routes are automatically named based on your controller's `#[Route]` name attribute.

> **Tip:** You can override any route by defining it in your controller - it will take precedence over the trait.

### Adding Link from Sonata Admin

Add a button in your Admin class to access the dedicated page:

```php
// In your ArticleAdmin.php

protected function configureRoutes(RouteCollectionInterface $collection): void
{
    $collection->add('ai_image', $this->getRouterIdParameter() . '/ai-image');
}

protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
{
    $buttonList = parent::configureActionButtons($buttonList, $action, $object);

    if ($action === 'edit' && $object !== null) {
        $buttonList['ai_image'] = [
            'template' => 'admin/button_ai_image.html.twig',
        ];
    }

    return $buttonList;
}
```

Create the button template:

```twig
{# templates/admin/button_ai_image.html.twig #}
<a class="btn btn-info" href="{{ path('admin_article_ai_image_page', {id: object.id}) }}">
    <i class="fa fa-magic"></i> AI Image
</a>
```

### Custom Template

You can override the default template by extending `getTemplate()` in your controller:

```php
protected function getTemplate(): string
{
    return 'admin/my_custom_ai_image_page.html.twig';
}
```

Your custom template can extend the bundle's template and override blocks:

```twig
{# templates/admin/my_custom_ai_image_page.html.twig #}
{% extends '@XmonAiContent/admin/ai_image_page.html.twig' %}

{% block header_title %}Generate Image for Article{% endblock %}
{% block entity_label %}Article{% endblock %}
{% block generate_subject_label %}Generate description from article content{% endblock %}
```

Available blocks to override:
- `page_title` - Browser tab title
- `header_title` - Main heading
- `entity_label` - Label before entity title
- `controls_title` - Controls panel title
- `subject_label` - Subject field label
- `subject_placeholder` - Subject textarea placeholder
- `subject_help` - Help text below subject
- `style_label` - Style section label
- `generate_subject_label` - AI subject generation button
- `generate_image_label` - Main action button
- `xmon_ai_image_styles` - CSS block
- `xmon_ai_image_scripts` - JavaScript block

## Global Style Configuration with AiStyleConfigType

The bundle provides `AiStyleConfigType` for configuring AI image styles globally (e.g., in a Settings or Configuration entity). This is the recommended approach for admin-editable style configuration.

### The Trait-FormType Contract

The `AiStyleConfigType` works together with `AiStyleConfigurableTrait`. They form a **contract**:

```
┌─────────────────────────────────────┐
│ Your Entity (e.g., Configuracion)   │
│                                     │
│  uses AiStyleConfigurableTrait ─────┼──► Defines fields:
│                                     │    - aiStyleMode (string)
│                                     │    - aiStylePreset (?string)
│                                     │    - aiStyleArtistic (?string)
│                                     │    - aiStyleComposition (?string)
│                                     │    - aiStylePalette (?string)
│                                     │    - aiStyleAdditional (?string)
└─────────────────────────────────────┘
                ↓ inherit_data=true
┌─────────────────────────────────────┐
│ AiStyleConfigType (FormType)        │
│                                     │
│  Adds form fields with exact ───────┼──► Same names as trait:
│  same names                         │    - aiStyleMode
│                                     │    - aiStylePreset
│                                     │    - aiStyleArtistic
│                                     │    - aiStyleComposition
│                                     │    - aiStylePalette
│                                     │    - aiStyleAdditional
└─────────────────────────────────────┘
```

Because `inherit_data => true` is the default, Symfony maps the form fields directly to the entity's properties. **The field names MUST match** - this is the implicit contract.

### Step 1: Use the Trait in Your Entity

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Xmon\AiContentBundle\Entity\AiStyleConfigurableInterface;
use Xmon\AiContentBundle\Entity\AiStyleConfigurableTrait;
use Xmon\AiContentBundle\Service\ImageOptionsService;

#[ORM\Entity]
class Configuracion implements AiStyleConfigurableInterface
{
    use AiStyleConfigurableTrait;  // <-- This adds the 6 fields

    /**
     * Build the complete style from configuration.
     *
     * @param ImageOptionsService $imageOptions Service to get presets
     * @param string|null $defaultPresetKey Default preset from bundle config
     */
    public function getBaseStylePreview(
        ImageOptionsService $imageOptions,
        ?string $defaultPresetKey = null
    ): string {
        return $this->buildStylePreview(
            presets: $imageOptions->getPresetsForForm(),
            suffix: 'no faces, no text',
            defaultPresetKey: $defaultPresetKey,
        );
    }
}
```

### Step 2: Create Database Migration

After adding the trait, create and run a migration:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

The migration will add columns: `ai_style_mode`, `ai_style_preset`, `ai_style_artistic`, `ai_style_composition`, `ai_style_palette`, `ai_style_additional`.

### Step 3: Use in Sonata Admin

```php
use Xmon\AiContentBundle\Form\AiStyleConfigType;

class ConfiguracionAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('AI')
                ->with('Image Generation Style')
                    ->add('aiStyleConfig', AiStyleConfigType::class, [
                        // Required: your data arrays
                        'presets' => Configuracion::PRESETS,
                        'styles' => Configuracion::STYLES,
                        'compositions' => Configuracion::COMPOSITIONS,
                        'palettes' => Configuracion::PALETTES,

                        // Preview (included automatically)
                        'show_preview' => true,
                        'preview_label' => 'Style Preview',
                        'suffix' => 'no faces, no text',  // Fixed suffix
                        'default_artistic' => 'watercolor style',
                        'default_composition' => 'balanced composition',
                        'default_palette' => 'natural colors',

                        // Labels (customize for your language)
                        'mode_label' => 'Configuration Mode',
                        'preset_label' => 'Preset',
                        // ... more label options
                    ])
                ->end()
            ->end();
    }
}
```

### AiStyleConfigType Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| **Data Options** |
| `presets` | array | `[]` | Preset configurations `['key' => ['name' => '...', 'style' => '...', 'composition' => '...', 'palette' => '...']]` |
| `styles` | array | `[]` | Artistic styles (can be grouped) |
| `compositions` | array | `[]` | Composition options (can be grouped) |
| `palettes` | array | `[]` | Color palette options (can be grouped) |
| **Preview Options** |
| `show_preview` | bool | `true` | Show dynamic style preview |
| `preview_label` | string | `'Style Preview'` | Label for preview section |
| `suffix` | string | `''` | Fixed text appended to all styles |
| `default_artistic` | ?string | `null` | Default artistic style when empty |
| `default_composition` | ?string | `null` | Default composition when empty |
| `default_palette` | ?string | `null` | Default palette when empty |
| **Label Options** |
| `mode_label` | string | `'Configuration Mode'` | Mode field label |
| `preset_mode_label` | string | `'Use Preset'` | Preset mode radio label |
| `custom_mode_label` | string | `'Custom Configuration'` | Custom mode radio label |
| `preset_label` | string | `'Preset'` | Preset select label |
| `artistic_label` | string | `'Artistic Style'` | Artistic select label |
| `composition_label` | string | `'Composition'` | Composition select label |
| `palette_label` | string | `'Color Palette'` | Palette select label |
| `additional_label` | string | `'Additional Text'` | Additional textarea label |
| **Placeholder Options** |
| `preset_placeholder` | string | `'Select a preset...'` | Preset select placeholder |
| `artistic_placeholder` | string | `'Select a style...'` | Artistic select placeholder |
| `composition_placeholder` | string | `'Select a composition...'` | Composition placeholder |
| `palette_placeholder` | string | `'Select a palette...'` | Palette placeholder |
| `additional_placeholder` | string | `'Additional instructions...'` | Additional textarea placeholder |
| **Help Text Options** |
| `mode_help` | ?string | `null` | Help text for mode field |
| `preset_help` | ?string | `null` | Help text for preset field |
| `additional_help` | ?string | `null` | Help text for additional field |

### Dynamic Preview

The FormType includes JavaScript that provides real-time preview:

- **Preset mode**: Shows the combined style from the selected preset
- **Custom mode**: Shows the combined individual fields
- **Both modes**: Adds the fixed suffix and additional text

The preview updates automatically when any field changes - no page reload needed.

### Using the Style in Image Generation

Implement a Style Provider to use the database configuration for image generation:

```php
<?php

namespace App\Provider;

use App\Repository\ConfiguracionRepository;
use Xmon\AiContentBundle\Provider\AiStyleProviderInterface;

final class ConfiguracionStyleProvider implements AiStyleProviderInterface
{
    public function __construct(
        private readonly ConfiguracionRepository $configuracionRepository,
    ) {}

    public function getGlobalStyle(): string
    {
        $config = $this->configuracionRepository->getConfiguracion();

        if ($config === null) {
            return '';
        }

        // getBaseStylePreview() uses buildStylePreview() from the trait
        return $config->getBaseStylePreview();
    }

    public function getPriority(): int
    {
        return 100;  // Higher than YAML default (0)
    }

    public function isAvailable(): bool
    {
        return $this->configuracionRepository->getConfiguracion() !== null;
    }
}
```

Register as tagged service:

```yaml
# config/services.yaml
services:
    App\Provider\ConfiguracionStyleProvider:
        tags:
            - { name: 'xmon_ai_content.style_provider', priority: 100 }
```

See [Style Providers](style-providers.md) for more details.

## Form Types (For In-Form Integration)

For simpler use cases, you can use Form Types directly in Sonata Admin forms instead of the dedicated page.

### 6. Configure Admin

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

The Admin Extension provides helper methods for accessing style configuration. Apply it via YAML:

```yaml
# config/packages/sonata_admin.yaml
sonata_admin:
    extensions:
        Xmon\AiContentBundle\Admin\AiImageAdminExtension:
            admins:
                - admin.article  # Your admin service ID
```

The extension provides helper methods in your Admin class:

```php
// Get available presets for dropdown
$presets = $this->getExtension(AiImageAdminExtension::class)->getPresetChoices();

// Get style choices
$styles = $this->getExtension(AiImageAdminExtension::class)->getStyleChoices();

// Get global style preview text
$preview = $this->getExtension(AiImageAdminExtension::class)->getGlobalStylePreview();
```

## Customizing Controller Behavior

The `AbstractAiImageController` provides several protected methods that can be overridden to customize behavior. These methods follow a consistent pattern: read from your application's configuration and fall back to the bundle's default.

### Override Global Style

By default, the bundle uses the first preset defined in YAML configuration as the "global" style. You can override this to read from a database entity:

```php
class MyEntityAiImageController extends AbstractAiImageController
{
    public function __construct(
        // ... other dependencies
        private readonly ConfigurationRepository $configRepository,
    ) {
        parent::__construct(/* ... */);
    }

    /**
     * Get global style from database configuration.
     */
    protected function resolveGlobalStyle(): string
    {
        $config = $this->configRepository->getConfiguration();

        if ($config !== null) {
            // Return the complete style prompt from your entity
            return $config->getBaseStylePrompt();
        }

        // Fallback to bundle's default (first preset)
        return parent::resolveGlobalStyle();
    }
}
```

This allows you to:
- Store style configuration in a database entity (e.g., Configuracion)
- Let admin users configure the default style via Sonata Admin
- Use different styles per entity type or context
- Combine preset/custom modes managed through your application

**Example with preset/custom mode:**

```php
protected function resolveGlobalStyle(): string
{
    $config = $this->configRepository->getConfiguration();

    if ($config === null) {
        return parent::resolveGlobalStyle();
    }

    // Check if using preset mode
    if ($config->getStyleMode() === 'preset' && $config->getPresetKey()) {
        return $this->promptBuilder->buildFromPreset($config->getPresetKey());
    }

    // Custom mode: build from individual components
    return $this->promptBuilder->buildStyleOnly([
        'style' => $config->getStyle(),
        'composition' => $config->getComposition(),
        'palette' => $config->getPalette(),
        'custom_prompt' => $config->getCustomPrompt(),
    ]);
}
```

### Override Maximum Images

The `AbstractAiImageController` provides a method to customize the maximum number of images in history:

```php
class MyEntityAiImageController extends AbstractAiImageController
{
    public function __construct(
        // ... other dependencies
        private readonly ConfigurationRepository $configRepository,
    ) {
        parent::__construct(/* ... */);
    }

    protected function getMaxHistoryImages(): int
    {
        // Read from database, session, or any source
        $config = $this->configRepository->getConfiguration();
        return $config?->getMaxHistoryImages() ?? parent::getMaxHistoryImages();
    }
}
```

This allows you to:
- Read the limit from a database entity (e.g., Configuracion)
- Set different limits per entity type
- Allow admin users to configure the limit dynamically

### Configuration Priority

The history limit follows this priority (highest to lowest):

1. **Controller override** - `getMaxHistoryImages()` method
2. **YAML configuration** - `xmon_ai_content.history.max_images`
3. **Bundle default** - 5 images

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    history:
        max_images: 10  # Override bundle default
```

### History Limit Modal

When the history limit is reached, the bundle automatically shows a management modal before allowing new image generation. This provides a user-friendly way to manage history:

**Features:**
- Lists all images in history with thumbnails
- Checkboxes to select images for deletion
- Current image is locked (cannot be deleted)
- Counter showing selected images
- "Delete and Generate" button to remove selected and proceed

**Behavior:**
1. User clicks "Generate image"
2. If `history.count >= limit`, modal appears
3. User selects which images to delete
4. Clicking "Delete and Generate" removes selected images
5. New image is generated automatically after deletion

This approach ensures users have full control over which images to keep, rather than automatic FIFO deletion.

## Troubleshooting

### Assets not loading

1. **Check symlink exists:**
   ```bash
   ls -la public/bundles/xmonaicontent/
   ```

2. **If symlink points to wrong path** (e.g., host path instead of container path):
   ```bash
   # Run inside Docker container
   docker compose exec php bin/console assets:install --symlink
   ```

3. **Verify YAML configuration:**
   ```bash
   bin/console debug:config sonata_admin assets
   ```

### Form theme not applied

Check that `@XmonAiContent/form/fields.html.twig` is in your `twig.form_themes` configuration.

## Related

- [Styles & Presets](styles-presets.md) - Configure image styles in YAML
- [Style Providers](style-providers.md) - Database-backed style configuration
- [Image Generation](image-generation.md) - Generate images programmatically
