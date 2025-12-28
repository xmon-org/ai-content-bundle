# Architecture

Bundle structure and components.

> **Architecture Decision (December 2025):** The bundle uses a simplified single-provider architecture. Pollinations is the sole provider for both text and image generation, providing unified access to multiple AI models (Claude, GPT, Gemini, Mistral, Flux, etc.). Model fallback happens within the provider, not between providers.

## Directory Structure

```
xmon-org/ai-content-bundle/
├── composer.json
├── README.md
├── docs/                            # Documentation
│   ├── installation.md
│   ├── guides/
│   │   ├── text-generation.md
│   │   ├── image-generation.md
│   │   ├── styles-presets.md
│   │   ├── prompt-templates.md
│   │   └── admin-integration.md
│   └── reference/
│       ├── configuration.md
│       ├── providers.md
│       ├── fallback-system.md
│       └── architecture.md
├── config/
│   ├── services.yaml                # Core services (always)
│   ├── services_text.yaml           # Text provider (always)
│   ├── services_media.yaml          # SonataMedia (if installed)
│   └── services_admin.yaml          # Sonata Admin (if installed)
└── src/
    ├── XmonAiContentBundle.php      # Bundle class
    ├── DependencyInjection/
    │   ├── Configuration.php        # Validated YAML configuration
    │   └── XmonAiContentExtension.php # Service loading
    ├── Admin/
    │   └── AiImageAdminExtension.php # Sonata Admin extension
    ├── Controller/
    │   └── AbstractAiImageController.php # Base controller for regeneration
    ├── Entity/
    │   ├── AiImageAwareInterface.php     # Interface for entities with AI images
    │   ├── AiImageAwareTrait.php         # Trait with common methods
    │   ├── AiImageHistoryInterface.php   # Interface for image history
    │   ├── AiImageHistoryTrait.php       # Trait for history entities
    │   ├── AiStyleConfigurableInterface.php # Interface for style config
    │   └── AiStyleConfigurableTrait.php  # Trait for style configuration
    ├── Form/
    │   ├── AiTextFieldType.php      # Textarea with AI generation
    │   ├── AiImageFieldType.php     # Image with regeneration modal
    │   ├── StyleSelectorType.php    # Style mode selector
    │   └── AiStyleConfigType.php    # Style configuration form
    ├── Provider/
    │   ├── AiStyleProviderInterface.php  # Style provider interface
    │   ├── Image/
    │   │   └── PollinationsImageProvider.php  # Single image provider
    │   ├── Text/
    │   │   └── PollinationsTextProvider.php   # Single text provider
    │   └── Style/
    │       └── YamlStyleProvider.php
    ├── Service/
    │   ├── AiImageService.php       # Image orchestrator
    │   ├── AiTextService.php        # Text orchestrator
    │   ├── TaskConfigService.php    # Task-based model configuration
    │   ├── ModelRegistryService.php # Model catalog with costs
    │   ├── ImageSubjectGenerator.php # Two-step anchor extraction
    │   ├── ImageOptionsService.php  # Style/preset management
    │   ├── PromptBuilder.php        # Builds prompts with options
    │   ├── PromptTemplateService.php # Configurable prompt templates
    │   ├── VariantSelector.php      # Intelligent variant selection
    │   ├── AiStyleService.php       # Style provider aggregator
    │   └── MediaStorageService.php  # SonataMedia (conditional)
    ├── Model/
    │   ├── ImageResult.php          # Immutable DTO
    │   ├── TextResult.php           # Immutable DTO
    │   └── ModelInfo.php            # Model metadata (name, tier, cost)
    ├── Enum/
    │   ├── TaskType.php             # Task types (NEWS_CONTENT, IMAGE_PROMPT, IMAGE_GENERATION)
    │   └── ModelTier.php            # Access tiers (ANONYMOUS, SEED, FLOWER)
    ├── Command/
    │   └── DebugConfigCommand.php   # xmon:ai:debug
    ├── Resources/
    │   ├── public/
    │   │   ├── css/ai-image.css     # Admin styles
    │   │   └── js/ai-image-regenerator.js # Admin JavaScript
    │   └── views/
    │       ├── form/fields.html.twig # Form theme
    │       └── admin/form/          # Admin templates
    └── Exception/
        └── AiProviderException.php
```

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Your Application                          │
│                                                                  │
│  ┌──────────────────┐        ┌──────────────────┐               │
│  │  AiTextService   │        │  AiImageService  │               │
│  │                  │        │                  │               │
│  │  generate()      │        │  generate()      │               │
│  │  generateForTask()        │  generateForTask()               │
│  └────────┬─────────┘        └────────┬─────────┘               │
│           │                           │                          │
│           ▼                           ▼                          │
│  ┌──────────────────┐        ┌──────────────────┐               │
│  │ Pollinations     │        │ Pollinations     │               │
│  │ TextProvider     │        │ ImageProvider    │               │
│  │                  │        │                  │               │
│  │ Model fallback:  │        │ Model fallback:  │               │
│  │ gemini → mistral │        │ flux → turbo     │               │
│  │ → deepseek       │        │                  │               │
│  └────────┬─────────┘        └────────┬─────────┘               │
│           │                           │                          │
└───────────┼───────────────────────────┼──────────────────────────┘
            │                           │
            ▼                           ▼
     ┌──────────────────────────────────────────┐
     │           Pollinations.ai API            │
     │                                          │
     │  Text: /v1/chat/completions              │
     │  Image: /image/{prompt}                  │
     │                                          │
     │  Models: Claude, GPT, Gemini, Mistral,   │
     │          Flux, Turbo, GPTImage, etc.     │
     └──────────────────────────────────────────┘
