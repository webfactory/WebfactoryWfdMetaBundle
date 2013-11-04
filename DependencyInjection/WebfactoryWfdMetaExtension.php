<?php

namespace Webfactory\Bundle\WfdMetaBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Definition;

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

    /**
     * Adds addWfdTableDependency-method calls for the array-fied values of the $configKey in the $configs to the
     * $definition. If no such value has been found in any of the $configurations, a addWfdTableDependency-method call
     * for '*' is added, which will be interpreted as a wildcard for all tables later on.
     *
     * @param array $configs
     * @param Definition $definition
     * @param string $configKey
     */
    protected function addWfdTableDependencies(array $configs, Definition $definition, $configKey) {
        $tables = array();
        foreach ($configs as $subConfig) {
            if (isset($subConfig[$configKey])) {
                $tables += (array) $subConfig[$configKey];
            }
        }

        if (empty($tables)) {
            $tables = array('*');
        }

        $definition->addMethodCall('addWfdTableDependency', array($tables));
    }

}
