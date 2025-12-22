# Image Styles & Presets Guide

The bundle includes a system of image options (styles, compositions, palettes, extras) and presets that combine these options.

## Available Services

- `ImageOptionsService`: Access to styles, compositions, palettes, extras and presets
- `PromptBuilder`: Builds prompts combining subject + options

## Default Options

The bundle includes predefined options that you can override or extend:

### Artistic Styles

| Key | Label | Description |
|-----|-------|-------------|
| `sumi-e` | Sumi-e (tinta japonesa) | Traditional Japanese ink painting style |
| `watercolor` | Acuarela | Painting with soft edges |
| `oil-painting` | Óleo clásico | Oil painting style |
| `digital-art` | Arte digital | Modern digital style |
| `photography` | Fotografía artística | Professional photographic style |

### Compositions

| Key | Label |
|-----|-------|
| `centered` | Centrada |
| `rule-of-thirds` | Regla de tercios |
| `negative-space` | Espacio negativo |
| `panoramic` | Panorámica |
| `close-up` | Primer plano |

### Color Palettes

| Key | Label |
|-----|-------|
| `monochrome` | Monocromo |
| `earth-tones` | Tonos tierra |
| `japanese-traditional` | Tradicional japonés |
| `muted` | Colores apagados |
| `high-contrast` | Alto contraste |

### Extras (modifiers)

| Key | Label |
|-----|-------|
| `no-text` | Sin texto |
| `silhouettes` | Siluetas |
| `atmospheric` | Atmosférico |
| `dramatic-light` | Luz dramática |

## Predefined Presets

Presets combine options into ready-to-use configurations:

| Key | Name | Style | Composition | Palette | Extras |
|-----|------|-------|-------------|---------|--------|
| `sumi-e-clasico` | Sumi-e Clásico | sumi-e | negative-space | monochrome | no-text, silhouettes, atmospheric |
| `zen-contemplativo` | Zen Contemplativo | sumi-e | centered | muted | no-text, atmospheric |
| `fotografia-aikido` | Fotografía Aikido | photography | rule-of-thirds | muted | dramatic-light |

## Using PromptBuilder

### With preset (simple mode)

```php
use Xmon\AiContentBundle\Service\PromptBuilder;
use Xmon\AiContentBundle\Service\AiImageService;

class MyService
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder,
        private readonly AiImageService $aiImageService,
    ) {}

    public function generateImage(): ImageResult
    {
        // Build prompt with preset
        $prompt = $this->promptBuilder->build(
            subject: 'aikidoka meditating in a traditional dojo',
            options: ['preset' => 'sumi-e-clasico']
        );

        // Result:
        // "aikidoka meditating in a traditional dojo, sumi-e Japanese ink wash
        //  painting, elegant brushstrokes, traditional, generous negative space,
        //  minimalist, breathing room, monochromatic color scheme, no text, ..."

        return $this->aiImageService->generate($prompt);
    }
}
```

### With individual options (advanced mode)

```php
$prompt = $this->promptBuilder->build(
    subject: 'serene bamboo forest at dawn',
    options: [
        'style' => 'watercolor',
        'composition' => 'panoramic',
        'palette' => 'earth-tones',
        'extras' => ['atmospheric', 'no-text'],
    ]
);
```

### Preset + option override

You can use a preset as a base and override specific options:

```php
$prompt = $this->promptBuilder->build(
    subject: 'aikido seminar group photo',
    options: [
        'preset' => 'zen-contemplativo',
        'composition' => 'rule-of-thirds', // Override from preset
    ]
);
```

### Free text with custom_prompt

In addition to predefined extras (multiselect), you can add free text at the end of the prompt:

```php
$prompt = $this->promptBuilder->build(
    subject: 'aikidoka meditating in dojo',
    options: [
        'preset' => 'sumi-e-clasico',
        'extras' => ['no-text', 'atmospheric'],       // Predefined extras
        'custom_prompt' => 'cinematic 16:9 ratio, 4k resolution',  // Free text
    ]
);

// Result:
// "aikidoka meditating in dojo, sumi-e Japanese ink wash painting, ...,
//  no text, atmospheric perspective, cinematic 16:9 ratio, 4k resolution"
```

