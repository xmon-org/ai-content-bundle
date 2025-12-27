# Installation & Configuration

Complete guide for installing and configuring xmon-org/ai-content-bundle.

## Installation

```bash
composer require xmon-org/ai-content-bundle
```

For SonataMedia integration:
```bash
composer require sonata-project/media-bundle
```

## Environment Variables

```bash
# .env

# Pollinations API key (optional but recommended)
# Without key: anonymous tier (rate limited, fewer models)
# With key: seed/flower tier (higher limits, premium models)
XMON_AI_POLLINATIONS_API_KEY=your_key_here
```

> **Note:** The bundle works without an API key using anonymous tier models. Get an API key from [pollinations.ai](https://pollinations.ai) for access to premium models and higher rate limits.

## Bundle Configuration

### Minimal Configuration (v1.4.0+ with TaskTypes)

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    # Task-based model configuration (recommended)
    tasks:
        news_content:
            default_model: 'openai-fast'
            allowed_models: ['openai-fast', 'openai']
        image_prompt:
            default_model: 'openai-fast'
            allowed_models: ['openai-fast']
        image_generation:
            default_model: 'flux'
            allowed_models: ['flux', 'turbo']

    text:
        providers:
            pollinations:
                enabled: true
    image:
        providers:
            pollinations:
                enabled: true
```

> **Note:** This configuration uses anonymous tier models (no API key required). For premium models, add your API key and update the model lists.

### Full Configuration

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    # Task-based model configuration (v1.4.0+)
    tasks:
        news_content:
            default_model: 'gemini'
            allowed_models: ['gemini', 'deepseek', 'mistral', 'openai']
        image_prompt:
            default_model: 'gemini-fast'
            allowed_models: ['gemini-fast', 'openai-fast', 'mistral']
        image_generation:
            default_model: 'flux'
            allowed_models: ['flux', 'turbo', 'gptimage']

    # Text provider
    text:
        providers:
            pollinations:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional
                model: 'openai-fast'
                fallback_models:
                    - 'openai'
                    - 'mistral'
                timeout: 60
        defaults:
            retries: 2
            retry_delay: 3

    # Image provider
    image:
        providers:
            pollinations:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
                model: 'flux'
                timeout: 120
        defaults:
            width: 1280
            height: 720
            retries: 3
            retry_delay: 5

    # SonataMedia integration (optional)
    media:
        default_context: 'default'
        provider: 'sonata.media.provider.image'
```

## Provider Configuration Schema

| Field | Type | Description |
|-------|------|-------------|
| `enabled` | bool | Enable/disable the provider |
| `priority` | int | Higher number = tried first |
| `api_key` | string | API key (optional for Pollinations) |
| `model` | string | Default model to use |
| `fallback_models` | array | Backup models if main fails |
| `timeout` | int | Timeout in seconds |

## Available Models

> **Note:** For complete model list with pricing, see [Providers Reference](reference/providers.md).

### Text Models (via Pollinations)

| Tier | Models | API Key Required |
|------|--------|------------------|
| Anonymous | `openai`, `openai-fast` | No |
| Seed | `gemini`, `gemini-fast`, `gemini-search`, `mistral`, `deepseek`, `grok`, `qwen-coder`, `nova-micro` | Yes |
| Flower | `claude`, `claude-fast`, `claude-large`, `openai-large`, `gemini-large`, `perplexity-fast` | Yes |

### Image Models (via Pollinations)

| Tier | Models | API Key Required |
|------|--------|------------------|
| Anonymous | `flux`, `turbo` | No |
| Seed | `nanobanana`, `zimage` | Yes |
| Flower | `gptimage`, `gptimage-large`, `seedream`, `seedream-pro`, `nanobanana-pro`, `kontext` | Yes |

## Budget-Based Configuration Examples

### Free Tier (No API Key)

```yaml
xmon_ai_content:
    tasks:
        news_content:
            default_model: 'openai-fast'
            allowed_models: ['openai-fast', 'openai']
        image_prompt:
            default_model: 'openai-fast'
            allowed_models: ['openai-fast']
        image_generation:
            default_model: 'flux'
            allowed_models: ['flux', 'turbo']
    text:
        providers:
            pollinations:
                enabled: true
                model: 'openai-fast'
    image:
        providers:
            pollinations:
                enabled: true
                model: 'flux'
```

### Low Budget (Seed Tier)

```yaml
xmon_ai_content:
    tasks:
        news_content:
            default_model: 'gemini'
            allowed_models: ['gemini', 'deepseek', 'mistral']
        image_prompt:
            default_model: 'gemini-fast'
            allowed_models: ['gemini-fast', 'mistral']
        image_generation:
            default_model: 'flux'
            allowed_models: ['flux', 'nanobanana']
    text:
        providers:
            pollinations:
                enabled: true
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
                model: 'gemini'
    image:
        providers:
            pollinations:
                enabled: true
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
                model: 'flux'
```

### Quality First (Flower Tier)

```yaml
xmon_ai_content:
    tasks:
        news_content:
            default_model: 'claude'
            allowed_models: ['claude', 'gemini-large', 'openai-large']
        image_prompt:
            default_model: 'gemini-fast'
            allowed_models: ['gemini-fast', 'openai-fast']
        image_generation:
            default_model: 'gptimage'
            allowed_models: ['gptimage', 'seedream', 'flux']
    text:
        providers:
            pollinations:
                enabled: true
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
                model: 'claude'
    image:
        providers:
            pollinations:
                enabled: true
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
                model: 'gptimage'
```

## Sonata Admin Integration (Optional)

If you're using Sonata Admin, additional setup is required:

### 1. Install Assets

```bash
# Standard
bin/console assets:install --symlink

# Docker
docker compose exec php bin/console assets:install --symlink
```

### 2. Configure Assets in Sonata Admin

```yaml
# config/packages/sonata_admin.yaml
sonata_admin:
    assets:
        extra_javascripts:
            - bundles/xmonaicontent/js/ai-image-regenerator.js
        extra_stylesheets:
            - bundles/xmonaicontent/css/ai-image.css
```

### 3. Add Form Theme

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - '@XmonAiContent/form/fields.html.twig'
```

For complete Sonata Admin integration, see [Admin Integration Guide](guides/admin-integration.md).

## Verify Installation

Run the debug command to verify your configuration:

```bash
bin/console xmon:ai:debug
```

This will show:
- Provider status (Pollinations API for text and image)
- Task models (default, cost, allowed models per task type)
- Styles, compositions, palettes, and presets
- Prompt templates

## Next Steps

- [Task Types Guide](guides/task-types.md) - Configure models per task type
- [Text Generation Guide](guides/text-generation.md)
- [Image Generation Guide](guides/image-generation.md)
- [Admin Integration Guide](guides/admin-integration.md)
- [Configuration Reference](reference/configuration.md)
