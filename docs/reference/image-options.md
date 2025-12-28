# Image Options Configuration

This document explains how to configure image generation options (styles, compositions, palettes, extras, and presets) in your project.

## Overview

The bundle provides a flexible configuration system that allows you to:

1. Use bundle defaults out of the box
2. Add your own custom options
3. Override bundle defaults by using the same key
4. Disable specific bundle defaults
5. Group options for better organization in the UI

## Configuration Structure

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    image_options:
        # Disable specific bundle defaults
        disable_defaults:
            styles: ['style-key-to-disable']
            compositions: []
            palettes: []
            extras: []

        # Your custom styles (merged with bundle defaults)
        styles:
            my-style:
                label: 'My Custom Style'
                prompt: 'custom style prompt for image generation'
                group: 'My Group'  # Optional: group for UI organization

        # Your custom compositions
        compositions:
            my-composition:
                label: 'My Composition'
                prompt: 'composition prompt'
                group: 'Custom'

        # Your custom palettes
        palettes:
            my-palette:
                label: 'My Palette'
                prompt: 'color palette prompt'
                group: 'Custom'

        # Your custom extras
        extras:
            my-extra:
                label: 'My Extra'
                prompt: 'extra modifier prompt'
                group: 'Custom'

    # Presets: predefined combinations
    presets:
        my-preset:
            name: 'My Preset Name'
            style: 'my-style'          # Key of a style
            composition: 'my-composition'
            palette: 'my-palette'
            extras: ['my-extra']

    # Disable specific bundle presets
    disable_preset_defaults: ['preset-key-to-disable']
```

## Option Fields

Each style, composition, palette, or extra has these fields:

| Field | Required | Description |
|-------|----------|-------------|
| `label` | Yes | Display name shown in the UI |
| `prompt` | Yes | Text fragment used in image generation prompts |
| `group` | No | Group name for organizing options in select dropdowns |

## Grouping Options

The `group` field allows you to organize options into logical categories. When groups are defined, the UI will display options using `<optgroup>` tags:

```yaml
xmon_ai_content:
    image_options:
        styles:
            # Traditional styles
            sumi-e:
                label: 'Sumi-e (Japanese ink)'
                prompt: 'sumi-e Japanese ink wash painting style'
                group: 'Traditional Art'
            ukiyo-e:
                label: 'Ukiyo-e (Japanese woodblock)'
                prompt: 'ukiyo-e Japanese woodblock print style'
                group: 'Traditional Art'

            # Modern styles
            minimalist:
                label: 'Minimalist'
                prompt: 'minimalist clean modern design'
                group: 'Modern'
            abstract:
                label: 'Abstract'
                prompt: 'abstract artistic style'
                group: 'Modern'
```

This will render in the select as:

```html
<select>
    <optgroup label="Traditional Art">
        <option value="sumi-e">Sumi-e (Japanese ink)</option>
        <option value="ukiyo-e">Ukiyo-e (Japanese woodblock)</option>
    </optgroup>
    <optgroup label="Modern">
        <option value="minimalist">Minimalist</option>
        <option value="abstract">Abstract</option>
    </optgroup>
</select>
```

Options without a `group` field will be placed in a "General" group.

## Override vs Add

- **Add new option**: Use a unique key that doesn't exist in bundle defaults
- **Override bundle option**: Use the same key as a bundle default - your config wins
- **Disable bundle option**: Add the key to `disable_defaults`

Example:

```yaml
xmon_ai_content:
    image_options:
        # Disable a bundle default
        disable_defaults:
            styles: ['baroque']  # Remove baroque style from bundle

        # Override an existing bundle style
        styles:
            impressionist:  # Same key as bundle = override
                label: 'My Impressionist'
                prompt: 'my custom impressionist prompt'
                group: 'Overridden'

            # Add a completely new style
            my-new-style:
                label: 'Brand New Style'
                prompt: 'new style prompt'
                group: 'Custom'
```

## Using in Sonata Admin

The `AiStyleConfigType` form type automatically loads options from the configuration. You don't need to pass options manually:

```php
use Xmon\AiContentBundle\Form\AiStyleConfigType;

protected function configureFormFields(FormMapper $form): void
{
    $form
        ->tab('AI')
            ->with('AI Image Generation')
                ->add('aiStyleConfig', AiStyleConfigType::class, [
                    // Options are loaded automatically from ImageOptionsService
                    // You only need to customize labels if desired:
                    'mode_label' => 'Configuration Mode',
                    'preset_label' => 'Style Preset',
                    'artistic_label' => 'Artistic Style',
                    'composition_label' => 'Composition',
                    'palette_label' => 'Color Palette',
                ])
            ->end()
        ->end();
}
```

## Programmatic Access

Use `ImageOptionsService` to access options programmatically:

```php
use Xmon\AiContentBundle\Service\ImageOptionsService;

class MyService
{
    public function __construct(
        private readonly ImageOptionsService $imageOptions,
    ) {}