```

## Components

### Main Services

| Service | Description |
|---------|-------------|
| `AiTextService` | Main entry point for text generation. Delegates to PollinationsTextProvider |
| `AiImageService` | Main entry point for image generation. Delegates to PollinationsImageProvider |
| `TaskConfigService` | Task-based model configuration and validation |
| `ModelRegistryService` | Model catalog with costs and tier information |
| `ImageSubjectGenerator` | Two-step anchor extraction for unique image subjects |
| `ImageOptionsService` | Manages styles, compositions, palettes, extras, presets |
| `PromptBuilder` | Combines subject + options into prompts |
| `PromptTemplateService` | Manages configurable prompt templates |
| `AiStyleService` | Aggregates style providers by priority |
| `MediaStorageService` | Saves images to SonataMedia (conditional) |

### Providers

The bundle uses a single-provider architecture per type:

| Provider | Class | Purpose |
|----------|-------|---------|
| Text | `PollinationsTextProvider` | Text generation via Pollinations API |
| Image | `PollinationsImageProvider` | Image generation via Pollinations API |
| Style | `YamlStyleProvider` | Provides global style from YAML config |

**Key design decision:** Providers handle model fallback internally. If `gemini` fails, the provider tries `mistral`, then `deepseek`, etc. This simplifies the architecture - no need for multiple provider implementations.

### Form Types (Sonata Admin)

| Form Type | Description |
|-----------|-------------|
| `AiTextFieldType` | Textarea with "Generate with AI" button |
| `AiImageFieldType` | Image field with regeneration modal |
| `StyleSelectorType` | Style mode selector (Global/Preset/Custom) |
| `AiStyleConfigType` | Complete style configuration form |

### Admin Components

| Component | Description |
|-----------|-------------|
| `AiImageAdminExtension` | Extension that adds CSS/JS assets to Admin |
| `AbstractAiImageController` | Base controller with regeneration logic |

### Entity Interfaces

| Interface | Description |
|-----------|-------------|
| `AiImageAwareInterface` | For entities that have AI-generated images |
| `AiImageHistoryInterface` | For image history entities |
| `AiStyleConfigurableInterface` | For entities with configurable AI style |
| `AiImageAwareTrait` | Default implementation of AiImageAwareInterface |
| `AiImageHistoryTrait` | Default implementation of AiImageHistoryInterface |
| `AiStyleConfigurableTrait` | Default implementation of style configuration |

### Models (DTOs)

Immutable data transfer objects:

**TextResult:**
- `getText()` - Generated text
- `getProvider()` - Provider used ('pollinations')
- `getModel()` - Model used (e.g., 'gemini', 'openai-fast')
- `getPromptTokens()` - Input tokens
- `getCompletionTokens()` - Output tokens
- `getFinishReason()` - Stop reason

**ImageResult:**
- `getBytes()` - Raw image data
- `getMimeType()` - Content type
- `getExtension()` - File extension
- `getProvider()` - Provider used ('pollinations')
- `getModel()` - Model used (e.g., 'flux', 'gptimage')
- `getWidth()`, `getHeight()` - Dimensions
- `toBase64()`, `toDataUri()` - Encoding helpers

**ModelInfo:**
- `name` - Human-readable model name
- `tier` - Access tier (ModelTier enum)
- `responsesPerPollen` - Approximate responses per pollen (cost metric)
- `getFormattedCost()` - Cost as readable string
- `getCostPerResponseUSD()` - Estimated cost per response in USD

### Configuration

**Configuration.php** validates YAML structure:
- Text/image provider settings (flat structure)
- Task types configuration
- Image options (styles, compositions, etc.)
- Presets
- Prompt templates

**XmonAiContentExtension.php** handles:
- Loading configuration
- Configuring providers with YAML values
- Conditional service loading (SonataMedia, Sonata Admin)

### Conditional Loading

Services are loaded conditionally based on dependencies:

```php
// In XmonAiContentExtension.php

