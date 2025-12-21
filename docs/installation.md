# Installation & Configuration

Complete guide for installing and configuring xmon/ai-content-bundle.

## Installation

```bash
composer require xmon/ai-content-bundle
```

For SonataMedia integration:
```bash
composer require sonata-project/media-bundle
```

## Environment Variables

```bash
# .env

# Text providers
XMON_AI_GEMINI_API_KEY=AIza...           # Gemini API (recommended)
XMON_AI_OPENROUTER_API_KEY=sk-or-v1-...  # OpenRouter API (optional)
XMON_AI_POLLINATIONS_API_KEY=your_key    # Optional: higher rate limits and access to more models

# Image providers (same Pollinations key works for both text and images)
# XMON_AI_POLLINATIONS_API_KEY=your_key
```

## Bundle Configuration

All providers use the same configuration schema:

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    # Text providers (priority: higher number = tried first)
    text:
        providers:
            gemini:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_GEMINI_API_KEY)%'
                model: 'gemini-2.0-flash-lite'
                fallback_models: []              # Backup models
                timeout: 30
            openrouter:
                enabled: true
                priority: 50
                api_key: '%env(XMON_AI_OPENROUTER_API_KEY)%'
                model: 'google/gemini-2.0-flash-exp:free'
                fallback_models:                 # If main model fails
                    - 'meta-llama/llama-3.3-70b-instruct:free'
                timeout: 90
            pollinations:
                enabled: true
                priority: 10
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional
                model: 'openai-fast'
                fallback_models:
                    - 'openai'
                timeout: 60
        defaults:
            retries: 2
            retry_delay: 3

    # Image providers
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

    # Only if you have SonataMedia installed
    media:
        default_context: 'default'
        provider: 'sonata.media.provider.image'
```

## Unified Provider Schema

| Field | Type | Description |
|-------|------|-------------|
| `enabled` | bool | Enable/disable the provider |
| `priority` | int | Higher number = tried first |
| `api_key` | string | API key (if required) |
| `model` | string | Main model |
| `fallback_models` | array | Backup models (optional) |
| `timeout` | int | Timeout in seconds |

## Enabling/Disabling Providers

There are several ways to control which providers are active:

### Option 1: `enabled` field (recommended)

```yaml
xmon_ai_content:
    text:
        providers:
            gemini:
                enabled: true   # Active
            openrouter:
                enabled: false  # Disabled
            pollinations:
                enabled: true
```

### Option 2: Omit the provider

If you don't need a provider, simply don't include it:

```yaml
xmon_ai_content:
    text:
        providers:
            pollinations:
                enabled: true
                model: 'openai-fast'
            # gemini and openrouter don't appear = disabled
```

### Option 3: Without API key

Providers that require an API key (gemini, openrouter) report `isAvailable(): false` if they don't have a key configured, and the system automatically skips them:

```yaml
gemini:
    enabled: true
    api_key: null  # No key → isAvailable() = false → skipped
```

### Fallback System Behavior

| Situation | Registered? | Used? |
|-----------|-------------|-------|
| `enabled: false` | No | No |
| `enabled: true` + no API key | Yes | No (fallback to next) |
| `enabled: true` + with API key | Yes | Yes (by priority) |
| Not in YAML | No | No |

The recommended method is to use `enabled: false` because it's explicit and documents the intention.

## Configuration Examples

### Pollinations only (minimal configuration)

```yaml
xmon_ai_content:
    text:
        providers:
            pollinations:
                enabled: true
                model: 'openai-fast'  # GPT-4.1 Nano (anonymous tier)
    image:
        providers:
            pollinations:
                enabled: true
```

> **Note:** Pollinations works without API key using `openai` or `openai-fast` models (anonymous tier). For access to `mistral`, `gemini`, `deepseek` models, a `seed` tier API key is required.

### Gemini as primary with fallback

```yaml
xmon_ai_content:
    text:
        providers:
            gemini:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_GEMINI_API_KEY)%'
            pollinations:
                enabled: true
                priority: 10  # Fallback if Gemini fails
```

### OpenRouter with multiple free models

```yaml
xmon_ai_content:
    text:
        providers:
            openrouter:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_OPENROUTER_API_KEY)%'
                model: 'google/gemini-2.0-flash-exp:free'
                fallback_models:
                    - 'meta-llama/llama-3.3-70b-instruct:free'
                    - 'qwen/qwen3-235b-a22b:free'
                    - 'mistralai/mistral-small-3.1-24b-instruct:free'
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

## Next Steps

- [Text Generation Guide](guides/text-generation.md)
- [Image Generation Guide](guides/image-generation.md)
- [Admin Integration Guide](guides/admin-integration.md)
- [Configuration Reference](reference/configuration.md)
