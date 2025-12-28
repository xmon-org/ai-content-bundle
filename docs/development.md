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

- Provider status (Pollinations API for both text and image)
- Task models configuration (default model, cost, allowed models per task)
- Configured styles, compositions, palettes, and extras
- Presets with their options
- Prompt templates

```
$ bin/console xmon:ai:debug

xmon-org/ai-content-bundle Configuration
========================================

Provider Status
---------------
 Service           Status   Provider
 Text Generation   ✓        Pollinations API
 Image Generation  ✓        Pollinations API

Task Models
-----------
 Task              Default Model   Cost    Allowed Models
 news_content      openai          FREE    openai, openai-fast
 image_prompt      openai-fast     FREE    openai-fast
 image_generation  flux            FREE    flux, turbo

Styles
------
 Key            Label
 sumi-e         Sumi-e (tinta japonesa)
 watercolor     Acuarela
 ...

Compositions
------------
 Key            Label
 centered       Centrada
 rule-of-thirds Regla de tercios
 ...

Palettes
--------
 Key            Label
 monochrome     Monocromo
 earth-tones    Tonos tierra
 ...

Extras
------
 Key            Label
 no-text        Sin texto
 atmospheric    Atmosférico
 ...

Presets
-------
 Key              Name             Style    Composition      Palette      Extras
 sumi-e-clasico   Sumi-e Clasico   sumi-e   negative-space   monochrome   no-text, silhouettes, atmospheric

Prompt Templates
----------------
 Key              Name                       Description
 image_subject    Image Subject Generator    Generates visual descriptions...
 summarizer       Content Summarizer         Summarizes content...
 ...

Use bin/console debug:config xmon_ai_content for full YAML configuration.
```

> **Note:** The output above shows a free-tier configuration. If you have an API key configured, you'll see different models (gemini, claude, gptimage, etc.) with their respective costs.

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
