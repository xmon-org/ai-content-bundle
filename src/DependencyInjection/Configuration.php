<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('xmon_ai_content');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                // Text providers configuration
                // Unified schema: all providers use the same fields
                ->arrayNode('text')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('providers')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->booleanNode('enabled')->defaultTrue()->end()
                                    ->integerNode('priority')->defaultValue(50)->end()
                                    ->scalarNode('api_key')->defaultNull()->end()
                                    ->scalarNode('model')->defaultNull()->end()
                                    ->arrayNode('fallback_models')
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()
                                    ->integerNode('timeout')->defaultValue(30)->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('defaults')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('retries')->defaultValue(2)->end()
                                ->integerNode('retry_delay')->defaultValue(3)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                // Image providers configuration
                ->arrayNode('image')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('providers')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->booleanNode('enabled')->defaultTrue()->end()
                                    ->integerNode('priority')->defaultValue(1)->end()
                                    ->scalarNode('api_key')->defaultNull()->end()
                                    ->scalarNode('model')->defaultNull()->end()
                                    ->integerNode('timeout')->defaultValue(120)->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('defaults')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('width')->defaultValue(1280)->end()
                                ->integerNode('height')->defaultValue(720)->end()
                                ->integerNode('retries')->defaultValue(3)->end()
                                ->integerNode('retry_delay')->defaultValue(5)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                // SonataMedia integration
                ->arrayNode('media')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_context')->defaultValue('default')->end()
                        ->scalarNode('provider')->defaultValue('sonata.media.provider.image')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
