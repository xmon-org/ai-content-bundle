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

    public function getAlias(): string
    {
        return 'xmon_ai_content';
    }
}
