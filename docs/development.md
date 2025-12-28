# Development

Local development setup and useful commands.

## Local Development

This bundle uses path repository during development:

```json
// composer.json of your project
{
    "repositories": [
        { "type": "path", "url": "../packages/ai-content-bundle" }
    ],
    "require": {
        "xmon-org/ai-content-bundle": "@dev"
    }
}
```

## Useful Commands

```bash
# View bundle configuration summary
bin/console xmon:ai:debug

# Clear cache after bundle changes
bin/console cache:clear

# View provider service
bin/console debug:container PollinationsTextProvider

# View full YAML configuration
bin/console debug:config xmon_ai_content
```

## Debug Command

The bundle includes a diagnostic command that shows:

- Text and image provider configuration (model, fallback_models, retries, timeout)
- Task models configuration (default model, cost, allowed models per task)
- Configured styles, compositions, palettes, and extras
- Presets with their options
- Prompt templates

```
$ bin/console xmon:ai:debug

xmon-org/ai-content-bundle Configuration
========================================

Text Provider (Pollinations)
----------------------------
 Setting            Value
 Status             ✓ Available
 API Key            Not set (free tier)
 Default Model      mistral
 Fallback Models    nova-micro → gemini-fast → openai-fast
 Retries per Model  2
 Retry Delay        3s
 Timeout            60s
 Endpoint Mode      openai

Image Provider (Pollinations)
-----------------------------
 Setting            Value
 Status             ✓ Available
 API Key            Not set (free tier)
 Default Model      flux
 Fallback Models    zimage → turbo
 Retries per Model  2
 Retry Delay        3s
 Timeout            120s
 Default Size       1280x720
 Quality            high
 Private            Yes

Task Models
-----------
 Task              Default Model   Cost    Allowed Models
 news_content      mistral         FREE    mistral, nova-micro, openai-fast
 image_prompt      mistral         FREE    mistral, openai-fast, nova-micro
 image_generation  flux            FREE    flux, zimage, turbo

Styles, Compositions, Palettes, Extras, Presets...

Use bin/console debug:config xmon_ai_content for full YAML configuration.
```

> **Note:** The output above shows a free-tier configuration. With an API key, you'll see premium models (gemini, claude, gptimage, seedream, etc.) with their costs.

## Testing

```bash
# Run PHP syntax check
php -l src/**/*.php

# Run PHPStan (if configured)
vendor/bin/phpstan analyse src/

# Run tests (if configured)
vendor/bin/phpunit
```

## Debugging Tips

### Provider not working

1. Check if enabled: `bin/console xmon:ai:debug`
2. Check API key is set: `bin/console debug:container --env-vars | grep XMON`
3. Check service registration: `bin/console debug:container AiTextService`

### Configuration not applied

1. Clear cache: `bin/console cache:clear`
2. Check YAML syntax: `bin/console debug:config xmon_ai_content`
3. Check merge behavior (your config merges with defaults)

### Model not working

1. Check if the model is in your tier (anonymous, seed, flower)
2. Check API key is set if using premium models
3. Check fallback configuration in `text.fallback_models` or `image.fallback_models`

## Related

- [Architecture](reference/architecture.md) - Bundle structure
- [Configuration Reference](reference/configuration.md) - Full YAML options
