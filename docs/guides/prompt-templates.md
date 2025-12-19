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

## Related

- [Text Generation](text-generation.md) - Basic text generation
- [Configuration Reference](../reference/configuration.md) - Full YAML reference
