<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('webfactory_wfd_meta');

        $rootNode
            ->children()

            ->arrayNode('refresh_router_tables')
                ->info("Deprecated - list of table names or IDs that must re-generate the router when changed.")
                ->prototype('scalar')->end()
            ->end()

            ->arrayNode('refresh_translator_tables')
            ->info("Deprecated - list of table names or IDs that must re-generate the translator when changed.")
                ->prototype("scalar")->end()
            ->end()

            ->arrayNode('refresh_router')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('tables')
                        ->info("List of table names or IDs the routing depends on")
                        ->prototype('scalar')->end()
                        ->defaultValue(array())
                    ->end()
                    ->arrayNode('entities')
                        ->info("List of Doctrine entity classes the routing depends on")
                        ->prototype('scalar')->end()
                        ->defaultValue(array())
                    ->end()
                ->end()
            ->end()

            ->arrayNode('refresh_translator')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('tables')
                        ->info("List of table names or IDs the translator depends on")
                        ->prototype('scalar')->end()
                        ->defaultValue(array())
                    ->end()
                    ->arrayNode('entities')
                        ->info("List of Doctrine entity classes the translator depends on")
                        ->prototype('scalar')->end()
                        ->defaultValue(array())
                    ->end()
                ->end()
            ->end()

        ->end();

        return $treeBuilder;
    }
}
