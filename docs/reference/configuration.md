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
            default_model: 'gemini'
            allowed_models: ['gemini', 'deepseek', 'mistral', 'openai', 'openai-fast']

        image_prompt:
            default_model: 'gemini-fast'
            allowed_models: ['gemini-fast', 'openai-fast', 'mistral']

        image_generation:
            default_model: 'flux'
            allowed_models: ['flux', 'turbo']

    # ============================================
    # TEXT GENERATION (Pollinations API)
    # ============================================
    text:
        api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional - defaults are free tier
        model: 'mistral'                   # Default model (free tier)
        fallback_models: ['nova-micro', 'gemini-fast', 'openai-fast']  # Free tier fallbacks
        retries_per_model: 2               # Retries before trying next model
        retry_delay: 3                     # Seconds between retries
        timeout: 60                        # HTTP timeout in seconds
        endpoint_mode: 'openai'            # 'openai' (POST) or 'simple' (GET)

    # ============================================
    # IMAGE GENERATION (Pollinations API)
    # ============================================
    image:
        api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional - defaults are free tier
        model: 'flux'                      # Default model (free tier)
        fallback_models: ['zimage', 'turbo']  # Free tier fallbacks
        retries_per_model: 2               # Retries before trying next model
        retry_delay: 3                     # Seconds between retries
        timeout: 120                       # HTTP timeout in seconds
        width: 1280                        # Default image width
        height: 720                        # Default image height
        quality: 'high'                    # Quality: low, medium, high, hd
        negative_prompt: 'worst quality, blurry, text, letters, watermark, human faces, detailed faces'
        private: true                      # Hide from Pollinations public feeds
        nofeed: true                       # Do not add to public feed

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

    # Default preset when none is selected in admin
    default_preset: 'sumi-e-clasico'  # null = use first available preset

    # Fixed suffix appended to ALL generated styles
    style_suffix: 'professional artistic quality, negative space'

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

    # ============================================
    # IMAGE SUBJECT GENERATOR (Two-step anchor)
    # ============================================
    image_subject:
        anchor_types:
            PLACE: 'Include distinctive regional landscape...'
            PERSON: 'Feature a distinguished silhouette...'
            NUMBER: 'Feature the number prominently...'
            EVENT: 'Show specific event atmosphere...'
            ORGANIZATION: 'Include institutional symbols...'
            MEMORIAL: 'Solemn respectful atmosphere...'
            default: 'Incorporate this element visually...'
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

**Free Configuration (No Cost) - This is the default:**

```yaml
# Just use: xmon_ai_content: ~
# Which gives you:
tasks:
    news_content:
        default_model: 'mistral'
        allowed_models: ['mistral', 'nova-micro', 'openai-fast']
    image_prompt:
        default_model: 'mistral'
        allowed_models: ['mistral', 'openai-fast', 'nova-micro']
    image_generation:
        default_model: 'flux'
        allowed_models: ['flux', 'zimage', 'turbo']

text:
    model: 'mistral'
    fallback_models: ['nova-micro', 'gemini-fast', 'openai-fast']

image:
    model: 'flux'
    fallback_models: ['zimage', 'turbo']
```

See [Task Types Guide](../guides/task-types.md) for detailed usage examples.

### Model Selection Priority

When generating images, the bundle resolves which model to use following this priority (highest to lowest):

```
+----------------------------------------------------------------+
|  1. PAGE SELECTOR (AI Image Generator page)                    |
|     - User selects model in dropdown for THIS generation       |
|     - Passed via POST request 'model' parameter                |
+----------------------------------------------------------------+
|  2. DATABASE (Entity.aiImageModel via AiStyleConfigurableTrait)|
|     - Default model configured via Sonata Admin                |
|     - Stored in entity using the trait                         |
+----------------------------------------------------------------+
|  3. YAML (tasks.image_generation.default_model)                |
|     - Project configuration in config/packages/                |
+----------------------------------------------------------------+
|  4. BUNDLE DEFAULT ('flux')                                    |
|     - Ultimate fallback if nothing else is configured          |
+----------------------------------------------------------------+
```

