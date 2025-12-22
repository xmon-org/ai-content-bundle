# xmon-org/ai-content-bundle

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-purple.svg?style=flat-square&logo=symfony)](https://symfony.com)
[![Total Downloads](https://img.shields.io/packagist/dt/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![License](https://img.shields.io/packagist/l/xmon-org/ai-content-bundle.svg?style=flat-square)](LICENSE)

[![CI](https://github.com/xmon-org/ai-content-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/xmon-org/ai-content-bundle/actions/workflows/ci.yml)
[![semantic-release](https://img.shields.io/badge/semantic--release-conventionalcommits-e10079?logo=semantic-release)](https://github.com/semantic-release/semantic-release)

Symfony 7 bundle for AI content generation (text and images) with automatic fallback between providers.

## Features

- **Text generation** with Gemini, OpenRouter, Pollinations
- **Image generation** with Pollinations (Flux model)
- **Automatic fallback** between providers
- **Style presets** for consistent image generation
- **Configurable prompt templates** for text generation
- **Prompt variants** with intelligent content-based selection
- **SonataMedia integration** (optional)
- **Sonata Admin integration** with image regeneration UI (optional)

## Requirements

- PHP >= 8.2
- Symfony >= 7.0
- symfony/http-client

## Quick Start

### 1. Installation

```bash
composer require xmon-org/ai-content-bundle
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
- [Style Providers](docs/guides/style-providers.md) - Database-backed style configuration
- [Prompt Templates](docs/guides/prompt-templates.md) - Configurable prompts with variants
- [Custom Providers](docs/guides/custom-providers.md) - Add your own providers
- [Admin Integration](docs/guides/admin-integration.md) - Sonata Admin integration

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
| Pollinations | Optional | Always available, higher rate limits with key |

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
- [x] Phase 6: Admin regeneration UI (Form Types, Controller, Templates)
- [ ] Phase 7: Aikido project migration
- [ ] Phase 8: Packagist publication

## License

MIT
