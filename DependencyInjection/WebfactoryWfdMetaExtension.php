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

        $this->configureRefreshingRouter($configs, $container, $loader);
        $this->configureRefreshingTranslator($configs, $container, $loader);
    }

    protected function configureRefreshingRouter(array $configs, ContainerBuilder $container, XmlFileLoader $loader) {
        $loader->load('routing.xml');

        $serviceId = 'webfactory.wfd_meta.refreshing_router';
        $this->addWfdTableDependencies($configs, $container->getDefinition($serviceId), 'refresh_router_tables');


        $container->setAlias('router', $serviceId);
    }

    protected function configureRefreshingTranslator(array $configs, ContainerBuilder $container, XmlFileLoader $loader) {
        $loader->load('translation.xml');

        $serviceId = 'webfactory.wfd_meta.refreshing_translator';
        $this->addWfdTableDependencies($configs, $container->getDefinition($serviceId), 'refreshing_translator_tables');

        $container->setAlias('translator', $serviceId);
    }

    protected function addWfdTableDependencies(array $configs, $definition, $configKey) {
        foreach ($configs as $subConfig) {
            $tables = (array) @$subConfig[$configKey] ?: '*';
            $definition->addMethodCall('addWfdTableDependency', array($tables));
        }
    }

}
