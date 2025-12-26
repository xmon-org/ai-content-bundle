# xmon-org/ai-content-bundle

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-purple.svg?style=flat-square&logo=symfony)](https://symfony.com)
[![Total Downloads](https://img.shields.io/packagist/dt/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![License](https://img.shields.io/packagist/l/xmon-org/ai-content-bundle.svg?style=flat-square)](https://github.com/xmon-org/ai-content-bundle/blob/main/LICENSE)


[![CI](https://github.com/xmon-org/ai-content-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/xmon-org/ai-content-bundle/actions/workflows/ci.yml)
[![semantic-release](https://img.shields.io/badge/semantic--release-conventionalcommits-e10079?logo=semantic-release)](https://github.com/semantic-release/semantic-release)

Symfony 7 bundle for AI content generation (text and images) with automatic fallback between providers.

## Features

- **Text generation** with multiple models (Claude, Gemini, GPT, Mistral) via Pollinations API
- **Image generation** with multiple models (GPTImage, Flux, Seedream, Turbo)
- **Task Types** - Configure different models for different tasks (content, prompts, images)
- **Cost tracking** - See estimated costs per model in the UI
- **Style presets** for consistent image generation
- **Configurable prompt templates** with intelligent variant selection
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
    # Configure models per task type
    tasks:
        news_content:
            default_model: 'claude'
            allowed_models: ['claude', 'gemini', 'openai']
        image_prompt:
            default_model: 'gemini-fast'
            allowed_models: ['gemini-fast', 'openai-fast']
        image_generation:
            default_model: 'gptimage'
            allowed_models: ['flux', 'gptimage', 'turbo']

    text:
        providers:
            pollinations:
                enabled: true
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional for basic use
    image:
        providers:
            pollinations:
                enabled: true
```

### 3. Generate Text

```php
use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Service\AiTextService;

class MyService
{
    public function __construct(
        private readonly AiTextService $aiTextService,
    ) {}

    public function summarize(string $content): string
    {
        // Uses the model configured for NEWS_CONTENT task
        $result = $this->aiTextService->generateForTask(
            TaskType::NEWS_CONTENT,
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
        // Uses the model configured for IMAGE_GENERATION task
        $result = $this->aiImageService->generateForTask(
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
- [Task Types](docs/guides/task-types.md) - Configure models per task
- [Text Generation](docs/guides/text-generation.md) - Generate text with AI
- [Image Generation](docs/guides/image-generation.md) - Generate images with AI
- [Styles & Presets](docs/guides/styles-presets.md) - Control image styles
- [Style Providers](docs/guides/style-providers.md) - Database-backed style configuration
- [Prompt Templates](docs/guides/prompt-templates.md) - Configurable prompts with variants
- [Custom Providers](docs/guides/custom-providers.md) - Add your own providers
- [Admin Integration](docs/guides/admin-integration.md) - Sonata Admin integration

### Reference
- [Configuration](docs/reference/configuration.md) - Full YAML reference
- [Providers](docs/reference/providers.md) - Available AI models and costs
- [Fallback System](docs/reference/fallback-system.md) - How automatic fallback works
- [Architecture](docs/reference/architecture.md) - Bundle structure

### Development
- [Development Guide](docs/development.md) - Local setup and commands

## Available Models

Query available models with pricing from Pollinations API:
```bash
# All models with pricing
curl -H "Authorization: Bearer YOUR_API_KEY" https://gen.pollinations.ai/models

# Image models with pricing
curl -H "Authorization: Bearer YOUR_API_KEY" https://gen.pollinations.ai/image/models
```

### Text Models (via Pollinations)

| Model | Tier | ~Resp/$ | Description |
|-------|------|---------|-------------|
| `openai-fast` | anonymous | 2,272 | GPT-5 Nano - Ultra fast |
| `openai` | anonymous | 1,666 | GPT-5 Mini - Balanced |
| `gemini-fast` | seed | 2,500 | Gemini 2.5 Flash Lite - Fast & cheap |
| `gemini` | seed | 333 | Gemini 3 Flash - Pro-grade |
| `gemini-search` | seed | 333 | Gemini 3 Flash with Search |
| `deepseek` | seed | 595 | DeepSeek V3.2 - Reasoning |
| `mistral` | seed | 2,857 | Mistral Small 3.2 - Efficient |
| `claude` | flower | 66 | Claude Sonnet 4.5 - Premium |
| `gptimage` | flower | - | GPT Image 1 Mini - Premium |

### Image Models (via Pollinations)

| Model | Tier | ~Img/$ | Description |
|-------|------|--------|-------------|
| `flux` | anonymous | 8,333 | Fast & high quality (free) |
| `turbo` | anonymous | 3,333 | Ultra-fast previews (free) |
| `nanobanana` | seed | 33,333 | Gemini-based, reference images |
| `gptimage` | flower | 125,000 | OpenAI, best prompt understanding |
| `seedream` | flower | 33 | ByteDance ARK, complex scenes |

### Tiers

| Tier | Requirements | Access |
|------|--------------|--------|
| `anonymous` | None | `openai`, `openai-fast`, `flux`, `turbo` |
| `seed` | API key from [auth.pollinations.ai](https://auth.pollinations.ai) | + `gemini*`, `deepseek`, `mistral`, `nanobanana` |
| `flower` | Premium account | All models (pollen credits) |

## Debug Command

```bash
bin/console xmon:ai:debug
```

Shows configured providers, styles, presets, and prompt templates.

## License

MIT
