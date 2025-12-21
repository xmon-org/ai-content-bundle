# Configuration Reference

Complete YAML configuration reference for xmon/ai-content-bundle.

## Full Configuration

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    # ============================================
    # TEXT PROVIDERS
    # ============================================
    text:
        providers:
            gemini:
                enabled: true
                priority: 100
                api_key: '%env(XMON_AI_GEMINI_API_KEY)%'
                model: 'gemini-2.0-flash-lite'
                fallback_models: []
                timeout: 30

            openrouter:
                enabled: true
                priority: 50
                api_key: '%env(XMON_AI_OPENROUTER_API_KEY)%'
                model: 'google/gemini-2.0-flash-exp:free'
                fallback_models:
                    - 'meta-llama/llama-3.3-70b-instruct:free'
                timeout: 90

            pollinations:
                enabled: true
                priority: 10
                model: 'openai'
                fallback_models: []
                timeout: 60

        defaults:
            retries: 2       # Retry attempts per provider
            retry_delay: 3   # Seconds between retries

    # ============================================
    # IMAGE PROVIDERS
    # ============================================
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

    # ============================================
    # ADMIN UI SETTINGS
    # ============================================
    admin:
        base_template: '@SonataAdmin/standard_layout.html.twig'  # Custom layout
        show_bundle_credit: true   # Show "Powered by XmonAiContentBundle" footer

    # ============================================
    # SONATA MEDIA INTEGRATION
    # ============================================
    media:
        default_context: 'default'
        provider: 'sonata.media.provider.image'

    # ============================================
    # IMAGE OPTIONS (Styles, Compositions, etc.)
    # ============================================
    image_options:
        # Custom styles (merged with defaults)
        styles:
            custom-style:
                label: 'My Custom Style'
                prompt: 'custom style description for AI'

        # Custom compositions
        compositions:
            custom-comp:
                label: 'Custom Composition'
                prompt: 'composition description'

        # Custom palettes
        palettes:
            custom-palette:
                label: 'Custom Palette'
                prompt: 'color palette description'

        # Custom extras
        extras:
            custom-extra:
                label: 'Custom Extra'
                prompt: 'extra modifier description'

        # Disable specific defaults
        disable_defaults:
            styles: []           # ['oil-painting', 'digital-art']
            compositions: []     # ['panoramic']
            palettes: []         # ['high-contrast']
            extras: []           # ['silhouettes']

    # ============================================
    # PRESETS (Predefined option combinations)
    # ============================================
    presets:
        custom-preset:
            name: 'My Custom Preset'
            style: 'sumi-e'
            composition: 'centered'
            palette: 'monochrome'
            extras: ['no-text', 'atmospheric']

    # Disable specific default presets
    disable_preset_defaults: []  # ['zen-contemplativo']

    # ============================================
    # PROMPT TEMPLATES
    # ============================================
    prompts:
        templates:
            custom-template:
                name: 'My Custom Template'
                description: 'What this template does'
                system: |
                    System prompt instructions here.
                    Multi-line supported.
                user: |
                    User message with {variable} placeholders.
                    Title: {title}
                    Content: {content}

        # Disable specific default templates
        disable_defaults: []  # ['title-generator', 'summarizer']

    # ============================================
    # HISTORY (Image history settings)
    # ============================================
    history:
        max_images: 5    # Maximum images per entity (1-50)
```

## Provider Configuration

### Common Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable the provider |
| `priority` | int | varies | Higher = tried first |
| `api_key` | string | `null` | API key (required for most) |
| `model` | string | varies | Default model |
| `fallback_models` | array | `[]` | Backup models |
| `timeout` | int | varies | Request timeout (seconds) |

### Text Provider Defaults

| Provider | Priority | Timeout | Requires API Key |
|----------|----------|---------|------------------|
| Gemini | 100 | 30s | Yes |
| OpenRouter | 50 | 90s | Yes |
| Pollinations | 10 | 60s | No |

### Image Provider Defaults

| Provider | Priority | Timeout | Requires API Key |
|----------|----------|---------|------------------|
| Pollinations | 100 | 120s | Optional |

## Image Options Structure

### Style Definition

```yaml
styles:
    key-name:
        label: 'Human-readable label'
        prompt: 'Text added to the prompt for this style'
```

### Preset Definition

```yaml
presets:
    key-name:
        name: 'Human-readable name'
        style: 'style-key'        # Optional
        composition: 'comp-key'   # Optional
        palette: 'palette-key'    # Optional
        extras: ['extra1', 'extra2']  # Optional
```

## Prompt Template Structure

```yaml
prompts:
    templates:
        key-name:
            name: 'Human-readable name'
            description: 'Optional description'
            system: 'System prompt for AI'
            user: 'User message with {variables}'
```

Variables use `{variable_name}` syntax and are replaced at runtime.

## Admin UI Settings

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `base_template` | string | `@SonataAdmin/standard_layout.html.twig` | Base template for AI image pages. Override to use your project's custom admin layout. |
| `show_bundle_credit` | bool | `true` | Show "Powered by XmonAiContentBundle" footer in the AI image generation page. Set to `false` to hide. |

```yaml
xmon_ai_content:
    admin:
        base_template: '@App/admin/layout.html.twig'  # Custom project layout
        show_bundle_credit: false  # Hide bundle credit
```

## Environment Variables

```bash
# Text providers
XMON_AI_GEMINI_API_KEY=AIza...
XMON_AI_OPENROUTER_API_KEY=sk-or-v1-...

# Image providers
XMON_AI_POLLINATIONS_API_KEY=your_key

# Optional: Custom provider
XMON_AI_ANTHROPIC_API_KEY=sk-ant-...
```

## Related

- [Installation](../installation.md) - Setup guide
- [Architecture](architecture.md) - Bundle structure
