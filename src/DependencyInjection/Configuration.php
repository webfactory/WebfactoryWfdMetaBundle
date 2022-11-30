<?php

namespace Webfactory\Bundle\WfdMetaBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('webfactory_wfd_meta');

        $treeBuilder->getRootNode()
            ->children()
            ->booleanNode('always_expire_wfd_meta_resources')
            ->defaultFalse()
            ->info('When set to "true", ConfigCache instances that depend on "\Webfactory\Bundle\WfdMetaBundle\Config\WfdMetaResource" will be refreshed every time; useful during functional tests to reload routes etc.');

        return $treeBuilder;
    }
}
