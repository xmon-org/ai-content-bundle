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

# View registered providers
bin/console debug:container --tag=xmon_ai_content.text_provider

# View full YAML configuration
bin/console debug:config xmon_ai_content
```

## Debug Command

The bundle includes a diagnostic command that shows:

- Available text providers
- Available image providers
- Configured styles, compositions, palettes, and extras
- Presets with their options
- Prompt templates

```
$ bin/console xmon:ai:debug

xmon-org/ai-content-bundle Configuration
====================================

Text Providers
--------------
 ✓   gemini
 ✓   openrouter
 ✓   pollinations

Image Providers
---------------
 ✓   pollinations

Styles
------
 sumi-e         Sumi-e (tinta japonesa)
 watercolor     Acuarela
 ...

Compositions
------------
 centered       Centrada
 rule-of-thirds Regla de tercios
 ...

Palettes
--------
 monochrome     Monocromo
 earth-tones    Tonos tierra
 ...

Extras
------
 no-text        Sin texto
 atmospheric    Atmosférico
 ...

Presets
-------
 sumi-e-clasico   Sumi-e Clásico   sumi-e   negative-space   monochrome   ...

Prompt Templates
----------------
 image-subject    Image Subject Generator    Generates visual descriptions...
 summarizer       Content Summarizer         Summarizes content...
 ...

Use bin/console debug:config xmon_ai_content for full YAML configuration.
```

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

### Custom provider not detected

1. Ensure it implements `TextProviderInterface`
2. Check `autoconfigure: true` in services.yaml
3. Verify tag: `bin/console debug:container --tag=xmon_ai_content.text_provider`

## Related

- [Architecture](reference/architecture.md) - Bundle structure
- [Configuration Reference](reference/configuration.md) - Full YAML options
