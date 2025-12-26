# Providers Reference

Available AI providers in xmon-org/ai-content-bundle.

> **Architecture Decision (December 2025):** The bundle uses Pollinations as the sole provider for both text and image generation. Pollinations provides access to multiple AI models (Claude, Gemini, GPT, Mistral, etc.) through a unified API, simplifying configuration and eliminating the need for multiple API keys.

## Text Provider

| Provider | Status | Requires API Key | Notes |
|----------|--------|------------------|-------|
| Pollinations | Implemented | Optional | Access to Claude, Gemini, GPT, Mistral via unified API |

### Pollinations

Unified AI API that provides access to multiple models through a single endpoint.

```yaml
pollinations:
    enabled: true
    priority: 10
    api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional for basic use, required for premium models
    model: 'openai'  # Default model
    timeout: 60
```

**Available Text Models:**

| Model Key | Model Name | ~Responses/Pollen | Best For |
|-----------|------------|-------------------|----------|
| `claude` | Claude Sonnet 4.5 | 330 | High-quality content (NEWS_CONTENT) |
| `gemini` | Gemini 3 Flash | 1,600 | General content |
| `openai` | GPT-5 Mini | 8,000 | General purpose |
| `gemini-fast` | Gemini 2.5 Flash Lite | 12,000 | Fast prompts (IMAGE_PROMPT) |
| `openai-fast` | GPT-5 Nano | 11,000 | Quick operations |
| `mistral` | Mistral Small | 13,000 | Backup/fallback |

> **Pricing:** 1 pollen ≈ $1 USD. Cost per response = 1 / responses_per_pollen

**Model Selection:** Models are selected per TaskType. See [Task Types Guide](../guides/task-types.md).

**Get API Key:** [Pollinations Dashboard](https://pollinations.ai/)

## Image Provider

| Provider | Status | Requires API Key | Notes |
|----------|--------|------------------|-------|
| Pollinations | Implemented | Optional | Multiple models including free options |

### Pollinations (Images)

Image generation API with multiple model options.

```yaml
pollinations:
    enabled: true
    priority: 100
    api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional for free models
    model: 'flux'  # Default free model
    timeout: 120
```

**Available Image Models:**

| Model Key | Model Name | ~Images/Pollen | Notes |
|-----------|------------|----------------|-------|
| `gptimage` | OpenAI Image 1 Mini | 160 | Best for complex scenes (recommended for aikido) |
| `seedream` | ByteDance ARK 2K | 35 | High quality |
| `nanobanana` | Gemini Image | 25 | With reference images |
| `flux` | Flux (free) | 8,300 | Good default, free tier |
| `turbo` | Turbo (free) | 3,300 | Fast previews, free tier |

**Options:**
- `width`, `height` - Image dimensions
- `seed` - For reproducible results
- `nologo` - Remove watermark (requires API key)
- `enhance` - AI enhances prompt automatically

**Model Selection:** Models are selected per TaskType. See [Task Types Guide](../guides/task-types.md).

## Cost Estimation

With default premium configuration (1 pollen = $1 USD):

| Task | Model | Cost |
|------|-------|------|
| News content | claude | ~$0.003/article |
| Image prompt | gemini-fast | ~$0.00008/prompt |
| Image generation | gptimage | ~$0.006/image |
| **Total per article** | | **~$0.01** |

> 100 complete articles (with images) ≈ $1 USD

## Custom Providers

You can create custom providers. See [Custom Providers Guide](../guides/custom-providers.md).

## Related

- [Task Types Guide](../guides/task-types.md) - Configure models per task
- [Configuration](configuration.md) - Full YAML reference
- [Custom Providers](../guides/custom-providers.md) - Create your own
