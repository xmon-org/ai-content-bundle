# Image Subject Generator Guide

How to generate unique, differentiated image subjects using the two-step anchor extraction system.

## Overview

The `ImageSubjectGenerator` service creates unique image subjects by:

1. **Extracting an anchor** - Identifying a distinctive visual element from content (place, person, number, etc.)
2. **Generating with anchor** - Creating a subject that prominently incorporates that anchor
3. **Fallback** - Using one-step generation if no usable anchor is found

This ensures that images generated for similar content are visually distinct. For example, two articles about aikido seminars will produce different images if one mentions "Galicia" and the other "Tokyo".

## Basic Usage

```php
use Xmon\AiContentBundle\Service\ImageSubjectGenerator;

class MyService
{
    public function __construct(
        private readonly ImageSubjectGenerator $imageSubjectGenerator,
    ) {}

    public function generateImageSubject(): string
    {
        $subject = $this->imageSubjectGenerator->generate(
            title: '50th Anniversary of Aikido in Galicia',
            summary: 'The Real Federacion Gallega de Judo celebrates 50 years of aikido practice in the region with a special seminar.',
        );

        // Result: "A distinguished aikido master silhouette performing a ceremonial bow
        // against the backdrop of rocky Atlantic coastline with Celtic stone monuments,
        // golden numerals 50 subtly integrated into the misty horizon"

        return $subject;
    }
}
```

## Understanding Anchors

An anchor is a distinctive visual element extracted from content. The service classifies anchors into these types:

| Type | What It Captures | Visual Result |
|------|------------------|---------------|
| `PLACE` | Cities, regions, venues | Regional landscapes, architecture |
| `PERSON` | Named individuals | Distinguished silhouettes |
| `NUMBER` | Anniversaries, editions | Prominent numerals, patterns |
| `EVENT` | Specific event types | Event atmosphere, gathering energy |
| `ORGANIZATION` | Federations, institutions | Institutional symbols, unity |
| `MEMORIAL` | Deaths, tributes | Solemn atmosphere, falling petals |
| `GENERIC` | No specific element found | Triggers fallback to one-step |

### Anchor Extraction Example

For the title "50th Anniversary of Aikido in Galicia":

```
TYPE: PLACE
VALUE: Galicia
VISUAL: rocky Atlantic coastline with Celtic stone monuments
```

The `PLACE` anchor type takes precedence because a regional location provides strong visual differentiation. Numbers and organizations are also present but the service selects the most visually distinctive element.

## Debugging and Inspection

### Get Last Anchor

```php
$subject = $this->imageSubjectGenerator->generate($title, $summary);

// Inspect what anchor was extracted
$anchor = $this->imageSubjectGenerator->getLastAnchor();
if ($anchor !== null) {
    echo "Type: " . $anchor['type'];     // PLACE
    echo "Value: " . $anchor['value'];   // Galicia
    echo "Visual: " . $anchor['visual']; // rocky Atlantic coastline...
    echo "Usable: " . ($anchor['isUsable'] ? 'yes' : 'no');
}
```

### Get Last Model Used

```php
$subject = $this->imageSubjectGenerator->generate($title, $summary);

// See which model processed the request
$model = $this->imageSubjectGenerator->getLastModel();
echo "Model used: " . $model; // e.g., "gemini-fast"
```

## Configuration

### Custom Anchor Guidelines

Override the visual guidelines for each anchor type:

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    image_subject:
        anchor_types:
            # Override default guidelines
            PLACE: 'Include distinctive regional landscape, traditional architecture, and natural elements specific to this location.'
            PERSON: 'Feature a distinguished silhouette (NEVER detailed face) with elements suggesting their discipline or profession.'

            # Add domain-specific types
            PRODUCT: 'Feature the product prominently in an elegant, professional setting.'
            TECHNOLOGY: 'Include futuristic or technical visual elements representing the technology.'