    public function example(): void
    {
        // Get flat list (key => label)
        $styles = $this->imageOptions->getStyles();

        // Get grouped list for Symfony ChoiceType optgroup
        // Format: ['Group' => ['label' => 'prompt', ...], ...]
        // Use this when you need to STORE the prompt value
        $groupedStyles = $this->imageOptions->getStylesGrouped();

        // Get grouped list for HTML select optgroup
        // Format: ['Group' => ['key' => 'label', ...], ...]
        // Use this when you need to STORE the key and resolve prompt later
        $groupedByKey = $this->imageOptions->getStylesGroupedByKey();

        // Get full data including prompt and group
        $allData = $this->imageOptions->getAllStylesData();

        // Get prompt for a specific style
        $prompt = $this->imageOptions->getStylePrompt('sumi-e');

        // Check if style exists
        if ($this->imageOptions->hasStyle('sumi-e')) {
            // ...
        }
    }
}
```

### Grouped Methods: Two Formats

The bundle provides two different grouped formats for different use cases:

#### `getStylesGrouped()` - For Symfony ChoiceType

Returns `['Group' => ['Label' => 'prompt', ...], ...]`

Use this when:
- Building Symfony Forms with `ChoiceType`
- You want to store the **prompt** directly in your entity
- The stored value will be used as-is in image generation

```php
// In a FormType
$builder->add('style', ChoiceType::class, [
    'choices' => $this->imageOptions->getStylesGrouped(),
]);
// Entity stores: "sumi-e Japanese ink wash painting style"
```

#### `getStylesGroupedByKey()` - For HTML Selects

Returns `['Group' => ['key' => 'label', ...], ...]`

Use this when:
- Building HTML selects manually in Twig templates
- You want to store the **key** and resolve the prompt later
- You need to reference options by their configuration key

```twig
{# In a Twig template #}
<select name="style">
    {% for groupName, options in styles %}
    <optgroup label="{{ groupName }}">
        {% for key, label in options %}
        <option value="{{ key }}">{{ label }}</option>
        {% endfor %}
    </optgroup>
    {% endfor %}
</select>
{# Select value is: "sumi-e" (the key) #}
```

Then resolve the prompt in your controller/service:
```php
$prompt = $this->imageOptions->getStylePrompt($selectedKey);
```

## Available Methods

### ImageOptionsService

| Method | Returns | Description |
|--------|---------|-------------|
| `getStyles()` | `array<string, string>` | Flat list: key => label |
| `getStylesGrouped($defaultGroup)` | `array<string, array>` | Grouped for ChoiceType: `[group => [label => prompt]]` |
| `getStylesGroupedByKey($defaultGroup)` | `array<string, array>` | Grouped for HTML selects: `[group => [key => label]]` |
| `getAllStylesData()` | `array` | Full data with label, prompt, group |
| `getStylePrompt($key)` | `?string` | Get prompt for a style |
| `getStyleData($key)` | `?array` | Get full data for a style |
| `hasStyle($key)` | `bool` | Check if style exists |
| `getStyleSuffix()` | `string` | Get the fixed style suffix from configuration |

Same methods exist for: `Compositions`, `Palettes`, `Extras`, and `Presets`.

### Style Suffix

The `style_suffix` configuration option defines fixed restrictions/modifiers appended to ALL generated styles:

```yaml
xmon_ai_content:
    style_suffix: 'no text, no letters, silhouette figures only, professional quality'
```

Access it programmatically:

```php
$suffix = $this->imageOptions->getStyleSuffix();
// Returns: "no text, no letters, silhouette figures only, professional quality"
```

The `AiStyleConfigType` form automatically loads this value via normalizer, so forms using the bundle will have access to the configured suffix without manual injection.

### Preset-Specific Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getPresetsForForm()` | `array` | Presets with resolved prompts for form integration |
| `getResolvedPreset($key)` | `?array` | Single preset with resolved prompts |
| `getPresetChoices()` | `array` | Presets as label => key for ChoiceType |
| `buildStyleFromPreset($key, $suffix, $additional)` | `?string` | Complete style string from preset |

#### getPresetsForForm()

Returns presets with resolved style/composition/palette prompts. Used by `AiStyleConfigType` and `AiStyleConfigurableTrait::buildStylePreview()`.

**Return format (English keys):**

```php
[
    'preset-key' => [
        'name' => 'Preset Name',
        'description' => 'Optional description',
        'style' => 'resolved style prompt',
        'composition' => 'resolved composition prompt',
        'palette' => 'resolved palette prompt',
    ],
]
```

#### getResolvedPreset()

Returns a single preset with resolved prompts:

```php
$preset = $imageOptions->getResolvedPreset('sumi-e-clasico');
// Returns:
// [
//     'name' => 'Sumi-e Clasico',
//     'description' => null,
//     'style' => 'sumi-e Japanese ink wash painting style',
//     'composition' => 'minimalist elegant composition',
//     'palette' => 'black white and dark crimson red color palette',
// ]
```
