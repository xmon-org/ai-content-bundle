<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\DependencyInjection;

use Sonata\MediaBundle\Model\MediaManagerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Xmon\AiContentBundle\Provider\Image\PollinationsImageProvider;
use Xmon\AiContentBundle\Provider\Text\GeminiTextProvider;
use Xmon\AiContentBundle\Provider\Text\OpenRouterTextProvider;
use Xmon\AiContentBundle\Provider\Text\PollinationsTextProvider;
use Xmon\AiContentBundle\Service\AiTextService;
use Xmon\AiContentBundle\Service\ImageOptionsService;
use Xmon\AiContentBundle\Service\MediaStorageService;

class XmonAiContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        // Always load core services (image providers)
        $loader->load('services.yaml');

        // Load text providers
        $loader->load('services_text.yaml');

        // Load SonataMedia integration only if available
        if ($this->isSonataMediaAvailable()) {
            $loader->load('services_media.yaml');
            $this->configureMediaStorage($container, $config['media'] ?? []);
        }

        // Configure providers
        $this->configureImageProviders($container, $config['image'] ?? []);
        $this->configureTextProviders($container, $config['text'] ?? []);

        // Configure image options (styles, compositions, palettes, extras, presets)
        $this->configureImageOptions(
            $container,
            $config['image_options'] ?? [],
            $config['presets'] ?? [],
            $config['disable_preset_defaults'] ?? []
        );
    }

    /**
     * Check if SonataMediaBundle is installed and available
     */
    private function isSonataMediaAvailable(): bool
    {
        return interface_exists(MediaManagerInterface::class);
    }

    private function configureImageProviders(ContainerBuilder $container, array $imageConfig): void
    {
        $providers = $imageConfig['providers'] ?? [];
        $defaults = $imageConfig['defaults'] ?? [];

        // Configure Pollinations image provider if enabled
        if (isset($providers['pollinations']) && $providers['pollinations']['enabled']) {
            $pollinationsConfig = $providers['pollinations'];

            if ($container->hasDefinition(PollinationsImageProvider::class)) {
                $definition = $container->getDefinition(PollinationsImageProvider::class);
                $definition->setArgument('$apiKey', $pollinationsConfig['api_key'] ?? null);
                $definition->setArgument('$model', $pollinationsConfig['model'] ?? 'flux');
                $definition->setArgument('$timeout', $pollinationsConfig['timeout'] ?? 120);
                $definition->setArgument('$defaultWidth', $defaults['width'] ?? 1280);
                $definition->setArgument('$defaultHeight', $defaults['height'] ?? 720);
            }
        }

        // Store config
        $container->setParameter('xmon_ai_content.image.providers', $providers);
        $container->setParameter('xmon_ai_content.image.defaults', $defaults);
    }

    private function configureTextProviders(ContainerBuilder $container, array $textConfig): void
    {
        $providers = $textConfig['providers'] ?? [];
        $defaults = $textConfig['defaults'] ?? [];

        // Provider class mapping
        $providerClasses = [
            'gemini' => GeminiTextProvider::class,
            'openrouter' => OpenRouterTextProvider::class,
            'pollinations' => PollinationsTextProvider::class,
        ];

        // Default values per provider
        $providerDefaults = [
            'gemini' => ['model' => 'gemini-2.0-flash-lite', 'timeout' => 30, 'priority' => 100],
            'openrouter' => ['model' => 'google/gemini-2.0-flash-exp:free', 'timeout' => 90, 'priority' => 50],
            'pollinations' => ['model' => 'openai', 'timeout' => 60, 'priority' => 10],
        ];

        // Configure each known provider with unified schema
        foreach ($providerClasses as $name => $class) {
            if (!isset($providers[$name]) || !$providers[$name]['enabled']) {
                continue;
            }

            if (!$container->hasDefinition($class)) {
                continue;
            }

            $config = $providers[$name];
            $providerDefault = $providerDefaults[$name];
            $definition = $container->getDefinition($class);

            // Common fields for all providers (unified schema)
            if (isset($config['api_key'])) {
                $definition->setArgument('$apiKey', $config['api_key']);
            }
            $definition->setArgument('$model', $config['model'] ?? $providerDefault['model']);
            $definition->setArgument('$fallbackModels', $config['fallback_models'] ?? []);
            $definition->setArgument('$timeout', $config['timeout'] ?? $providerDefault['timeout']);
            $definition->setArgument('$priority', $config['priority'] ?? $providerDefault['priority']);
        }

        // Configure AiTextService with defaults
        if ($container->hasDefinition(AiTextService::class)) {
            $definition = $container->getDefinition(AiTextService::class);
            $definition->setArgument('$retries', $defaults['retries'] ?? 2);
            $definition->setArgument('$retryDelay', $defaults['retry_delay'] ?? 3);
        }

        // Store config
        $container->setParameter('xmon_ai_content.text.providers', $providers);
        $container->setParameter('xmon_ai_content.text.defaults', $defaults);
    }

    private function configureMediaStorage(ContainerBuilder $container, array $mediaConfig): void
    {
        $defaultContext = $mediaConfig['default_context'] ?? 'default';
        $providerName = $mediaConfig['provider'] ?? 'sonata.media.provider.image';

        if ($container->hasDefinition(MediaStorageService::class)) {
            $definition = $container->getDefinition(MediaStorageService::class);
            $definition->setArgument('$defaultContext', $defaultContext);
            $definition->setArgument('$providerName', $providerName);
        }

        // Store parameters
        $container->setParameter('xmon_ai_content.media.default_context', $defaultContext);
        $container->setParameter('xmon_ai_content.media.provider', $providerName);
    }

    private function configureImageOptions(ContainerBuilder $container, array $imageOptions, array $userPresets, array $disablePresetDefaults): void
    {
        // Get disable lists
        $disableDefaults = $imageOptions['disable_defaults'] ?? [];
        $disableStyles = $disableDefaults['styles'] ?? [];
        $disableCompositions = $disableDefaults['compositions'] ?? [];
        $disablePalettes = $disableDefaults['palettes'] ?? [];
        $disableExtras = $disableDefaults['extras'] ?? [];

        // Bundle defaults - user config is MERGED with these (user wins on conflict)
        $defaultStyles = [
            'sumi_e' => [
                'label' => 'Sumi-e (tinta japonesa)',
                'prompt' => 'sumi-e Japanese ink wash painting, elegant brushstrokes, traditional',
            ],
            'watercolor' => [
                'label' => 'Acuarela',
                'prompt' => 'delicate watercolor painting, soft edges, flowing pigments',
            ],
            'oil_painting' => [
                'label' => 'Óleo clásico',
                'prompt' => 'classical oil painting style, rich textures, masterful brushwork',
            ],
            'digital_art' => [
                'label' => 'Arte digital',
                'prompt' => 'modern digital art, clean lines, vibrant rendering',
            ],
            'photography' => [
                'label' => 'Fotografía artística',
                'prompt' => 'professional photography, dramatic lighting, shallow depth of field',
            ],
        ];

        $defaultCompositions = [
            'centered' => [
                'label' => 'Centrada',
                'prompt' => 'centered subject, symmetrical balance, clear focal point',
            ],
            'rule_of_thirds' => [
                'label' => 'Regla de tercios',
                'prompt' => 'rule of thirds composition, dynamic placement, visual flow',
            ],
            'negative_space' => [
                'label' => 'Espacio negativo',
                'prompt' => 'generous negative space, minimalist, breathing room',
            ],
            'panoramic' => [
                'label' => 'Panorámica',
                'prompt' => 'wide panoramic view, expansive scene, horizontal emphasis',
            ],
            'close_up' => [
                'label' => 'Primer plano',
                'prompt' => 'intimate close-up, detail focus, cropped composition',
            ],
        ];

        $defaultPalettes = [
            'monochrome' => [
                'label' => 'Monocromo',
                'prompt' => 'monochromatic color scheme, single hue variations, elegant simplicity',
            ],
            'earth_tones' => [
                'label' => 'Tonos tierra',
                'prompt' => 'warm earth tones, browns, ochres, natural organic colors',
            ],
            'japanese_traditional' => [
                'label' => 'Tradicional japonés',
                'prompt' => 'traditional Japanese palette, indigo, vermillion, gold leaf accents',
            ],
            'muted' => [
                'label' => 'Colores apagados',
                'prompt' => 'muted desaturated colors, soft tones, subtle elegance',
            ],
            'high_contrast' => [
                'label' => 'Alto contraste',
                'prompt' => 'high contrast, bold blacks and whites, dramatic tonal range',
            ],
        ];

        $defaultExtras = [
            'no_text' => [
                'label' => 'Sin texto',
                'prompt' => 'no text, no letters, no words, no typography',
            ],
            'silhouettes' => [
                'label' => 'Siluetas',
                'prompt' => 'silhouette figures, no detailed faces, anonymous forms',
            ],
            'atmospheric' => [
                'label' => 'Atmosférico',
                'prompt' => 'atmospheric perspective, misty, ethereal quality',
            ],
            'dramatic_light' => [
                'label' => 'Luz dramática',
                'prompt' => 'dramatic lighting, chiaroscuro, strong shadows',
            ],
        ];

        $defaultPresets = [
            'sumi_e_clasico' => [
                'name' => 'Sumi-e Clásico',
                'style' => 'sumi_e',
                'composition' => 'negative_space',
                'palette' => 'monochrome',
                'extras' => ['no_text', 'silhouettes', 'atmospheric'],
            ],
            'zen_contemplativo' => [
                'name' => 'Zen Contemplativo',
                'style' => 'sumi_e',
                'composition' => 'centered',
                'palette' => 'muted',
                'extras' => ['no_text', 'atmospheric'],
            ],
            'fotografia_aikido' => [
                'name' => 'Fotografía Aikido',
                'style' => 'photography',
                'composition' => 'rule_of_thirds',
                'palette' => 'muted',
                'extras' => ['dramatic_light'],
            ],
        ];

        // Filter out disabled defaults
        $defaultStyles = array_diff_key($defaultStyles, array_flip($disableStyles));
        $defaultCompositions = array_diff_key($defaultCompositions, array_flip($disableCompositions));
        $defaultPalettes = array_diff_key($defaultPalettes, array_flip($disablePalettes));
        $defaultExtras = array_diff_key($defaultExtras, array_flip($disableExtras));
        $defaultPresets = array_diff_key($defaultPresets, array_flip($disablePresetDefaults));

        // Merge: filtered defaults first, then user config (user wins on same key)
        $styles = array_merge($defaultStyles, $imageOptions['styles'] ?? []);
        $compositions = array_merge($defaultCompositions, $imageOptions['compositions'] ?? []);
        $palettes = array_merge($defaultPalettes, $imageOptions['palettes'] ?? []);
        $extras = array_merge($defaultExtras, $imageOptions['extras'] ?? []);
        $presets = array_merge($defaultPresets, $userPresets);

        // Filter out presets that reference disabled options
        $presets = $this->filterBrokenPresets($presets, $styles, $compositions, $palettes, $extras);

        // Configure ImageOptionsService
        if ($container->hasDefinition(ImageOptionsService::class)) {
            $definition = $container->getDefinition(ImageOptionsService::class);
            $definition->setArgument('$styles', $styles);
            $definition->setArgument('$compositions', $compositions);
            $definition->setArgument('$palettes', $palettes);
            $definition->setArgument('$extras', $extras);
            $definition->setArgument('$presets', $presets);
        }

        // Store parameters for external access
        $container->setParameter('xmon_ai_content.image_options.styles', $styles);
        $container->setParameter('xmon_ai_content.image_options.compositions', $compositions);
        $container->setParameter('xmon_ai_content.image_options.palettes', $palettes);
        $container->setParameter('xmon_ai_content.image_options.extras', $extras);
        $container->setParameter('xmon_ai_content.presets', $presets);
    }

    public function getAlias(): string
    {
        return 'xmon_ai_content';
    }

    /**
     * Filter out presets that reference non-existent options (due to disabled defaults)
     */
    private function filterBrokenPresets(array $presets, array $styles, array $compositions, array $palettes, array $extras): array
    {
        return array_filter($presets, function (array $preset) use ($styles, $compositions, $palettes, $extras): bool {
            // Check if style exists (if specified)
            if (!empty($preset['style']) && !isset($styles[$preset['style']])) {
                return false;
            }

            // Check if composition exists (if specified)
            if (!empty($preset['composition']) && !isset($compositions[$preset['composition']])) {
                return false;
            }

            // Check if palette exists (if specified)
            if (!empty($preset['palette']) && !isset($palettes[$preset['palette']])) {
                return false;
            }

            // Check if all extras exist (if specified)
            foreach ($preset['extras'] ?? [] as $extra) {
                if (!isset($extras[$extra])) {
                    return false;
                }
            }

            return true;
        });
    }
}
