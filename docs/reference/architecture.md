# Architecture

Bundle structure and components.

## Directory Structure

```
xmon/ai-content-bundle/
├── composer.json
├── README.md
├── docs/                            # Documentation
│   ├── installation.md
│   ├── guides/
│   │   ├── text-generation.md
│   │   ├── image-generation.md
│   │   ├── styles-presets.md
│   │   ├── prompt-templates.md
│   │   └── custom-providers.md
│   ├── reference/
│   │   ├── configuration.md
│   │   ├── providers.md
│   │   ├── fallback-system.md
│   │   └── architecture.md
│   └── development.md
├── config/
│   ├── services.yaml                # Core image services (always)
│   ├── services_text.yaml           # Text providers (always)
│   └── services_media.yaml          # SonataMedia (if installed)
└── src/
    ├── XmonAiContentBundle.php      # Bundle class
    ├── DependencyInjection/
    │   ├── Configuration.php        # Validated YAML configuration
    │   └── XmonAiContentExtension.php # Conditional service loading
    ├── Provider/
    │   ├── ImageProviderInterface.php
    │   ├── TextProviderInterface.php # With #[AutoconfigureTag]
    │   ├── Image/
    │   │   └── PollinationsImageProvider.php
    │   └── Text/
    │       ├── GeminiTextProvider.php
    │       ├── OpenRouterTextProvider.php
    │       └── PollinationsTextProvider.php
    ├── Service/
    │   ├── AiImageService.php       # Image orchestrator with fallback
    │   ├── AiTextService.php        # Text orchestrator with fallback
    │   ├── ImageOptionsService.php  # Style/preset management
    │   ├── PromptBuilder.php        # Builds prompts with options
    │   ├── PromptTemplateService.php # Configurable prompt templates
    │   └── MediaStorageService.php  # SonataMedia (conditional)
    ├── Model/
    │   ├── ImageResult.php          # Immutable DTO
    │   └── TextResult.php           # Immutable DTO
    ├── Command/
    │   └── DebugConfigCommand.php   # xmon:ai:debug
    └── Exception/
        ├── AiProviderException.php
        └── AllProvidersFailedException.php
```

## Components

### Services

| Service | Description |
|---------|-------------|
| `AiTextService` | Main service for text generation with fallback |
| `AiImageService` | Main service for image generation with fallback |
| `ImageOptionsService` | Manages styles, compositions, palettes, extras, presets |
| `PromptBuilder` | Combines subject + options into prompts |
| `PromptTemplateService` | Manages configurable prompt templates |
| `MediaStorageService` | Saves images to SonataMedia (conditional) |

### Providers

Providers implement `TextProviderInterface` or `ImageProviderInterface`:

```php
interface TextProviderInterface
{
    public function getName(): string;
    public function isAvailable(): bool;
    public function getPriority(): int;
    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult;
}
```

Tagged with `xmon_ai_content.text_provider` for auto-discovery.

### Models (DTOs)

Immutable data transfer objects:

**TextResult:**
- `getText()` - Generated text
- `getProvider()` - Provider used
- `getModel()` - Model used
- `getPromptTokens()` - Input tokens
- `getCompletionTokens()` - Output tokens
- `getFinishReason()` - Stop reason

**ImageResult:**
- `getBytes()` - Raw image data
- `getMimeType()` - Content type
- `getExtension()` - File extension
- `getProvider()` - Provider used
- `getWidth()`, `getHeight()` - Dimensions
- `toBase64()`, `toDataUri()` - Encoding helpers

### Configuration

**Configuration.php** validates YAML structure:
- Text/image provider settings
- Image options (styles, compositions, etc.)
- Presets
- Prompt templates

**XmonAiContentExtension.php** handles:
- Loading configuration
- Registering providers
- Conditional service loading (SonataMedia)
- Merging defaults with user config

### Conditional Loading

Services are loaded conditionally based on dependencies:

```php
// In XmonAiContentExtension.php
if (interface_exists(MediaInterface::class)) {
    $loader->load('services_media.yaml');
}
```

## Service Tags

| Tag | Purpose |
|-----|---------|
| `xmon_ai_content.text_provider` | Register text providers |
| `xmon_ai_content.image_provider` | Register image providers |
| `console.command` | Register console commands |

## Related

- [Configuration Reference](configuration.md) - Full YAML options
- [Custom Providers](../guides/custom-providers.md) - Extend the bundle
