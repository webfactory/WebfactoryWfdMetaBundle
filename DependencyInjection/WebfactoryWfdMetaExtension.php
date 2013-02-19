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

        $refreshingRouterId = 'webfactory.wfd_meta.refreshing_router';

        $definition = $container->getDefinition($refreshingRouterId);

        foreach ($configs as $subConfig) {
            $refreshRouterTables = (array) @$subConfig['refresh_router_tables'] ?: '*';
            $definition->addMethodCall('addWfdTableDependency', array($refreshRouterTables));
        }

        $container->setAlias('router', $refreshingRouterId);
    }

}