This hierarchy allows:
- **End users** to override model per-generation (level 1)
- **Admins** to set a project-wide default in the UI (level 2)
- **Developers** to configure defaults in YAML (level 3)
- **Bundle** to provide sensible fallback (level 4)

See [Admin Integration - Default Image Model Selection](../guides/admin-integration.md#default-image-model-selection) for implementation details.

## Text Configuration

Direct configuration for the Pollinations text provider.

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `api_key` | string | `null` | Optional - all defaults are free tier |
| `model` | string | `'mistral'` | Default model (free tier) |
| `fallback_models` | array | `['nova-micro', 'gemini-fast', 'openai-fast']` | Free tier fallback models |
| `retries_per_model` | int | `2` | Retries before trying next model (1-10) |
| `retry_delay` | int | `3` | Seconds between retries (0-60) |
| `timeout` | int | `60` | Request timeout in seconds |
| `endpoint_mode` | enum | `'openai'` | `'openai'` (POST) or `'simple'` (GET) |

### Endpoint Modes

| Mode | Endpoint | Pros | Cons |
|------|----------|------|------|
| `openai` | `POST /v1/chat/completions` | No URL limit, token tracking | More parsing overhead |
| `simple` | `GET /text/{prompt}` | Simpler, less overhead | URL limit (~2000 chars) |

**Recommendation:** Use `openai` (default) for production.

## Image Configuration

Direct configuration for the Pollinations image provider.

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `api_key` | string | `null` | Optional - all defaults are free tier |
| `model` | string | `'flux'` | Default model (free tier) |
| `fallback_models` | array | `['zimage', 'turbo']` | Free tier fallback models |
| `retries_per_model` | int | `2` | Retries before trying next model (1-10) |
| `retry_delay` | int | `3` | Seconds between retries (0-60) |
| `timeout` | int | `120` | Request timeout in seconds |
| `width` | int | `1280` | Default image width |
| `height` | int | `720` | Default image height |
| `quality` | enum | `'high'` | Image quality: `low`, `medium`, `high`, `hd` |
| `negative_prompt` | string | *(see below)* | What to avoid in generated images |
| `private` | bool | `true` | Hide images from Pollinations public feeds |
| `nofeed` | bool | `true` | Do not add images to public feed |

**Default negative_prompt:**
```
worst quality, blurry, text, letters, watermark, human faces, detailed faces
```

## Per-Request Options Override

Both text and image generation support per-request override of configuration values:

```php
// Override timeout and retries for this specific call
$result = $aiTextService->generate($system, $user, [
    'model' => 'claude',           // Use specific model
    'use_fallback' => false,       // Don't try fallback models
    'timeout' => 120,              // Custom timeout
    'retries_per_model' => 1,      // Fewer retries
    'retry_delay' => 1,            // Shorter delay
]);

// Image generation with custom options
$result = $aiImageService->generate($prompt, [
    'model' => 'gptimage',
    'width' => 1920,
    'height' => 1080,
    'quality' => 'hd',
    'use_fallback' => true,        // Explicitly enable fallback
]);
```

### Fallback Behavior

| Scenario | `use_fallback` | Behavior |
|----------|----------------|----------|
| No model specified | `true` (default) | Uses configured model + fallback_models |
| Model specified | `false` (default) | Only tries the specified model |
| Model + use_fallback: true | `true` | Specified model + fallback_models |

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

### Default Preset

The `default_preset` option specifies which preset to use as a fallback when no preset is explicitly selected in the admin interface.

```yaml
xmon_ai_content:
    default_preset: 'sumi-e-clasico'
```

**Fallback priority:**

1. Selected preset (if mode is 'preset' and a preset is selected)
2. Custom fields (if mode is 'custom' and fields are filled)
3. Configured `default_preset` (if set)
4. First available preset (last resort)

If `default_preset` is `null` (default), the bundle uses the first available preset as the fallback.

### Style Suffix

The `style_suffix` option allows you to append fixed text to ALL generated style prompts. This is useful for applying consistent quality modifiers or technical restrictions across all image generations.

```yaml
xmon_ai_content:
    style_suffix: 'professional artistic quality, negative space'
```

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `style_suffix` | string | `''` (empty) | Text appended to all generated style prompts |

**Use cases:**

- **Quality modifiers**: `'professional artistic quality, high detail'`
- **Technical restrictions**: `'no text, no letters, clean composition'`
- **Consistent aesthetic**: `'minimalist, elegant, balanced negative space'`

**Example:**

If a style generates the prompt:
```
sumi-e ink wash painting with fluid brushstrokes
```

With `style_suffix: 'professional artistic quality, negative space'`, the final prompt becomes:
```
sumi-e ink wash painting with fluid brushstrokes professional artistic quality, negative space
```

**Service parameter:** The suffix is exposed as `%xmon_ai_content.style_suffix%` for use in custom services.

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

## Image Subject Generator

The `image_subject` section configures the two-step anchor extraction system for generating unique, differentiated image subjects.

### How It Works

1. **Step 1 - Anchor Extraction**: Analyzes content to extract a unique visual "anchor" (a distinctive element like a place, person, number, etc.)
2. **Step 2 - Subject Generation**: If an anchor is found, generates an image subject that prominently incorporates that anchor
3. **Fallback**: If no usable anchor is found, falls back to one-step generation

This two-step process ensures that images generated for different content are visually distinct, even when the content themes are similar.

### Anchor Types

| Type | Description | Visual Guideline |
|------|-------------|-----------------|
| `PLACE` | Specific location (city, venue, region) | Regional landscape, architecture |
| `PERSON` | Named individual | Distinguished silhouette (no faces) |
| `NUMBER` | Anniversary, edition, year | Prominent numerals or patterns |
| `EVENT` | Specific event type | Event atmosphere, formality |
| `ORGANIZATION` | Institution, federation | Institutional symbols, unity |
| `MEMORIAL` | Tribute, death | Solemn atmosphere, falling petals |
| `default` | Fallback for any type | Generic visual incorporation |

### Configuration

```yaml
xmon_ai_content:
    image_subject:
        anchor_types:
            # Override default guidelines
            PLACE: 'Include distinctive regional landscape, architecture, or natural elements from this location.'
            PERSON: 'Feature a distinguished silhouette (NEVER detailed face) representing this individual.'
            NUMBER: 'Feature the number prominently - as golden numerals, symbolic element, or visual pattern.'
            EVENT: 'Show specific event atmosphere - gathering energy, formality, celebration mood.'
            ORGANIZATION: 'Include institutional symbols, unity elements, or formal group atmosphere.'
            MEMORIAL: 'Solemn respectful atmosphere, falling petals or leaves, solitary distinguished silhouette.'
            default: 'Incorporate this element visually in the scene.'

            # Add custom anchor types
            PRODUCT: 'Feature the product prominently in an elegant setting.'
```

### Default Templates

The bundle includes three default prompt templates for the anchor system:

| Template | Purpose |
|----------|---------|
| `anchor_extraction` | Extracts anchor from content (TYPE, VALUE, VISUAL) |
| `subject_from_anchor` | Generates subject using extracted anchor |
| `subject_one_step` | Fallback when no anchor is usable |

These templates can be overridden in the `prompts.templates` section. See [Image Subject Generator Guide](../guides/image-subject-generator.md) for usage examples.

## Related

- [Task Types Guide](../guides/task-types.md) - Detailed TaskType usage
- [Image Subject Generator Guide](../guides/image-subject-generator.md) - Two-step anchor system
- [Providers Reference](providers.md) - Available models and costs
- [Installation](../installation.md) - Setup guide
- [Architecture](architecture.md) - Bundle structure
