# Providers Reference

Available AI providers in xmon-org/ai-content-bundle.

> **Architecture Decision (December 2025):** The bundle uses Pollinations as the sole provider for both text and image generation. Pollinations provides access to multiple AI models (Claude, Gemini, GPT, Mistral, etc.) through a unified API.

## Query Available Models

Get the current list of models with pricing directly from Pollinations:

```bash
# All text models with pricing
curl -H "Authorization: Bearer YOUR_API_KEY" https://gen.pollinations.ai/models

# All image models with pricing
curl -H "Authorization: Bearer YOUR_API_KEY" https://gen.pollinations.ai/image/models
```

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
```

### Available Text Models

| Model | Tier | ~Resp/$ | Description |
|-------|------|---------|-------------|
| `openai-fast` | anonymous | 2,272 | GPT-5 Nano - Ultra fast, no reasoning |
| `openai` | anonymous | 1,666 | GPT-5 Mini - Fast & balanced |
| `nova-micro` | seed | 7,142 | Amazon Nova Micro - Ultra cheap |
| `gemini-fast` | seed | 2,500 | Gemini 2.5 Flash Lite - Fast prompts |
| `mistral` | seed | 2,857 | Mistral Small 3.2 - Efficient |
| `grok` | seed | 2,000 | xAI Grok 4 Fast - Real-time |
| `deepseek` | seed | 595 | DeepSeek V3.2 - Reasoning |
| `qwen-coder` | seed | 1,111 | Qwen 2.5 Coder - Code generation |
| `gemini` | seed | 333 | Gemini 3 Flash - Pro-grade |
| `gemini-search` | seed | 333 | Gemini 3 Flash with Search |
| `claude-fast` | flower | 200 | Claude Haiku 4.5 - Fast premium |
| `perplexity-fast` | flower | 1,000 | Perplexity Sonar - Web search |
| `claude` | flower | 66 | Claude Sonnet 4.5 - High quality |
| `openai-large` | flower | 71 | GPT-5.2 - Most powerful |
| `gemini-large` | flower | 83 | Gemini 3 Pro - 1M context |
| `claude-large` | flower | 40 | Claude Opus 4.5 - Most intelligent |

> **Pricing:** 1 pollen = $1 USD. ~Resp/$ = approximate responses per dollar.

## Image Provider

```yaml
image:
    providers:
        pollinations:
            enabled: true
            priority: 100
            api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Required for premium models
            timeout: 120
```

### Available Image Models

| Model | Tier | ~Img/$ | Description |
|-------|------|--------|-------------|
| `flux` | anonymous | 8,333 | Fast & high quality (free) |
| `turbo` | anonymous | 3,333 | Ultra-fast previews (free) |
| `zimage` | seed | 5,000 | Z-Image-Turbo (alpha) |
| `nanobanana` | seed | 33,333 | Gemini 2.5 Flash, reference images |
| `nanobanana-pro` | flower | 8,333 | Gemini 3 Pro, 4K |
| `gptimage` | flower | 125,000 | OpenAI, best prompt understanding |
| `gptimage-large` | flower | 31,250 | OpenAI 1.5, advanced |
| `seedream` | flower | 33 | ByteDance ARK, complex scenes |
| `seedream-pro` | flower | 25 | ByteDance 4K, multi-image |
| `kontext` | flower | 25 | Context-aware generation |

**Options:**
- `width`, `height` - Image dimensions
- `seed` - For reproducible results
- `nologo` - Remove watermark
- `enhance` - AI enhances prompt

## Cost Estimation

With seed tier (gemini + flux):

| Task | Model | Cost |
|------|-------|------|
| News content | gemini | ~$0.003/article |
| Image prompt | gemini-fast | ~$0.0004/prompt |
| Image generation | flux | FREE |
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
- [Custom Providers](../guides/custom-providers.md) - Create your own
