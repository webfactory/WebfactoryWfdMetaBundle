<?php

namespace Webfactory\Bundle\WfdMetaBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class WebfactoryWfdMetaExtension extends Extension {

    public function load(array $configs, ContainerBuilder $container) {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $enableRouter = false;
        $routingServicesDefinitions = array(
            $container->getDefinition($refreshingRouterId = 'webfactory.wfd_meta.refreshing_router'),
            $container->getDefinition('webfactory.inverted_route_index_factory')
        );

        foreach ($configs as $subConfig) {
            if (isset($subConfig['refresh_router_tables'])) {
                $enableRouter = true;
                foreach ($routingServicesDefinitions as $definition) {
                    $definition->addMethodCall('addWfdTableDependency', array($subConfig['refresh_router_tables']));
                }
            }
        }

        if ($enableRouter)
            $container->setAlias('router', $refreshingRouterId);
    }

}
