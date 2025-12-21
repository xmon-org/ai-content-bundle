# Prompt Templates Guide

The bundle includes a configurable system/user prompt templates system via YAML.

## Available Services

- `PromptTemplateService`: Access to prompt templates with variable rendering

## Default Templates

The bundle includes predefined templates that you can override or extend:

| Key | Name | Description |
|-----|------|-------------|
| `image_subject` | Image Subject Generator | Generates visual descriptions for images. Uses two-step classification for precise categorization |
| `summarizer` | Content Summarizer | Summarizes content preserving key information |
| `title_generator` | Title Generator | Generates attractive titles for content |
| `content_generator` | All-in-One Content Generator | **Optimized**: Generates all fields in a single call (title, summary, SEO, image subject). Returns JSON |

## Basic Usage

```php
use Xmon\AiContentBundle\Service\PromptTemplateService;
use Xmon\AiContentBundle\Service\AiTextService;

class MyService
{
    public function __construct(
        private readonly PromptTemplateService $promptTemplates,
        private readonly AiTextService $aiTextService,
    ) {}

    public function generateImageSubject(string $title, string $summary): string
    {
        // Render template with variables
        $prompts = $this->promptTemplates->render('image_subject', [
            'title' => $title,
            'summary' => $summary,
        ]);

        // $prompts = ['system' => '...', 'user' => 'Title: ...\n\nSummary: ...']

        $result = $this->aiTextService->generate(
            $prompts['system'],
            $prompts['user']
        );

        return $result->getText();
    }

    public function summarize(string $content): string
    {
        $prompts = $this->promptTemplates->render('summarizer', [
            'content' => $content,
        ]);

        return $this->aiTextService->generate(
            $prompts['system'],
            $prompts['user']
        )->getText();
    }
}
```

## Optimized Usage with content_generator (recommended)

The `content_generator` template generates all fields in **a single API call**, optimizing costs and latency:

```php
public function generateAllContent(string $rawContent): array
{
    $prompts = $this->promptTemplates->render('content_generator', [
        'content' => $rawContent,
    ]);

    $result = $this->aiTextService->generate($prompts['system'], $prompts['user']);

    // The result is structured JSON
    $data = json_decode($result->getText(), true);

    // Single call → all fields
    return [
        'title' => $data['title'],           // "Seminario de Aikido en Madrid"
        'summary' => $data['summary'],       // "El próximo mes se celebrará..."
        'metaTitle' => $data['metaTitle'],   // "Seminario Aikido Madrid 2025"
        'metaDescription' => $data['metaDescription'],
        'imageSubject' => $data['imageSubject'], // "multiple silhouettes practicing..."
    ];
}
```

**Advantages vs individual calls:**
- **1 call** instead of 5 (title + summary + metaTitle + metaDescription + imageSubject)
- **Fewer tokens** consumed (context not repeated)
- **Lower total latency**

**Customization:** Copy this template and adapt it to the fields your project needs.

## Individual Access to Prompt Parts

```php
// Get only the system prompt
$systemPrompt = $this->promptTemplates->getSystemPrompt('image_subject');

// Get and render only the user message
$userMessage = $this->promptTemplates->renderUserMessage('image_subject', [
    'title' => 'My title',
    'summary' => 'My summary',
]);

// Check if a template exists
if ($this->promptTemplates->hasTemplate('custom_prompt')) {
    // ...
}
```

## Customizing Templates in Your Project

Bundle templates are **merged** with yours automatically. Your templates are added to the defaults, and if you use the same key you can override them.

### Add templates (automatic merge)

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    prompts:
        templates:
            seo_description:
                name: 'SEO Description Generator'
                description: 'Generates SEO-optimized meta descriptions'
                system: |
                    You are an SEO expert. Generate meta descriptions that:
                    1. Are between 150-160 characters
                    2. Include the main keyword naturally
                    3. Have a clear call to action
                    4. Are compelling and accurate
                user: |
                    Title: {title}
                    Content: {content}
                    Keyword: {keyword}

                    Generate a meta description.

            social_post:
                name: 'Social Media Post'
                description: 'Creates posts for social networks'
                system: |
                    Create engaging social media posts that are concise and shareable.
                    Use emojis appropriately. Include relevant hashtags.
                user: |
                    Content: {content}
                    Platform: {platform}
