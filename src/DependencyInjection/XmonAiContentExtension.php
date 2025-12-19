<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Xmon\AiContentBundle\Provider\Image\PollinationsImageProvider;

class XmonAiContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        // Set image provider configuration
        $this->configureImageProviders($container, $config['image'] ?? []);
    }

    private function configureImageProviders(ContainerBuilder $container, array $imageConfig): void
    {
        $providers = $imageConfig['providers'] ?? [];
        $defaults = $imageConfig['defaults'] ?? [];

        // Configure Pollinations provider if enabled
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

        // Store config for AiImageService
        $container->setParameter('xmon_ai_content.image.providers', $providers);
        $container->setParameter('xmon_ai_content.image.defaults', $defaults);
    }

    public function getAlias(): string
    {
        return 'xmon_ai_content';
    }
}