```

### Custom Prompt Templates

Override the default templates for complete control:

```yaml
xmon_ai_content:
    prompts:
        templates:
            anchor_extraction:
                name: 'Custom Anchor Extraction'
                description: 'Your custom anchor extraction logic'
                system: |
                    Your custom system prompt...
                user: "Title: {title}\n\nSummary: {summary}"

            subject_from_anchor:
                name: 'Custom Subject with Anchor'
                system: |
                    Your custom subject generation prompt...
                    Anchor type: {anchor_type}
                    Anchor value: {anchor_value}
                    Visual hint: {anchor_visual}
                    Guideline: {anchor_guideline}
                user: "Title: {title}\n\nSummary: {summary}"
```

### Template Variables

**For `anchor_extraction`:**
- `{title}` / `{titulo}` - Content title
- `{summary}` / `{resumen}` - Content summary

**For `subject_from_anchor`:**
- All variables from `anchor_extraction`, plus:
- `{anchor_type}` - Extracted type (PLACE, PERSON, etc.)
- `{anchor_value}` - Extracted value
- `{anchor_visual}` - Visual interpretation
- `{anchor_guideline}` - Guideline for this anchor type

## Integration with Image Generation

Combine with the image generation services for a complete workflow:

```php
use Xmon\AiContentBundle\Service\ImageSubjectGenerator;
use Xmon\AiContentBundle\Service\PromptBuilder;
use Xmon\AiContentBundle\Service\AiImageService;

class NewsImageGenerator
{
    public function __construct(
        private readonly ImageSubjectGenerator $subjectGenerator,
        private readonly PromptBuilder $promptBuilder,
        private readonly AiImageService $aiImageService,
    ) {}

    public function generateImage(string $title, string $summary): ImageResult
    {
        // Step 1: Generate unique subject with anchor
        $subject = $this->subjectGenerator->generate($title, $summary);

        // Step 2: Build full prompt with style options
        $prompt = $this->promptBuilder->build(
            subject: $subject,
            options: ['preset' => 'sumi-e-clasico']
        );

        // Step 3: Generate image
        return $this->aiImageService->generate($prompt);
    }
}
```

## Fallback Behavior

When the anchor extraction fails or returns `GENERIC`, the service falls back to one-step generation:

```
Content: "News about aikido" (no specific elements)
  -> Anchor: GENERIC (not usable)
  -> Falls back to subject_one_step template
  -> Generates generic but appropriate subject
```

This ensures the service always produces a result, even for generic content.

## Logging

The service logs its decision process for debugging:

```
[INFO] [ImageSubjectGenerator] Anchor extracted
       type: PLACE, value: Galicia

[INFO] [ImageSubjectGenerator] Subject generated with two-step
       anchorType: PLACE, subject: A distinguished aikido master...
```

Or when falling back:

```
[INFO] [ImageSubjectGenerator] Anchor not usable, using one-step
       type: GENERIC
```

Enable the `ai` logging channel to see these messages:

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        ai:
            type: stream
            path: "%kernel.logs_dir%/ai.log"
            level: debug
            channels: ["ai"]
```

## Best Practices

1. **Provide rich summaries** - The more specific details in the summary, the better the anchor extraction
2. **Use meaningful titles** - Titles with places, names, or numbers produce stronger anchors
3. **Configure domain-specific guidelines** - Customize anchor guidelines for your content domain
4. **Monitor anchor types** - Log `getLastAnchor()` to understand what anchors your content produces
5. **Handle GENERIC gracefully** - The fallback produces acceptable results but may be less distinctive

## Related

- [Configuration Reference](../reference/configuration.md#image-subject-generator) - Full configuration options
- [Image Generation Guide](image-generation.md) - Generate images from subjects
- [Prompt Templates Guide](prompt-templates.md) - Customize prompt templates
- [Styles & Presets Guide](styles-presets.md) - Apply visual styles to generated images