```

**Variables in templates**: Use `{variable_name}` syntax to define placeholders that will be replaced when calling `render()` or `renderUserMessage()`.

### Override a default

Use the same key to modify a default:

```yaml
xmon_ai_content:
    prompts:
        templates:
            # Override the bundle's summarizer for your context
            summarizer:
                name: 'Resumidor de noticias de Aikido'
                description: 'Summarizes news keeping martial arts context'
                system: |
                    You are an expert summarizer for aikido and martial arts news.
                    Focus on:
                    - Key events, seminars, and courses
                    - Names of senseis and instructors
                    - Dates and locations
                    - Technical terms in their original form

                    Keep summaries concise but informative.
                user: "{content}"
```

### Disable specific defaults

If you don't want to use some bundle templates:

```yaml
xmon_ai_content:
    prompts:
        disable_defaults:
            - title_generator
            - summarizer
```

**Result**: The listed templates are removed from available options.

## Getting Templates for UI

`PromptTemplateService` provides methods to display available templates:

```php
// List of available templates (key => name)
$templates = $this->promptTemplates->getTemplates();
// ['image_subject' => 'Image Subject Generator', 'summarizer' => 'Content Summarizer', ...]

// Get full template data
$template = $this->promptTemplates->getTemplate('image_subject');
// ['name' => '...', 'description' => '...', 'system' => '...', 'user' => '...']

// All available keys
$keys = $this->promptTemplates->getTemplateKeys();
// ['image_subject', 'summarizer', 'title_generator']
```

## Validation

The `PromptTemplateService` validates that templates exist:

```php
try {
    $prompts = $this->promptTemplates->render('invalid_template', []);
} catch (AiProviderException $e) {
    // "Prompt template not found: invalid_template"
}
```

## Prompt Variants

The bundle includes a powerful variant system that allows you to define multiple options for parts of your prompts and have them automatically selected based on content analysis.

### Why Use Variants?

Variants solve a common problem: you want your AI to generate diverse content, but you also want the diversity to be **contextually appropriate**. Instead of the AI choosing randomly or always picking the same option, variants let you:

1. **Define multiple options** for different aspects of your prompt (location, mood, style, etc.)
2. **Let the bundle select** the most appropriate option based on the actual content
3. **Fall back to random selection** when no clear match exists

### Basic Configuration

For best results, use the same language for your content, variants, and keywords. If you need output in a different language, add an explicit instruction in the system prompt.

```yaml
xmon_ai_content:
    prompts:
        templates:
            image_scene:
                name: 'Scene Generator'
                description: 'Generates scene descriptions with pre-selected elements'
                system: |
                    Genera una escena usando EXACTAMENTE estos elementos:
                    - UBICACIÓN: {variant_location}
                    - AMBIENTE: {variant_mood}

                    IMPORTANTE: Tu respuesta debe estar EN INGLÉS.
                user: "Título: {title}\n\nResumen: {summary}"

                variants:
                    location:
                        - "patio de museo con fuente"
                        - "terraza en azotea con vistas"
                        - "sendero de jardín botánico"
                    mood:
                        - "contemplación tranquila"
                        - "después de celebración"
                        - "anticipación matutina"
```

**Key points:**

- Define `variants` as a map of category name to list of options
- Use `{variant_CATEGORY}` placeholders in the `system` prompt
- The bundle replaces placeholders with selected options before sending to the LLM
- Keep variants in the same language as your content for accurate matching

### Keyword-Based Selection

For more intelligent selection, use `variant_keywords` to define which keywords should trigger which options. Keywords must appear in **both** the content AND the variant option to score:

```yaml
xmon_ai_content:
    prompts:
        templates:
            image_scene:
                name: 'Scene Generator'
                system: |
                    Genera una escena usando:
                    - UBICACIÓN: {variant_location}
                    - PRESENCIA: {variant_presence}

                    IMPORTANTE: Responde EN INGLÉS.
                user: "Título: {title}\n\nResumen: {summary}"

                variants:
                    location:
                        - "patio de museo con fuente"
                        - "sala de lectura de universidad"
                        - "andén de estación de tren"
                    presence:
                        - "figura solitaria de espaldas"
                        - "grupo alejándose"
                        - "espacio vacío con tela doblada"

                variant_keywords:
                    location:
                        - "museo"
                        - "universidad"
                        - "estación"
                    presence:
                        - "grupo"
                        - "seminario"
                        - "solo"
                        - "individual"
