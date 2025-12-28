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

### Minimal Configuration (Zero Config)

The bundle works **out of the box** with free-tier models:

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content: ~  # That's it! Uses free-tier defaults
```

**Default models (no API key required):**
- **Text:** `mistral` → `nova-micro` → `gemini-fast` → `openai-fast`
- **Image:** `flux` → `zimage` → `turbo`

> **Note:** All default models work without an API key. Get a free API key from [enter.pollinations.ai](https://enter.pollinations.ai) for premium models.

### Full Configuration

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    # Task-based model configuration
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

    # Text generation (Pollinations API)
    text:
        api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
        model: 'gemini'
        fallback_models: ['mistral', 'deepseek']
        retries_per_model: 2
        retry_delay: 3
        timeout: 60
        endpoint_mode: 'openai'

    # Image generation (Pollinations API)
    image:
        api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
        model: 'flux'
        fallback_models: ['turbo']
        retries_per_model: 2
        retry_delay: 3
        timeout: 120
        width: 1280
        height: 720
        quality: 'high'
        negative_prompt: 'worst quality, blurry, text, letters, watermark'
        private: true
        nofeed: true

    # SonataMedia integration (optional)
    media:
        default_context: 'default'
        provider: 'sonata.media.provider.image'
```

## Configuration Schema

### Text Configuration

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `api_key` | string | `null` | API key (optional - all defaults are free) |
| `model` | string | `'mistral'` | Default model (free tier) |
| `fallback_models` | array | `['nova-micro', 'gemini-fast', 'openai-fast']` | Backup models (free tier) |
| `retries_per_model` | int | `2` | Retries before next model |
| `retry_delay` | int | `3` | Seconds between retries |
| `timeout` | int | `60` | HTTP timeout |
| `endpoint_mode` | string | `'openai'` | `'openai'` or `'simple'` |

### Image Configuration

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `api_key` | string | `null` | API key (optional - all defaults are free) |
| `model` | string | `'flux'` | Default model (free tier) |
| `fallback_models` | array | `['zimage', 'turbo']` | Backup models (free tier) |
| `retries_per_model` | int | `2` | Retries before next model |
| `retry_delay` | int | `3` | Seconds between retries |
| `timeout` | int | `120` | HTTP timeout |
| `width` | int | `1280` | Default width |
| `height` | int | `720` | Default height |
| `quality` | string | `'high'` | `low`, `medium`, `high`, `hd` |
| `negative_prompt` | string | *(see below)* | What to avoid |
| `private` | bool | `true` | Hide from public feeds |
| `nofeed` | bool | `true` | Don't add to feed |

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

### Free Tier (No API Key) - Default Configuration

```yaml
# This is the default! Just use:
xmon_ai_content: ~

# Which is equivalent to:
xmon_ai_content:
    tasks:
        news_content:
            default_model: 'mistral'
            allowed_models: ['mistral', 'nova-micro', 'openai-fast']
        image_prompt:
            default_model: 'mistral'
            allowed_models: ['mistral', 'openai-fast', 'nova-micro']
        image_generation:
            default_model: 'flux'
            allowed_models: ['flux', 'zimage', 'turbo']

    text:
        model: 'mistral'
        fallback_models: ['nova-micro', 'gemini-fast', 'openai-fast']

    image:
        model: 'flux'
        fallback_models: ['zimage', 'turbo']
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
        api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
        model: 'gemini'
        fallback_models: ['mistral', 'deepseek']

    image:
        api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
        model: 'flux'
        fallback_models: ['turbo']
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
        api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
        model: 'claude'
        fallback_models: ['gemini']

    image:
        api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'
        model: 'gptimage'
        fallback_models: ['flux']
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
