<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\DependencyInjection;

use Sonata\MediaBundle\Model\MediaManagerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Xmon\AiContentBundle\Provider\Image\PollinationsImageProvider;
use Xmon\AiContentBundle\Service\MediaStorageService;

class XmonAiContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        // Always load core services
        $loader->load('services.yaml');

        // Load SonataMedia integration only if available
        if ($this->isSonataMediaAvailable()) {
            $loader->load('services_media.yaml');
            $this->configureMediaStorage($container, $config['media'] ?? []);
        }

        // Set image provider configuration
        $this->configureImageProviders($container, $config['image'] ?? []);
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

    public function getAlias(): string
    {
        return 'xmon_ai_content';
    }
}
