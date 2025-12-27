# xmon-org/ai-content-bundle

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-purple.svg?style=flat-square&logo=symfony)](https://symfony.com)
[![Total Downloads](https://img.shields.io/packagist/dt/xmon-org/ai-content-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/ai-content-bundle)
[![License](https://img.shields.io/packagist/l/xmon-org/ai-content-bundle.svg?style=flat-square)](https://github.com/xmon-org/ai-content-bundle/blob/main/LICENSE)

[![CI](https://github.com/xmon-org/ai-content-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/xmon-org/ai-content-bundle/actions/workflows/ci.yml)
[![semantic-release](https://img.shields.io/badge/semantic--release-conventionalcommits-e10079?logo=semantic-release)](https://github.com/semantic-release/semantic-release)

---

<div align="center">

**Powered by [Pollinations.ai](https://pollinations.ai)** - Unified access to Claude, GPT, Gemini, Mistral, Flux, and more.

</div>

---

Symfony 7 bundle for AI-powered content generation. Access multiple LLMs and image models through a single, consistent API powered by [Pollinations](https://pollinations.ai).

## Why Pollinations?

This bundle uses **Pollinations.ai** as its AI gateway, providing:

- **Unified API** - One integration for Claude, GPT, Gemini, Mistral, DeepSeek, and more
- **No vendor lock-in** - Switch models with a config change, no code modifications
- **Generous free tier** - Start generating content without API keys
- **Transparent pricing** - Pay only for what you use with premium models
- **Open ecosystem** - Built on open-source principles

## Features

- **Text generation** with multiple models (Claude, Gemini, GPT, Mistral, DeepSeek)
- **Image generation** with multiple models (GPTImage, Flux, Seedream, Turbo)
- **Task Types** - Configure different models for different tasks (content, prompts, images)
- **Cost tracking** - See estimated costs per model in the UI
- **Style presets** for consistent image generation
- **Configurable prompt templates** with intelligent variant selection
- **Automatic fallback** between models when one fails
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
            default_model: 'gemini'
            allowed_models: ['gemini', 'mistral', 'openai']
        image_prompt:
            default_model: 'gemini-fast'
            allowed_models: ['gemini-fast', 'openai-fast']
        image_generation:
            default_model: 'flux'
            allowed_models: ['flux', 'gptimage', 'turbo']

    text:
        providers:
            pollinations:
                enabled: true
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional for anonymous tier
    image:
        providers:
            pollinations:
                enabled: true
```

> **Tip:** Works without API key for `openai`, `openai-fast`, `flux`, and `turbo` models. Get a free API key at [auth.pollinations.ai](https://auth.pollinations.ai) for access to premium models.

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
- [Prompt Templates](docs/guides/prompt-templates.md) - Configurable prompts with variants
- [Admin Integration](docs/guides/admin-integration.md) - Sonata Admin integration

### Reference
- [Configuration](docs/reference/configuration.md) - Full YAML reference
- [Providers](docs/reference/providers.md) - Available AI models and costs
- [Fallback System](docs/reference/fallback-system.md) - How automatic fallback works
- [Architecture](docs/reference/architecture.md) - Bundle structure

### Development
- [Development Guide](docs/development.md) - Local setup and commands

## Available Models

All models are accessed through [Pollinations.ai](https://pollinations.ai). Query current models and pricing:

```bash
# Text models with Pollen pricing
curl https://gen.pollinations.ai/text/models

# Image models with Pollen pricing
curl https://gen.pollinations.ai/image/models
```

> **API Documentation:** [enter.pollinations.ai/api/docs](https://enter.pollinations.ai/api/docs)
>
> **Note:** Pricing is shown in Pollen credits. See [auth.pollinations.ai](https://auth.pollinations.ai) for current rates.

### Text Models

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

### Image Models

| Model | Tier | ~Img/$ | Description |
|-------|------|--------|-------------|
| `flux` | anonymous | 8,333 | Fast & high quality (free) |
| `turbo` | anonymous | 3,333 | Ultra-fast previews (free) |
| `nanobanana` | seed | 33,333 | Gemini-based, reference images |
| `gptimage` | flower | 125,000 | OpenAI, best prompt understanding |
| `seedream` | flower | 33 | ByteDance ARK, complex scenes |

### Access Tiers

| Tier | Requirements | Access |
|------|--------------|--------|
| `anonymous` | None | `openai`, `openai-fast`, `flux`, `turbo` |
| `seed` | Free API key from [auth.pollinations.ai](https://auth.pollinations.ai) | + `gemini*`, `deepseek`, `mistral`, `nanobanana` |
| `flower` | Pollen credits | All models including `claude`, `gptimage`, `seedream` |

## Debug Command

```bash
bin/console xmon:ai:debug
```

Shows configured providers, styles, presets, and prompt templates.

## Acknowledgments

This bundle is built on top of **[Pollinations.ai](https://pollinations.ai)**, an open-source platform that provides unified access to state-of-the-art AI models. Their commitment to open AI and developer-friendly APIs makes projects like this possible.

- Website: [pollinations.ai](https://pollinations.ai)
- API Docs: [enter.pollinations.ai/api/docs](https://enter.pollinations.ai/api/docs)
- GitHub: [github.com/pollinations](https://github.com/pollinations)

## License

MIT
