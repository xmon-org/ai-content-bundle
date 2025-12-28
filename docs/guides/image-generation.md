# Image Generation Guide

How to generate images using Pollinations AI.

## Basic Usage

```php
use Xmon\AiContentBundle\Service\AiImageService;

class MyController
{
    public function __construct(
        private readonly AiImageService $aiImageService,
    ) {}

    public function generateImage(): Response
    {
        $result = $this->aiImageService->generate(
            prompt: 'A serene Japanese dojo with morning light',
            options: [
                'width' => 1280,
                'height' => 720,
            ]
        );

        // $result is an ImageResult with:
        // - getBytes(): raw image data
        // - getMimeType(): 'image/png', 'image/jpeg', etc.
        // - getProvider(): 'pollinations'
        // - getWidth(), getHeight()
        // - toBase64(), toDataUri()

        return new Response($result->getBytes(), 200, [
            'Content-Type' => $result->getMimeType(),
        ]);
    }
}
```

## Generation Options

```php
$result = $this->aiImageService->generate('prompt here', [
    'width' => 1280,           // Width in pixels
    'height' => 720,           // Height in pixels
    'model' => 'flux',         // AI model (overrides all defaults)
    'seed' => 12345,           // Seed for reproducibility
    'nologo' => true,          // No watermark (requires API key)
    'enhance' => false,        // AI enhances the prompt automatically
    'use_fallback' => true,    // Try fallback_models if primary fails
    'timeout' => 180,          // Custom timeout for this request
]);
```

### Model Selection

When you pass a `model` option, it takes **highest priority** and overrides all other configurations. If no model is specified, the bundle follows the [model selection hierarchy](../reference/configuration.md#model-selection-priority):

1. Database configuration (via `AiStyleConfigurableTrait`)
2. YAML configuration (`tasks.image_generation.default_model`)
3. Bundle default (`'flux'`)

**Using generateForTask()** for task-aware generation:

```php
// Uses model from hierarchy automatically
$result = $this->aiImageService->generateForTask('prompt here');

// Override with specific model
$result = $this->aiImageService->generateForTask('prompt here', [
    'model' => 'gptimage',  // Use this model instead of default
]);
```

### Available Options

| Option | Type | Description |
|--------|------|-------------|
| `width` | int | Image width in pixels |
| `height` | int | Image height in pixels |
| `model` | string | AI model to use |
| `seed` | int | Seed for reproducible results |
| `nologo` | bool | Remove watermark (requires API key) |
| `enhance` | bool | Let AI enhance the prompt |
| `use_fallback` | bool | Try fallback models if primary fails |
| `timeout` | int | Request timeout in seconds |
| `retries_per_model` | int | Retries before trying next model |
| `retry_delay` | int | Seconds between retries |

## ImageResult Object

The `generate()` method returns an `ImageResult` object:

```php
$result = $this->aiImageService->generate($prompt);

$result->getBytes();        // Raw image data
$result->getMimeType();     // 'image/png', 'image/jpeg', etc.
$result->getExtension();    // 'png', 'jpeg', etc.
$result->getProvider();     // Provider that generated the image
$result->getWidth();        // Image width
$result->getHeight();       // Image height
$result->toBase64();        // Base64 encoded string
$result->toDataUri();       // Data URI for HTML embedding
```

## Save to SonataMedia

> Requires `sonata-project/media-bundle` installed

```php
use Xmon\AiContentBundle\Service\AiImageService;
use Xmon\AiContentBundle\Service\MediaStorageService;

class MyService
{
    public function __construct(
        private readonly AiImageService $aiImageService,
        private readonly MediaStorageService $mediaStorageService,
    ) {}

    public function generateForNoticia(): MediaInterface
    {
        $result = $this->aiImageService->generate('A serene dojo');

        // Save to the target entity's context
        return $this->mediaStorageService->save(
            imageResult: $result,
            filename: 'noticia-ai-image',   // Optional (auto-generates)
            context: 'noticias',            // SonataMedia context of the entity
        );
    }

    public function generateForEvento(): MediaInterface
    {
        $result = $this->aiImageService->generate('Aikido seminar');

        // Each entity can use its own context
        return $this->mediaStorageService->save(
            imageResult: $result,
            context: 'eventos',
        );
    }
}
```

**Note about contexts**: Use the same context as the target entity so that image formats (thumbnails, etc.) are consistent. The `default_context` in configuration is only a fallback.

## Save Manually (without SonataMedia)

```php
$result = $this->aiImageService->generate('A serene dojo');

// Save to disk
file_put_contents('image.' . $result->getExtension(), $result->getBytes());

// Or send to S3, another service, etc.
$s3->putObject([
    'Body' => $result->getBytes(),
    'ContentType' => $result->getMimeType(),
]);
```

## Using Styles and Presets

For more control over the generated image style, use the [PromptBuilder](styles-presets.md):

```php
use Xmon\AiContentBundle\Service\PromptBuilder;
use Xmon\AiContentBundle\Service\AiImageService;

class MyService
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder,
        private readonly AiImageService $aiImageService,
    ) {}

    public function generateImage(): ImageResult
    {
        $prompt = $this->promptBuilder->build(
            subject: 'aikidoka meditating in a traditional dojo',
            options: ['preset' => 'sumi-e-clasico']
        );

        return $this->aiImageService->generate($prompt);
    }
}
```

## Error Handling

```php
use Xmon\AiContentBundle\Exception\AiProviderException;

try {
    $result = $this->aiImageService->generate($prompt);
} catch (AiProviderException $e) {
    // Provider error (all models failed, timeout, rate limit, etc.)
    $provider = $e->getProvider();        // 'pollinations'
    $statusCode = $e->getHttpStatusCode(); // 429, 500, etc.
    $message = $e->getMessage();           // Error details

    // Handle specific cases
    if ($statusCode === 429) {
        // Rate limited - wait and retry later
    }
}
```

## Image History

When generating images via the [Admin Integration](admin-integration.md), the bundle automatically maintains a history of generated images per entity.

### Configuration

The maximum number of images to keep in history can be configured:

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    history:
        max_images: 10  # Default: 5, Range: 1-50
```

### History Limit Management

When the history limit is reached, the bundle shows a **management modal** before allowing new image generation:

1. User attempts to generate a new image
2. Modal appears listing all history images with checkboxes
3. User selects which images to delete
4. "Delete and Generate" removes selected images and proceeds with generation

This approach gives users full control over which images to keep, rather than automatic deletion.

> **Note:** The current featured image is always locked and cannot be deleted from the modal.

### Dynamic Limit from Database

To configure the limit dynamically (e.g., from a Sonata Admin entity), override the `getMaxHistoryImages()` method in your controller:

```php
protected function getMaxHistoryImages(): int
{
    $config = $this->configRepository->getConfiguration();
    return $config?->getMaxHistoryImages() ?? parent::getMaxHistoryImages();
}
```

This allows you to:
- Read the limit from a database entity
- Set different limits per entity type
- Allow admin users to configure the limit dynamically

## Related

- [Image Subject Generator](image-subject-generator.md) - Generate unique subjects with anchor extraction
- [Styles & Presets](styles-presets.md) - Control image styles with presets
- [Style Providers](style-providers.md) - Database-backed style configuration
- [Providers Reference](../reference/providers.md) - Available image models and costs
- [Fallback System](../reference/fallback-system.md) - How automatic fallback works
- [Admin Integration](admin-integration.md) - Sonata Admin integration with history