```

### How Selection Works

The `VariantSelector` service uses this algorithm:

1. **Analyze content**: Combines `title`, `summary`, and `content` variables
2. **For each category**:
   - If `variant_keywords` is defined for this category:
     - Score each option by counting keywords that appear in **BOTH** the content AND the option text
     - Select the option with the highest score
   - If no keywords defined (or no matches):
     - Extract significant words from each option
     - Score by how many words appear in the content
   - If still no matches: **random selection**

**Important**: For best results, keep your content, variants, and keywords in the **same language**. The matching algorithm looks for exact string matches, so "museo" will match "museo" but not "museum".

**Example with Spanish content and options:**

Content: "Seminario de aikido en el museo de Tokyo"

| Option | Keyword in content? | Keyword in option? | Score |
|--------|--------------------|--------------------|-------|
| "patio de museo con fuente" | museo ✓ | museo ✓ | 1 |
| "sala de lectura de universidad" | - | universidad ✗ | 0 |
| "andén de estación de tren" | - | estación ✗ | 0 |

Result: "patio de museo con fuente" is selected (score 1).

### Multi-Language Output

If you need the final output in a different language than your content, add an explicit instruction in the system prompt:

```yaml
xmon_ai_content:
    prompts:
        templates:
            image_scene:
                name: 'Scene Generator'
                system: |
                    Genera una escena usando estos elementos:
                    - UBICACIÓN: {variant_location}
                    - AMBIENTE: {variant_mood}

                    IMPORTANTE: Tu respuesta debe estar EN INGLÉS.

                    Escribe solo la descripción:
                user: "Título: {title}\n\nResumen: {summary}"

                variants:
                    location:
                        - "patio de museo con fuente"
                        - "terraza en azotea con vistas"
                    mood:
                        - "contemplación tranquila"
                        - "después de celebración"

                variant_keywords:
                    location:
                        - "museo"
                        - "terraza"
                    mood:
                        - "celebración"
                        - "reflexión"
```

This approach:
1. Uses Spanish for matching (content, options, keywords all in Spanish)
2. Tells the LLM to output in English for downstream use (e.g., image generation APIs)

### Using Variants in Code

When you call `render()`, variants are automatically processed:

```php
use Xmon\AiContentBundle\Service\PromptTemplateService;

class MyService
{
    public function __construct(
        private readonly PromptTemplateService $promptTemplates,
    ) {}

    public function generateScene(string $title, string $summary): array
    {
        $result = $this->promptTemplates->render('image_scene', [
            'title' => $title,
            'summary' => $summary,
        ]);

        // $result contains:
        // [
        //     'system' => 'Genera una escena usando:
        //                  - UBICACIÓN: patio de museo con fuente
        //                  - AMBIENTE: contemplación tranquila
        //                  IMPORTANTE: Responde EN INGLÉS.',
        //     'user' => 'Título: Mi Artículo\n\nResumen: ...',
        //     'selected_variants' => [
        //         'location' => 'patio de museo con fuente',
        //         'mood' => 'contemplación tranquila',
        //     ]
        // ]

        return $result;
    }
}
```

The `selected_variants` key is only present when variants were processed.

### Best Practices

1. **Keep language consistent**: Use the same language for content, variants, and keywords. The matching is literal.
2. **Keep options diverse**: Each option should represent a genuinely different outcome
3. **Use descriptive keywords**: Keywords should be specific and likely to appear in your content
4. **Balance keyword coverage**: Ensure keywords cover the range of content you expect
5. **Request output language explicitly**: If you need output in a different language, add an instruction in the system prompt
6. **Test with real content**: Use your actual content to verify selections make sense
7. **Log during development**: The bundle logs selected variants at `debug` level

### Backward Compatibility

Templates without `variants` work exactly as before. This feature is entirely optional:

```yaml
# This template works fine - no variants
xmon_ai_content:
    prompts:
        templates:
            simple_summarizer:
                name: 'Summarizer'
                system: 'Summarize the following content.'
                user: '{content}'
```

### Validation

The bundle validates that all `{variant_X}` placeholders in the system prompt have corresponding entries in `variants`:

```yaml
# This will throw AiProviderException
prompts:
    templates:
        broken:
            system: 'Use {variant_missing}'  # Error: no 'missing' in variants
            variants:
                other: ['option1']
```

Error message: `Template "broken" uses {variant_missing} but no variants defined for category "missing"`

## Related

- [Text Generation](text-generation.md) - Basic text generation
- [Configuration Reference](../reference/configuration.md) - Full YAML reference
