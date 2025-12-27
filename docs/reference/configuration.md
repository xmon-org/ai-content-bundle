# Configuration Reference

Complete YAML configuration reference for xmon-org/ai-content-bundle.

## Full Configuration

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    # ============================================
    # TASK TYPES (Model selection per task)
    # ============================================
    tasks:
        news_content:
            default_model: 'claude'
            allowed_models: ['claude', 'gemini', 'openai', 'gemini-fast', 'mistral']

        image_prompt:
            default_model: 'gemini-fast'
            allowed_models: ['openai-fast', 'gemini-fast', 'mistral']

        image_generation:
            default_model: 'gptimage'
            allowed_models: ['flux', 'gptimage', 'seedream', 'nanobanana', 'turbo']

    # ============================================
    # TEXT PROVIDERS
    # ============================================
    text:
        providers:
            pollinations:
                enabled: true
                priority: 10
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional for basic use
                model: 'openai'  # Default model (TaskType config takes precedence)
                fallback_models: ['openai-fast']  # Backup models if main fails
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
                model: 'flux'  # Default model (TaskType config takes precedence)
                fallback_models: ['turbo']  # Backup models if main fails
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
        base_template: '@SonataAdmin/standard_layout.html.twig'
        show_bundle_credit: true

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
        styles:
            custom-style:
                label: 'My Custom Style'
                prompt: 'custom style description for AI'

        compositions:
            custom-comp:
                label: 'Custom Composition'
                prompt: 'composition description'

        palettes:
            custom-palette:
                label: 'Custom Palette'
                prompt: 'color palette description'

        extras:
            custom-extra:
                label: 'Custom Extra'
                prompt: 'extra modifier description'

        disable_defaults:
            styles: []
            compositions: []
            palettes: []
            extras: []

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

    disable_preset_defaults: []

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
                user: |
                    User message with {variable} placeholders.
                    Title: {title}

        disable_defaults: []

    # ============================================
    # HISTORY (Image history settings)
    # ============================================
    history:
        max_images: 5
```

## Task Types Configuration

The `tasks` section configures which AI models to use for different types of operations.

### Available Task Types

| Task Type | Description | Model Type |
|-----------|-------------|------------|
| `news_content` | Article/content generation | Text |
| `image_prompt` | Scene description for images | Text |
| `image_generation` | Actual image creation | Image |

### Task Configuration Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `default_model` | string | Yes | Model to use when none specified |
| `allowed_models` | array | Yes | List of models allowed for this task |

### Text Models

| Model Key | Name | ~Responses/Pollen | Recommended For |
|-----------|------|-------------------|-----------------|
| `claude` | Claude Sonnet 4.5 | 330 | `news_content` (high quality) |
| `gemini` | Gemini 3 Flash | 1,600 | General content |
| `openai` | GPT-5 Mini | 8,000 | General purpose |
| `gemini-fast` | Gemini 2.5 Flash Lite | 12,000 | `image_prompt` (fast) |
| `openai-fast` | GPT-5 Nano | 11,000 | Quick operations |
| `mistral` | Mistral Small | 13,000 | Backup/fallback |

### Image Models

| Model Key | Name | ~Images/Pollen | Notes |
|-----------|------|----------------|-------|
| `gptimage` | OpenAI Image 1 Mini | 160 | Best for complex scenes |
| `seedream` | ByteDance ARK 2K | 35 | High quality |
| `nanobanana` | Gemini Image | 25 | With reference |
| `flux` | Flux (free) | 8,300 | Good default |
| `turbo` | Turbo (free) | 3,300 | Fast previews |

### Example Configurations

**Premium Configuration (Best Quality):**

```yaml
tasks:
    news_content:
        default_model: 'claude'
        allowed_models: ['claude', 'gemini']
    image_prompt:
        default_model: 'gemini-fast'
        allowed_models: ['gemini-fast', 'openai-fast']
    image_generation:
        default_model: 'gptimage'
        allowed_models: ['gptimage', 'seedream', 'flux']
```

**Free Configuration (No Cost):**

```yaml
tasks:
    news_content:
        default_model: 'openai'
        allowed_models: ['openai', 'openai-fast', 'mistral']
    image_prompt:
        default_model: 'openai-fast'
        allowed_models: ['openai-fast', 'mistral']
    image_generation:
        default_model: 'flux'
        allowed_models: ['flux', 'turbo']
```

See [Task Types Guide](../guides/task-types.md) for detailed usage examples.

## Provider Configuration

### Pollinations (Text & Image)

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `enabled` | bool | `true` | Enable/disable the provider |
| `priority` | int | varies | Higher = tried first |
| `api_key` | string | `null` | Optional for basic use, required for premium models |
| `model` | string | varies | Fallback model (TaskType takes precedence) |
| `timeout` | int | varies | Request timeout (seconds) |

> **Note:** Model selection is now handled by the `tasks` configuration. The `model` field in providers is only used as a fallback if no TaskType is specified.

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
        style: 'style-key'
        composition: 'comp-key'
        palette: 'palette-key'
        extras: ['extra1', 'extra2']
```

## Prompt Template Structure

### Basic Template

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

### Template with Variants

```yaml
prompts:
    templates:
        key-name:
            name: 'Scene Generator'
            system: |
                Generate using:
                - LOCATION: {variant_location}
                - MOOD: {variant_mood}
            user: 'Title: {title}'

            variants:
                location:
                    - "museum courtyard"
                    - "rooftop terrace"
                mood:
                    - "peaceful contemplation"
                    - "celebration aftermath"

            variant_keywords:
                location: ["museum", "terrace"]
                mood: ["celebration", "memorial"]
```

See [Prompt Templates Guide](../guides/prompt-templates.md) for detailed examples.

## Admin UI Settings

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `base_template` | string | `@SonataAdmin/standard_layout.html.twig` | Base template for AI image pages |
| `show_bundle_credit` | bool | `true` | Show bundle credit footer |

## Environment Variables

```bash
# Single API key for all AI operations (optional for basic use)
XMON_AI_POLLINATIONS_API_KEY=your_pollinations_api_key
```

> **Note:** The API key is optional for basic models (openai, openai-fast, flux, turbo) but required for premium models (claude, gptimage, seedream).

## Related

- [Task Types Guide](../guides/task-types.md) - Detailed TaskType usage
- [Providers Reference](providers.md) - Available models and costs
- [Installation](../installation.md) - Setup guide
- [Architecture](architecture.md) - Bundle structure
