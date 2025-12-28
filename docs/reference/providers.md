# Providers Reference

Available AI models and pricing in xmon-org/ai-content-bundle.

> **Architecture Decision (December 2025):** The bundle uses Pollinations as the sole provider for both text and image generation. Pollinations provides access to multiple AI models (Claude, Gemini, GPT, Mistral, etc.) through a unified API.

## Query Available Models

Get the current list of models with pricing directly from Pollinations:

```bash
# Text models with Pollen pricing
curl https://gen.pollinations.ai/text/models

# Image models with Pollen pricing
curl https://gen.pollinations.ai/image/models
```

> **API Documentation:** [enter.pollinations.ai/api/docs](https://enter.pollinations.ai/api/docs)

## Access Tiers

Models are available based on your account tier:

| Tier | Requirements | Access |
|------|--------------|--------|
| `anonymous` | None | Basic models (`openai`, `openai-fast`, `flux`, `turbo`) |
| `seed` | API key from [auth.pollinations.ai](https://auth.pollinations.ai) | + `gemini*`, `deepseek`, `mistral`, `nanobanana` |
| `flower` | Premium account | All models (pollen credits) |

## Text Provider

```yaml
text:
    providers:
        pollinations:
            enabled: true
            priority: 10
            api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Required for seed/flower tier
            timeout: 60
            endpoint_mode: 'openai'  # 'openai' (default) or 'simple'
```

### Endpoint Modes

The text provider supports two endpoint modes:

| Mode | Endpoint | Pros | Cons |
|------|----------|------|------|
| `openai` | `POST /v1/chat/completions` | No URL limit, token tracking, OpenAI-compatible | More parsing overhead |
| `simple` | `GET /text/{prompt}` | Simpler, less overhead | URL limit (~2000 chars), no token info |

**Recommendation:** Use `openai` (default) for production. Use `simple` only for debugging or very short prompts.

### Available Text Models

> **Prices updated:** 2025-12-27 | Approximate estimates for reference. Query API endpoints for current pricing.

| Model | Tier | ~Resp/$ | Description |
|-------|------|---------|-------------|
| `openai-fast` | anonymous | ~2,270 | GPT-5 Nano - Ultra fast, no reasoning |
| `openai` | anonymous | ~1,660 | GPT-5 Mini - Fast & balanced |
| `nova-micro` | seed | ~7,140 | Amazon Nova Micro - Ultra cheap |
| `gemini-fast` | seed | ~2,500 | Gemini 2.5 Flash Lite - Fast prompts |
| `mistral` | seed | ~2,850 | Mistral Small 3.2 - Efficient |
| `grok` | seed | ~2,000 | xAI Grok 4 Fast - Real-time |
| `deepseek` | seed | ~595 | DeepSeek V3.2 - Reasoning |
| `qwen-coder` | seed | ~1,110 | Qwen 2.5 Coder - Code generation |
| `gemini` | seed | ~330 | Gemini 3 Flash - Pro-grade |
| `gemini-search` | seed | ~330 | Gemini 3 Flash with Search |
| `claude-fast` | flower | ~200 | Claude Haiku 4.5 - Fast premium |
| `perplexity-fast` | flower | ~1,000 | Perplexity Sonar - Web search |
| `claude` | flower | ~65 | Claude Sonnet 4.5 - High quality |
| `openai-large` | flower | ~70 | GPT-5.2 - Most powerful |
| `gemini-large` | flower | ~85 | Gemini 3 Pro - 1M context |
| `claude-large` | flower | ~40 | Claude Opus 4.5 - Most intelligent |

> **Pricing formula:** 1 pollen ≈ $1 USD. ~Resp/$ = approximate responses per dollar (assuming ~1K tokens/response).

## Image Provider

```yaml
image:
    providers:
        pollinations:
            enabled: true
            priority: 100
            api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Required for premium models
            timeout: 120
            quality: 'high'              # low, medium, high, hd
            negative_prompt: 'worst quality, blurry, text, letters, watermark, human faces'
            private: true                # Hide from Pollinations public feeds
            nofeed: true                 # Do not add to public feed
```

### Image Provider Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable provider |
| `api_key` | string | `null` | Required for premium models |
| `timeout` | int | `120` | Request timeout in seconds |
| `quality` | enum | `'high'` | Image quality: `low`, `medium`, `high`, `hd` |
| `negative_prompt` | string | `'worst quality...'` | What to avoid in generated images |
| `private` | bool | `true` | Hide images from Pollinations public feeds |
| `nofeed` | bool | `true` | Do not add images to public feed |

#### Quality Levels

| Level | Description | Use Case |
|-------|-------------|----------|
| `low` | Fast, lower quality | Quick previews |
| `medium` | Balanced (API default) | General use |
| `high` | Higher quality, slower | Production images (recommended) |
| `hd` | Maximum quality, slowest | High-resolution outputs |

#### Negative Prompt

The `negative_prompt` parameter tells the AI what to avoid in the image. Common values to exclude:

```yaml
# Recommended for news/editorial images
negative_prompt: 'worst quality, blurry, text, letters, watermark, human faces, detailed faces, aggressive poses'

# For artistic styles
negative_prompt: 'worst quality, blurry, text, watermark, photorealistic'

# Minimal filtering
negative_prompt: 'worst quality, blurry'
```

### Available Image Models

> **Prices updated:** 2025-12-27 | Approximate estimates for reference. Query API endpoints for current pricing.

| Model | Tier | ~Img/$ | Description |
|-------|------|--------|-------------|
| `flux` | anonymous | ~8,333 | Fast & high quality |
| `turbo` | anonymous | ~3,333 | Ultra-fast previews |
| `zimage` | seed | ~5,000 | Z-Image-Turbo (alpha) |
| `nanobanana` | seed | ~33,333 | Gemini 2.5 Flash, reference images |
| `nanobanana-pro` | flower | ~8,333 | Gemini 3 Pro, 4K |
| `gptimage` | flower | ~125,000 | OpenAI, best prompt understanding |
| `gptimage-large` | flower | ~31,250 | OpenAI 1.5, advanced |
| `seedream` | flower | ~33 | ByteDance ARK, complex scenes |
| `seedream-pro` | flower | ~25 | ByteDance 4K, multi-image |
| `kontext` | flower | ~25 | Context-aware generation |

#### Runtime Options

These can be passed at runtime via the `generate()` method:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `width` | int | `1280` | Image width in pixels |
| `height` | int | `720` | Image height in pixels |
| `seed` | int | random | For reproducible results (same seed = same image) |
| `nologo` | bool | `true` if API key | Remove Pollinations watermark |
| `enhance` | bool | `false` | AI enhances/expands your prompt |
| `safe` | bool | `false` | Enable content safety filters |

### Content Moderation (gptimage)

> **Warning:** The `gptimage` model uses Azure OpenAI as backend, which has strict content moderation.

**Will be rejected (`moderation_blocked`):**
- Names of real people (artists, photographers, celebrities)
- References to copyrighted characters or brands
- Violent, sexual, or harmful content

**Alternatives when blocked:**
- Use `flux` model (no strict moderation)
- Describe the style without naming the artist
  - ❌ `"Hiroshi Sugimoto photography style"`
  - ✅ `"minimalist long exposure photography, serene ethereal atmosphere"`

### Rate Limits

| Tier | Image | Text |
|------|-------|------|
| Anonymous | 1 req / 15s | 1 req / 15s |
| Seed | 1 req / 5s | 1 req / 3s |
| Flower | 1 req / 3s | 1 req / 3s |
| Nectar | No limit | No limit |

## Cost Estimation

> **Estimates as of:** 2025-12-27 | These are rough estimates. Query API endpoints for current pricing.

With seed tier (gemini + flux):

| Task | Model | Cost |
|------|-------|------|
| News content | gemini | ~$0.003/article |
| Image prompt | gemini-fast | ~$0.0004/prompt |
| Image generation | flux | ~FREE (anonymous) |
| **Total per article** | | **~$0.004** |

> 250 complete articles (with images) ≈ $1 USD

With flower tier (claude + gptimage):

| Task | Model | Cost |
|------|-------|------|
| News content | claude | ~$0.015/article |
| Image prompt | claude-fast | ~$0.005/prompt |
| Image generation | gptimage | ~$0.000008/image |
| **Total per article** | | **~$0.02** |

## Related

- [Task Types Guide](../guides/task-types.md) - Configure models per task
- [Configuration](configuration.md) - Full YAML reference
