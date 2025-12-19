# xmon/ai-content-bundle

Symfony 7 bundle for AI content generation (text and images) with automatic fallback between providers.

## Features

- **Text generation** with Gemini, OpenRouter, Pollinations
- **Image generation** with Pollinations (Flux model)
- **Automatic fallback** between providers
- **Style presets** for consistent image generation
- **Configurable prompt templates** for text generation
- **SonataMedia integration** (optional)

## Requirements

- PHP >= 8.2
- Symfony >= 7.0
- symfony/http-client

## Quick Start

### 1. Installation

```bash
composer require xmon/ai-content-bundle
```

### 2. Configuration

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    text:
        providers:
            pollinations:
                enabled: true
    image:
        providers:
            pollinations:
                enabled: true
```

### 3. Generate Text

```php
use Xmon\AiContentBundle\Service\AiTextService;

class MyService
{
    public function __construct(
        private readonly AiTextService $aiTextService,
    ) {}

    public function summarize(string $content): string
    {
        $result = $this->aiTextService->generate(
            systemPrompt: 'You are a helpful assistant.',
            userMessage: "Summarize: {$content}",
        );

        return $result->getText();
    }
}
```

### 4. Generate Image

```php
use Xmon\AiContentBundle\Service\AiImageService;

class MyService
{
    public function __construct(
        private readonly AiImageService $aiImageService,
    ) {}

    public function generateImage(): void
    {
        $result = $this->aiImageService->generate(
            prompt: 'A serene Japanese dojo with morning light',
        );

        file_put_contents('image.png', $result->getBytes());
    }
}
```

## Documentation

### Installation & Setup
- [Installation Guide](docs/installation.md) - Full setup instructions

### Guides
- [Text Generation](docs/guides/text-generation.md) - Generate text with AI
- [Image Generation](docs/guides/image-generation.md) - Generate images with AI
- [Styles & Presets](docs/guides/styles-presets.md) - Control image styles
- [Prompt Templates](docs/guides/prompt-templates.md) - Configurable prompts
- [Custom Providers](docs/guides/custom-providers.md) - Add your own providers

### Reference
- [Configuration](docs/reference/configuration.md) - Full YAML reference
- [Providers](docs/reference/providers.md) - Available AI providers
- [Fallback System](docs/reference/fallback-system.md) - How automatic fallback works
- [Architecture](docs/reference/architecture.md) - Bundle structure

### Development
- [Development Guide](docs/development.md) - Local setup and commands

## Available Providers

### Text

| Provider | Requires API Key | Notes |
|----------|------------------|-------|
| Gemini | Yes | Recommended, fast |
| OpenRouter | Yes | Multiple models |
| Pollinations | No | Always available |

### Image

| Provider | Requires API Key | Notes |
|----------|------------------|-------|
| Pollinations | Optional | Flux model |

## Debug Command

```bash
bin/console xmon:ai:debug
```

Shows configured providers, styles, presets, and prompt templates.

## Roadmap

- [x] Phase 1: Base structure + Pollinations
- [x] Phase 2: SonataMedia integration
- [x] Phase 3: Text providers (Gemini, OpenRouter, Pollinations)
- [x] Phase 4: Styles/presets system (ImageOptionsService, PromptBuilder)
- [x] Phase 5: Configurable prompts (PromptTemplateService)
- [ ] Phase 6: Admin regeneration UI
- [ ] Phase 7: Aikido project migration
- [ ] Phase 8: Packagist publication

## License

MIT
