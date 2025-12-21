# Providers Reference

Available AI providers in xmon/ai-content-bundle.

## Text Providers

| Provider | Status | Requires API Key | Default Priority | Notes |
|----------|--------|------------------|------------------|-------|
| Gemini | Implemented | Yes | 100 | Recommended, fast and free with limits |
| OpenRouter | Implemented | Yes | 50 | Multiple models, internal fallback |
| Pollinations | Implemented | Optional | 10 | Always available, supports fallback_models |

### Gemini

Google's Gemini API. Fast responses, generous free tier.

```yaml
gemini:
    enabled: true
    priority: 100
    api_key: '%env(XMON_AI_GEMINI_API_KEY)%'
    model: 'gemini-2.0-flash-lite'
    timeout: 30
```

**Models:**
- `gemini-2.0-flash-lite` - Fastest, lightest
- `gemini-2.0-flash` - Balanced
- `gemini-1.5-pro` - Most capable

**Get API Key:** [Google AI Studio](https://aistudio.google.com/apikey)

### OpenRouter

API aggregator with access to multiple models including free options.

```yaml
openrouter:
    enabled: true
    priority: 50
    api_key: '%env(XMON_AI_OPENROUTER_API_KEY)%'
    model: 'google/gemini-2.0-flash-exp:free'
    fallback_models:
        - 'meta-llama/llama-3.3-70b-instruct:free'
        - 'qwen/qwen3-235b-a22b:free'
    timeout: 90
```

**Free Models:**
- `google/gemini-2.0-flash-exp:free`
- `meta-llama/llama-3.3-70b-instruct:free`
- `qwen/qwen3-235b-a22b:free`
- `mistralai/mistral-small-3.1-24b-instruct:free`

**Get API Key:** [OpenRouter](https://openrouter.ai/keys)

### Pollinations

Free AI API. Works without authentication but supports API key for higher rate limits.

```yaml
pollinations:
    enabled: true
    priority: 10
    api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional, for higher rate limits
    model: 'openai-fast'  # GPT-4.1 Nano
    fallback_models:
        - 'openai'  # GPT-5 Nano
    timeout: 60
```

**Models by Tier:**

| Tier | Models | Access |
|------|--------|--------|
| `anonymous` | `openai`, `openai-fast` | No registration required |
| `seed` | `mistral`, `gemini`, `deepseek`, `gemini-search` | Free registration |
| `flower` | `qwen-coder` | Paid tier |

**Recommended for anonymous tier:**
- `openai-fast` - GPT-4.1 Nano (faster, fewer content filter issues)
- `openai` - GPT-5 Nano (may have aggressive content filters)

> **Note:** Models like `mistral` and `gemini` require a `seed` tier API key.

**Available Models:** [Pollinations Models Endpoint](https://text.pollinations.ai/models)

**Get API Key:** [Pollinations Dashboard](https://pollinations.ai/) - Optional, for higher rate limits and access to more models

## Image Providers

| Provider | Status | Requires API Key | Notes |
|----------|--------|------------------|-------|
| Pollinations | Implemented | Optional | Without key = rate limits |

### Pollinations (Images)

Free image generation API using Flux model.

```yaml
pollinations:
    enabled: true
    priority: 100
    api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional
    model: 'flux'
    timeout: 120
```

**Options:**
- `width`, `height` - Image dimensions
- `seed` - For reproducible results
- `nologo` - Remove watermark (requires API key)
- `enhance` - AI enhances prompt automatically

## Provider Priority

The fallback system uses priority to determine order:

```
Higher priority → Tried first
Lower priority  → Fallback options
```

Default order for text:
1. Gemini (100)
2. OpenRouter (50)
3. Pollinations (10)

## Custom Providers

You can create custom providers. See [Custom Providers Guide](../guides/custom-providers.md).

## Related

- [Fallback System](fallback-system.md) - How automatic fallback works
- [Custom Providers](../guides/custom-providers.md) - Create your own
- [Configuration](configuration.md) - Full YAML reference