**Use cases for `custom_prompt`:**
- Input in Admin to add modifiers on the fly
- Fixed configuration in code for certain contexts
- Technical parameters (aspect ratio, resolution, etc.)

## Getting Options for UI

`ImageOptionsService` provides methods to populate selects in forms:

```php
use Xmon\AiContentBundle\Service\ImageOptionsService;

class MyAdmin
{
    public function __construct(
        private readonly ImageOptionsService $imageOptions,
    ) {}

    public function getFormChoices(): array
    {
        return [
            'styles' => $this->imageOptions->getStyles(),
            // ['sumi-e' => 'Sumi-e (tinta japonesa)', 'watercolor' => 'Acuarela', ...]

            'compositions' => $this->imageOptions->getCompositions(),
            'palettes' => $this->imageOptions->getPalettes(),
            'extras' => $this->imageOptions->getExtras(),
            'presets' => $this->imageOptions->getPresets(),
        ];
    }
}
```

## Customizing Options in Your Project

Bundle options are **merged** with yours automatically. Your options are added to the defaults, and if you use the same key you can override them.

### Add options (automatic merge)

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    image_options:
        # Add artistic styles
        styles:
            ukiyo-e:
                label: 'Ukiyo-e'
                prompt: 'ukiyo-e Japanese woodblock print style, bold outlines, flat colors'
            manga:
                label: 'Manga'
                prompt: 'manga art style, anime aesthetic, bold lines'

        # Add compositions
        compositions:
            diagonal:
                label: 'Diagonal'
                prompt: 'diagonal composition, dynamic angles, leading lines'
            symmetrical:
                label: 'Simétrica'
                prompt: 'perfect symmetry, mirror balance, architectural precision'

        # Add color palettes
        palettes:
            neon:
                label: 'Neón'
                prompt: 'neon colors, cyberpunk palette, glowing accents'
            pastel:
                label: 'Pastel'
                prompt: 'soft pastel colors, gentle hues, dreamy tones'

        # Add extras/modifiers
        extras:
            cinematic:
                label: 'Cinematográfico'
                prompt: 'cinematic lighting, movie scene, dramatic atmosphere'
            vintage:
                label: 'Vintage'
                prompt: 'vintage film look, grain, faded colors, retro aesthetic'

    # Add presets (predefined combinations)
    presets:
        dojo-moderno:
            name: 'Dojo Moderno'
            style: 'photography'
            composition: 'diagonal'
            palette: 'high-contrast'
            extras: ['cinematic', 'no-text']
        manga-action:
            name: 'Manga Acción'
            style: 'manga'
            composition: 'diagonal'
            palette: 'neon'
            extras: ['dramatic-light']
```

**Result**: All options are merged with the bundle defaults.

### Override a default

Use the same key to modify a default:

```yaml
xmon_ai_content:
    image_options:
        styles:
            # Override the bundle's photography style
            photography:
                label: 'Fotografía profesional'
                prompt: 'professional studio photography, product shot, clean background'
```

### Disable specific defaults

If you don't want to use some bundle defaults, you can disable them:

```yaml
xmon_ai_content:
    image_options:
        disable_defaults:
            styles: ['oil-painting', 'digital-art']
            compositions: ['panoramic']
            palettes: ['high-contrast']
            extras: ['silhouettes']

    # For presets, use this option at root level
    disable_preset_defaults: ['zen-contemplativo']
```

**Result**: The listed styles, compositions, etc. are removed from available options.

> **Important**: If you disable an option that is being used by a preset, that preset is automatically disabled. For example, if you disable `sumi-e`, the presets `sumi-e-clasico` and `zen-contemplativo` are also deactivated.

> **Tip**: Use `bin/console xmon:ai:debug` to see all currently configured options.

## Validation

The `PromptBuilder` validates that options exist and throws `AiProviderException` if not:

```php
try {
    $prompt = $this->promptBuilder->build('subject', [
        'preset' => 'invalid-preset'
    ]);
} catch (AiProviderException $e) {
    // "Unknown preset: invalid-preset"
}
```

## Related

- [Style Providers](style-providers.md) - Database-backed style configuration
- [Image Generation](image-generation.md) - Basic image generation
- [Admin Integration](admin-integration.md) - Sonata Admin setup
- [Configuration Reference](../reference/configuration.md) - Full YAML reference
