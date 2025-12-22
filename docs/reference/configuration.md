# Configuration Reference

Complete YAML configuration reference for xmon-org/ai-content-bundle.

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
                api_key: '%env(XMON_AI_POLLINATIONS_API_KEY)%'  # Optional
                model: 'openai-fast'
                fallback_models:
                    - 'openai'
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
            # Simple template (no variants)
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

            # Template with variants (advanced)
            # Use same language for content, variants and keywords
            scene-generator:
                name: 'Scene Generator'
                description: 'Generates scenes with pre-selected elements'
                system: |
                    Genera una escena usando EXACTAMENTE:
                    - UBICACIÓN: {variant_location}
                    - AMBIENTE: {variant_mood}

                    IMPORTANTE: Responde EN INGLÉS.
                user: "Título: {title}\nResumen: {summary}"

                # Pre-defined options (same language as content)
                variants:
                    location:
                        - "patio de museo con fuente"
                        - "terraza en azotea con vistas"
                        - "sendero de jardín botánico"
                    mood:
                        - "contemplación tranquila"
                        - "después de celebración"

                # Keywords for intelligent matching (same language)
                variant_keywords:
                    location: ["museo", "jardín", "terraza"]
                    mood: ["celebración", "memorial", "mañana"]

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
| Pollinations | 10 | 60s | Optional (higher rate limits with key) |

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

For best results, keep variants and keywords in the **same language** as your content.
If you need output in a different language, add an explicit instruction in the system prompt.

```yaml
prompts:
    templates:
        key-name:
            name: 'Scene Generator'
            description: 'Generates scenes with pre-selected elements'
            system: |
                Genera contenido usando:
                - UBICACIÓN: {variant_location}
                - AMBIENTE: {variant_mood}

                IMPORTANTE: Tu respuesta debe estar EN INGLÉS.
            user: 'Título: {title}\nResumen: {summary}'

            # Pre-defined options (same language as content)
            variants:
                location:
                    - "patio de museo con fuente"
                    - "terraza en azotea con vistas a la ciudad"
                    - "sendero de jardín botánico"
                mood:
                    - "contemplación tranquila"
                    - "después de celebración"
                    - "anticipación matutina"

            # Keywords for intelligent selection (same language as content)
            variant_keywords:
                location:
                    - "museo"
                    - "terraza"
                    - "jardín"
                mood:
                    - "celebración"
                    - "memorial"
                    - "mañana"
```

### Prompt Template Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Human-readable name for UI display |
| `description` | string | No | Explanation of what the template does |
| `system` | string | Yes | System prompt (instructions for the AI) |
| `user` | string | Yes | User message template with `{variable}` placeholders |
| `variants` | map | No | Category => list of options for dynamic injection |
| `variant_keywords` | map | No | Category => keywords for intelligent selection |

### Variant Placeholders

In the `system` prompt, use `{variant_CATEGORY}` placeholders that match keys in `variants`:

```yaml
system: |
    Use LOCATION: {variant_location}    # Matches variants.location
    Use PRESENCE: {variant_presence}    # Matches variants.presence
variants:
    location: [...]
    presence: [...]
```

### Variant Selection Algorithm

1. If `variant_keywords` is defined for the category:
   - Score each option by keywords found in **both** content AND option
   - Select highest-scoring option
2. If no keywords (or no matches): extract words from options, match against content
3. If still no matches: random selection

**Important**: The matching is literal string comparison. Keep content, variants, and keywords in the same language for accurate matching. Use system prompt instructions to request output in a different language if needed.

### Regex Patterns in Keywords

Keywords can use pipe `|` syntax for OR matching:

```yaml
variant_keywords:
    location:
        - "río|agua|playa|cascada"      # Matches any of these words
        - "montaña|nevada|sendero"
    time_of_day:
        - "amanecer|atardecer|sol"      # Time-related patterns
        - "noche|luna|estrellas"
```

**Behavior:**

| Keyword Type | Matching Rule |
|--------------|---------------|
| Simple (no `\|`) | Must appear in BOTH content AND option to score |
| Regex (with `\|`) | Must match in BOTH content AND option to score |

**Example:**

```yaml
variant_keywords:
    location:
        - "museo"                    # Simple: needs "museo" in content AND option
        - "dojo|tatami"              # Regex: needs "dojo" OR "tatami" in content AND option
```

With content "Graduación en el dojo central":
- Option "dojo tradicional con tatami" → `"dojo|tatami"` matches both → scores +1
- Option "terraza con vistas" → `"dojo|tatami"` not in option → no score

See [Prompt Templates Guide](../guides/prompt-templates.md#prompt-variants) for detailed examples.

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
XMON_AI_POLLINATIONS_API_KEY=your_key  # Optional: higher rate limits

# Image providers (same key works for both text and image)
# XMON_AI_POLLINATIONS_API_KEY=your_key

# Optional: Custom provider
XMON_AI_ANTHROPIC_API_KEY=sk-ant-...
```

## Related

- [Installation](../installation.md) - Setup guide
- [Architecture](architecture.md) - Bundle structure