// SonataMedia integration
if (interface_exists(MediaManagerInterface::class)) {
    $loader->load('services_media.yaml');
}

// Sonata Admin integration
if (interface_exists(AdminInterface::class)) {
    $loader->load('services_admin.yaml');
}
```

## Service Tags

| Tag | Purpose |
|-----|---------|
| `xmon_ai_content.style_provider` | Register style providers (for global style) |
| `console.command` | Register console commands |
| `form.type` | Register form types |
| `sonata.admin.extension` | Register admin extensions |
| `monolog.logger` | Logger channel 'ai' for AI operations |

## Data Flow

### Text Generation

```
1. Application calls AiTextService->generate($system, $user, $options)
   or AiTextService->generateForTask(TaskType::NEWS_CONTENT, ...)

2. AiTextService:
   - If using generateForTask(), resolves model via TaskConfigService
   - Delegates to PollinationsTextProvider->generate()

3. PollinationsTextProvider:
   - Builds list of models to try (primary + fallbacks)
   - For each model, tries up to retries_per_model times
   - Makes HTTP request to Pollinations API
   - On success: returns TextResult
   - On 4xx error: skips to next model
   - On 5xx/timeout: retries with delay

4. Returns TextResult to application
```

### Image Generation

```
1. Application calls AiImageService->generate($prompt, $options)
   or AiImageService->generateForTask($prompt, $options)

2. AiImageService:
   - If using generateForTask(), resolves model via TaskConfigService
   - Delegates to PollinationsImageProvider->generate()

3. PollinationsImageProvider:
   - Builds list of models to try (primary + fallbacks)
   - For each model, tries up to retries_per_model times
   - Builds URL with prompt and parameters
   - Makes HTTP GET request to Pollinations API
   - On success: returns ImageResult with bytes
   - On failure: retries or tries next model

4. Returns ImageResult to application
```

## Related

- [Configuration Reference](configuration.md) - Full YAML options
- [Fallback System](fallback-system.md) - How model fallback works
- [Providers Reference](providers.md) - Available models and costs
- [Admin Integration](../guides/admin-integration.md) - Sonata Admin integration
