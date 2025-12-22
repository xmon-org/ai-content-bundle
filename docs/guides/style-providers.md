# Style Providers

This guide explains how to customize the global/default AI image style using Style Providers.

## Overview

Style Providers allow you to dynamically provide the default style for AI image generation. Instead of hardcoding styles in YAML configuration, you can:

- Store style configuration in a database entity
- Allow admin users to configure styles via Sonata Admin
- Implement context-aware style selection
- Override styles per entity type

## Architecture

The bundle uses a **Strategy + Service Provider pattern**:

```
┌─────────────────────────────────────────────────────────────┐
│                    AiStyleService                            │
│         (aggregates providers by priority)                   │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ YamlStyleProvider│ │ConfigStyleProvider│ │ CustomProvider  │
│   Priority: 0   │ │   Priority: 100  │ │  Priority: 200  │
│   (fallback)    │ │   (from DB)      │ │   (custom)      │
└─────────────────┘ └─────────────────┘ └─────────────────┘
```

The provider with the highest priority that returns a non-empty style wins.

## Default Provider

The bundle includes `YamlStyleProvider` (priority 0) which uses the first preset from YAML configuration:

```yaml
# config/packages/xmon_ai_content.yaml
xmon_ai_content:
    presets:
        my-preset:
            name: 'My Style'
            style: 'sumi-e'
            composition: 'negative-space'
            palette: 'monochrome'
            extras: ['no-text']
```

## Creating a Custom Provider

### Step 1: Implement the Interface

Create a class implementing `AiStyleProviderInterface`:

```php
<?php

namespace App\Provider;

use App\Repository\ConfiguracionRepository;
use Xmon\AiContentBundle\Provider\AiStyleProviderInterface;

final class ConfiguracionStyleProvider implements AiStyleProviderInterface
{
    public function __construct(
        private readonly ConfiguracionRepository $repository,
    ) {}

    public function getGlobalStyle(): string
    {
        $config = $this->repository->getConfiguracion();

        if ($config === null) {
            return ''; // Empty = skip this provider
        }

        return $config->getBaseStylePreview();
    }

    public function getPriority(): int
    {
        return 100; // Higher than YAML (0)
    }

    public function isAvailable(): bool
    {
        return $this->repository->getConfiguracion() !== null;
    }
}
```

### Step 2: Register as Tagged Service

The interface uses `AutoconfigureTag`, so if you have autoconfigure enabled, it's automatic.

Otherwise, register manually:

```yaml
# config/services.yaml
services:
    App\Provider\ConfiguracionStyleProvider:
        tags:
            - { name: 'xmon_ai_content.style_provider', priority: 100 }
```

### Step 3: Entity Configuration (Optional)

Use the provided trait and interface for storing style config in your entity:

```php
<?php

namespace App\Entity;

use Xmon\AiContentBundle\Entity\AiStyleConfigurableInterface;
use Xmon\AiContentBundle\Entity\AiStyleConfigurableTrait;

#[ORM\Entity]
class Configuracion implements AiStyleConfigurableInterface
{
    use AiStyleConfigurableTrait;

    // Define your presets as constants
    public const PRESETS = [
        'sumi-e-clasico' => [
            'nombre' => 'Sumi-e Clásico',
            'estilo' => 'sumi-e Japanese ink wash painting style',
            'composicion' => 'minimalist elegant composition',
            'paleta' => 'black white and dark crimson red color palette',
        ],
        // ... more presets
    ];

    public const STYLES = [...];
    public const COMPOSITIONS = [...];
    public const PALETTES = [...];

    /**
     * Build the complete style from configuration.
     */
    public function getBaseStylePreview(): string
    {
        return $this->buildStylePreview(
            presets: self::PRESETS,
            suffix: 'no text, professional quality',
            artDefault: 'sumi-e style',
            compDefault: 'minimalist composition',
            palDefault: 'monochrome palette',
        );
    }
}
```

The trait provides:
- `aiStyleMode`: 'preset' or 'custom'
- `aiStylePreset`: Selected preset key
- `aiStyleArtistic`: Custom artistic style
- `aiStyleComposition`: Custom composition
- `aiStylePalette`: Custom palette
- `aiStyleAdditional`: Additional text modifier
- `buildStylePreview()`: Combines all into a prompt string

### Step 4: Admin Form (Optional)

Use `AiStyleConfigType` in Sonata Admin:

```php
protected function configureFormFields(FormMapper $form): void
{
    $form
        ->tab('AI')
            ->with('Image Style')
                ->add('aiStyleMode', ChoiceType::class, [
                    'label' => 'Mode',
                    'choices' => [
                        'Use Preset' => 'preset',
                        'Custom' => 'custom',
                    ],
                    'expanded' => true,
                ])
                ->add('aiStylePreset', ChoiceType::class, [
                    'label' => 'Preset',
                    'choices' => $this->formatPresetChoices(Configuracion::PRESETS),
                    'required' => false,
                ])
                // ... more fields
            ->end()
        ->end();
}
```

Or use the embedded form type:

```php
use Xmon\AiContentBundle\Form\AiStyleConfigType;

$form->add('styleConfig', AiStyleConfigType::class, [
    'presets' => Configuracion::PRESETS,
    'styles' => Configuracion::STYLES,
    'compositions' => Configuracion::COMPOSITIONS,
    'palettes' => Configuracion::PALETTES,
    // Customize labels (optional)
    'mode_label' => 'Modo de configuración',
    'preset_label' => 'Preset predefinido',
]);
```

## Provider Priority

| Priority | Provider | Use Case |
|----------|----------|----------|
| 0 | `YamlStyleProvider` | Fallback to YAML config |
| 100 | Database provider | Admin-configurable styles |
| 200+ | Custom providers | Context-aware, per-entity styles |

## How It Works in the Controller

The `AbstractAiImageController` automatically uses `AiStyleService`:

```php
protected function resolveGlobalStyle(): string
{
    // AiStyleService aggregates all providers
    if ($this->styleService !== null) {
        $style = $this->styleService->getGlobalStyle();
        if ($style !== '') {
            return $style;
        }
    }

    // Fallback to PromptBuilder
    return $this->promptBuilder->buildGlobalStyle();
}
```

You can still override `resolveGlobalStyle()` in your controller for custom logic.

## Debugging

Use the debug command to see registered providers:

```bash
php bin/console debug:container --tag=xmon_ai_content.style_provider
```

Or check programmatically:

```php
$info = $styleService->getProviderInfo();
// [
//     ['class' => 'App\Provider\ConfiguracionStyleProvider', 'priority' => 100, 'available' => true],
//     ['class' => 'YamlStyleProvider', 'priority' => 0, 'available' => true],
// ]
```

## Migration from Controller Override

If you were previously overriding `resolveGlobalStyle()` in your controller:

**Before (controller override):**
```php
protected function resolveGlobalStyle(): string
{
    $config = $this->configRepository->getConfiguracion();
    return $config?->getBaseStylePreview() ?? parent::resolveGlobalStyle();
}
```

**After (style provider):**

1. Create a style provider class (see Step 1)
2. Remove the override from your controller
3. Pass `AiStyleService` to the parent constructor

The provider approach is more flexible and reusable across multiple controllers.

## Related

- [Styles & Presets](styles-presets.md) - Configure styles in YAML
- [Admin Integration](admin-integration.md) - Sonata Admin setup
- [Image Generation](image-generation.md) - Generate images programmatically
