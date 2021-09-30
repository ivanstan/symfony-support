<?php

namespace Ivanstan\SymfonyRest\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_CONFIG = 'symfony_rest';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('symfony_rest');

        $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('exception_subscriber')
                ->children()
                    ->arrayNode('paths')->scalarPrototype()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}