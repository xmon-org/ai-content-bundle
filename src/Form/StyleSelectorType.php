<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\PromptBuilder;

/**
 * Form type for selecting AI image generation style.
 *
 * Provides three modes:
 * - Global: Use the default style from bundle configuration
 * - Preset: Use a predefined preset
 * - Custom: Build style from individual components (style, composition, palette, extra)
 *
 * Usage in Sonata Admin:
 *
 *     protected function configureFormFields(FormMapper $form): void
 *     {
 *         $form->add('styleSelector', StyleSelectorType::class, [
 *             'label' => 'Image Style',
 *             'default_mode' => 'global',
 *             'show_preview' => true,
 *         ]);
 *     }
 *
 * The form data is an array with keys: mode, preset, style, composition, palette, extra
 */
class StyleSelectorType extends AbstractType
{
    public function __construct(
        private readonly ImageOptionsService $imageOptionsService,
        private readonly PromptBuilder $promptBuilder,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Mode selector
        $builder->add('mode', ChoiceType::class, [
            'label' => false,
            'choices' => [
                'Use global style' => 'global',
                'Use preset' => 'preset',
                'Custom style' => 'custom',
            ],
            'expanded' => true,
            'data' => $options['default_mode'],
            'attr' => [
                'class' => 'xmon-ai-style-mode',
            ],
        ]);

        // Preset selector (visible when mode = preset)
        $presets = $this->imageOptionsService->getPresets();
        if (!empty($presets)) {
            $presetChoices = array_flip($presets);
            $builder->add('preset', ChoiceType::class, [
                'label' => 'Preset',
                'choices' => $presetChoices,
                'required' => false,
                'placeholder' => 'Select a preset...',
                'attr' => [
                    'class' => 'xmon-ai-style-preset',
                ],
            ]);
        }

        // Custom style fields (visible when mode = custom)
        $styles = $this->imageOptionsService->getStyles();
        if (!empty($styles)) {
            $builder->add('style', ChoiceType::class, [
                'label' => 'Style',
                'choices' => array_flip($styles),
                'required' => false,
                'placeholder' => 'Select style...',
                'attr' => [
                    'class' => 'xmon-ai-style-style',
                ],
            ]);
        }

        $compositions = $this->imageOptionsService->getCompositions();
        if (!empty($compositions)) {
            $builder->add('composition', ChoiceType::class, [
                'label' => 'Composition',
                'choices' => array_flip($compositions),
                'required' => false,
                'placeholder' => 'Select composition...',
                'attr' => [
                    'class' => 'xmon-ai-style-composition',
                ],
            ]);
        }

        $palettes = $this->imageOptionsService->getPalettes();
        if (!empty($palettes)) {
            $builder->add('palette', ChoiceType::class, [
                'label' => 'Palette',
                'choices' => array_flip($palettes),
                'required' => false,
                'placeholder' => 'Select palette...',
                'attr' => [
                    'class' => 'xmon-ai-style-palette',
                ],
            ]);
        }

        // Extra text field for additional style instructions
        $builder->add('extra', TextType::class, [
            'label' => 'Extra instructions',
            'required' => false,
            'attr' => [
                'class' => 'xmon-ai-style-extra',
                'placeholder' => 'Additional style instructions...',
            ],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Pass options to template
        $view->vars['show_preview'] = $options['show_preview'];
        $view->vars['preview_endpoint'] = $options['preview_endpoint'];
        $view->vars['default_mode'] = $options['default_mode'];
        $view->vars['template'] = $options['template'];

        // Pass image options for JavaScript
        $view->vars['styles_data'] = $this->getOptionsWithPrompts('styles');
        $view->vars['compositions_data'] = $this->getOptionsWithPrompts('compositions');
        $view->vars['palettes_data'] = $this->getOptionsWithPrompts('palettes');
        $view->vars['presets_data'] = $this->getPresetsData();

        // Global style preview
        $view->vars['global_style_preview'] = $this->promptBuilder->buildGlobalStyle();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Default style mode
            'default_mode' => 'global',

            // Show style preview
            'show_preview' => true,

            // Endpoint for live preview (optional)
            'preview_endpoint' => null,

            // Template for rendering
            'template' => '@XmonAiContent/admin/form/style_selector.html.twig',

            // Form data type
            'data_class' => null,
            'compound' => true,
        ]);

        $resolver->setAllowedValues('default_mode', ['global', 'preset', 'custom']);
        $resolver->setAllowedTypes('show_preview', 'bool');
        $resolver->setAllowedTypes('preview_endpoint', ['null', 'string']);
        $resolver->setAllowedTypes('template', 'string');
    }

    public function getBlockPrefix(): string
    {
        return 'xmon_ai_style';
    }

    /**
     * Get options with their prompts for JavaScript.
     *
     * @return array<string, array{label: string, prompt: string}>
     */
    private function getOptionsWithPrompts(string $type): array
    {
        $result = [];
        $methodMap = [
            'styles' => 'getStyleData',
            'compositions' => 'getCompositionData',
            'palettes' => 'getPaletteData',
        ];

        $labelsMethod = match ($type) {
            'styles' => 'getStyles',
            'compositions' => 'getCompositions',
            'palettes' => 'getPalettes',
            default => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        $dataMethod = $methodMap[$type];

        foreach ($this->imageOptionsService->$labelsMethod() as $key => $label) {
            $data = $this->imageOptionsService->$dataMethod($key);
            $result[$key] = [
                'label' => $label,
                'prompt' => $data['prompt'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Get presets data for JavaScript.
     *
     * @return array<string, array{name: string, style: ?string, composition: ?string, palette: ?string, extras: string[], preview: string}>
     */
    private function getPresetsData(): array
    {
        $result = [];

        foreach ($this->imageOptionsService->getPresets() as $key => $name) {
            $preset = $this->imageOptionsService->getPreset($key);
            if ($preset) {
                $result[$key] = [
                    'name' => $preset['name'],
                    'style' => $preset['style'],
                    'composition' => $preset['composition'],
                    'palette' => $preset['palette'],
                    'extras' => $preset['extras'],
                    'preview' => $this->promptBuilder->buildFromPreset($key),
                ];
            }
        }

        return $result;
    }
}
