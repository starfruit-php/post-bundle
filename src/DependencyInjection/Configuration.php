<?php

namespace Starfruit\PostBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('starfruit_post');

         $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('target')
                    ->children()
                        ->arrayNode('class_object')
                            ->info('List of Class, using Class name as key')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('content_field')
                                        ->info('Field to paste crawled content')
                                    ->end()
                                    ->scalarNode('last_version_field')
                                        ->info('Input field to store last version')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('asset_store_path')
                    ->info('Asset path to store image, ...')
                ->end()
                ->arrayNode('content_format')
                    ->children()
                        ->arrayNode('heading')
                            ->info('List of heading format')
                                ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
