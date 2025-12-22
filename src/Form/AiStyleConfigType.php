<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for AI style configuration.
 *
 * This form type provides fields for configuring AI image styles:
 * - Mode selector (preset vs custom)
 * - Preset selector (for preset mode)
 * - Style, Composition, Palette selectors (for custom mode)
 * - Additional text modifier
 *
 * Usage in Sonata Admin:
 *
 *     use Xmon\AiContentBundle\Form\AiStyleConfigType;
 *
 *     protected function configureFormFields(FormMapper $form): void
 *     {
 *         $form
 *             ->tab('AI')
 *                 ->with('AI Image Generation')
 *                     ->add('aiStyleConfig', AiStyleConfigType::class, [
 *                         'presets' => MyEntity::PRESETS,
 *                         'styles' => MyEntity::STYLES,
 *                         'compositions' => MyEntity::COMPOSITIONS,
 *                         'palettes' => MyEntity::PALETTES,
 *                         // Optional: customize labels (Spanish example)
 *                         'mode_label' => 'Modo de configuración',
 *                         'preset_label' => 'Preset de estilo',
 *                         // etc.
 *                     ])
 *                 ->end()
 *             ->end();
 *     }
 *
 * The form uses inherit_data=true by default, so fields map directly to
 * the parent entity's AiStyleConfigurableTrait properties.
 *
 * @see AiStyleConfigurableInterface
 * @see AiStyleConfigurableTrait
 */
class AiStyleConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('aiStyleMode', ChoiceType::class, [
                'label' => $options['mode_label'],
                'choices' => [
                    $options['preset_mode_label'] => 'preset',
                    $options['custom_mode_label'] => 'custom',
                ],
                'expanded' => true,
                'required' => true,
                'attr' => [
                    'class' => 'ai-style-mode-selector',
                ],
                'help' => $options['mode_help'],
            ])
            ->add('aiStylePreset', ChoiceType::class, [
                'label' => $options['preset_label'],
                'choices' => $this->formatPresetChoices($options['presets']),
                'required' => false,
                'placeholder' => $options['preset_placeholder'],
                'attr' => [
                    'class' => 'ai-style-preset-selector',
                ],
                'help' => $options['preset_help'],
            ])
            ->add('aiStyleArtistic', ChoiceType::class, [
                'label' => $options['artistic_label'],
                'choices' => $this->formatGroupedChoices($options['styles']),
                'required' => false,
                'placeholder' => $options['artistic_placeholder'],
                'attr' => [
                    'class' => 'ai-style-artistic-selector',
                ],
            ])
            ->add('aiStyleComposition', ChoiceType::class, [
                'label' => $options['composition_label'],
                'choices' => $this->formatGroupedChoices($options['compositions']),
                'required' => false,
                'placeholder' => $options['composition_placeholder'],
                'attr' => [
                    'class' => 'ai-style-composition-selector',
                ],
            ])
            ->add('aiStylePalette', ChoiceType::class, [
                'label' => $options['palette_label'],
                'choices' => $this->formatGroupedChoices($options['palettes']),
                'required' => false,
                'placeholder' => $options['palette_placeholder'],
                'attr' => [
                    'class' => 'ai-style-palette-selector',
                ],
            ])
            ->add('aiStyleAdditional', TextareaType::class, [
                'label' => $options['additional_label'],
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => $options['additional_placeholder'],
                    'class' => 'ai-style-additional-text',
                ],
                'help' => $options['additional_help'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Inherit data to map fields directly to parent entity
            // This is essential for Sonata Admin embedded forms
            'inherit_data' => true,

            // Hide the container label (redundant with Sonata's box title)
            'label' => false,

            // Options data
            'presets' => [],
            'styles' => [],
            'compositions' => [],
            'palettes' => [],

            // Labels
            'mode_label' => 'Configuration Mode',
            'preset_mode_label' => 'Use Preset',
            'custom_mode_label' => 'Custom Configuration',
            'preset_label' => 'Preset',
            'artistic_label' => 'Artistic Style',
            'composition_label' => 'Composition',
            'palette_label' => 'Color Palette',
            'additional_label' => 'Additional Text',

            // Placeholders
            'preset_placeholder' => 'Select a preset...',
            'artistic_placeholder' => 'Select a style...',
            'composition_placeholder' => 'Select a composition...',
            'palette_placeholder' => 'Select a palette...',
            'additional_placeholder' => 'Additional instructions for the image generation...',

            // Help texts
            'mode_help' => null,
            'preset_help' => null,
            'additional_help' => null,

            // Preview options
            'show_preview' => true,
            'preview_label' => 'Style Preview',
            'suffix' => '',
            'default_artistic' => null,
            'default_composition' => null,
            'default_palette' => null,
        ]);

        $resolver->setAllowedTypes('presets', 'array');
        $resolver->setAllowedTypes('styles', 'array');
        $resolver->setAllowedTypes('compositions', 'array');
        $resolver->setAllowedTypes('palettes', 'array');
        $resolver->setAllowedTypes('show_preview', 'bool');
        $resolver->setAllowedTypes('preview_label', 'string');
        $resolver->setAllowedTypes('suffix', 'string');
        $resolver->setAllowedTypes('default_artistic', ['null', 'string']);
        $resolver->setAllowedTypes('default_composition', ['null', 'string']);
        $resolver->setAllowedTypes('default_palette', ['null', 'string']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Pass presets data as JSON for JavaScript dynamic preview and descriptions
        $presetsData = [];
        foreach ($options['presets'] as $key => $data) {
            $presetsData[$key] = [
                'name' => $data['nombre'] ?? $key,
                'estilo' => $data['estilo'] ?? '',
                'composicion' => $data['composicion'] ?? '',
                'paleta' => $data['paleta'] ?? '',
                'descripcion' => $data['descripcion'] ?? null,
            ];
        }

        $view->vars['show_preview'] = $options['show_preview'];
        $view->vars['preview_label'] = $options['preview_label'];
        $view->vars['suffix'] = $options['suffix'];
        $view->vars['default_artistic'] = $options['default_artistic'] ?? '';
        $view->vars['default_composition'] = $options['default_composition'] ?? '';
        $view->vars['default_palette'] = $options['default_palette'] ?? '';
        $view->vars['presets_json'] = json_encode($presetsData, \JSON_THROW_ON_ERROR);
    }

    public function getBlockPrefix(): string
    {
        return 'xmon_ai_style_config';
    }

    /**
     * Format preset choices for ChoiceType.
     *
     * Expected input format:
     *     ['preset-key' => ['nombre' => 'Display Name', ...], ...]
     *
     * Output format:
     *     ['Display Name' => 'preset-key', ...]
     *
     * @param array<string, array{nombre: string}> $presets
     *
     * @return array<string, string>
     */
    private function formatPresetChoices(array $presets): array
    {
        $choices = [];
        foreach ($presets as $key => $data) {
            $label = $data['nombre'] ?? $key;
            $choices[$label] = $key;
        }

        return $choices;
    }

    /**
     * Format grouped choices for ChoiceType.
     *
     * Expected input format (grouped):
     *     ['Group Name' => ['Label' => 'value', ...], ...]
     *
     * Or flat format:
     *     ['Label' => 'value', ...]
     *
     * @param array<string, array<string, string>|string> $options
     *
     * @return array<string, array<string, string>|string>
     */
    private function formatGroupedChoices(array $options): array
    {
        // Check if it's already in grouped format
        $firstValue = reset($options);
        if (\is_array($firstValue)) {
            // Already grouped, return as-is
            return $options;
        }

        // Flat format, return as-is
        return $options;
    }
}
