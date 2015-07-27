<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Definition;

class WebfactoryWfdMetaExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $config = $this->mergeConfiguration($configs);

        $this->configureRefreshingRouter($config['refresh_router'], $container, $loader);
        $this->configureRefreshingTranslator($config['refresh_translator'], $container, $loader);
    }

    protected function configureRefreshingRouter(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('routing.xml');
        $container->setAlias('router', 'webfactory_wfd_meta.refreshing_router');

        $configurator = new MetaQueryConfigurator();
        $configurator->configure($container, 'webfactory_wfd_meta.refreshing_router.meta_query', $config);
    }

    protected function configureRefreshingTranslator(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('translation.xml');
        $container->setAlias('translator', 'webfactory_wfd_meta.refreshing_translator');

        $configurator = new MetaQueryConfigurator();
        $configurator->configure($container, 'webfactory_wfd_meta.refreshing_translator.meta_query', $config);
    }

    protected function mergeConfiguration(array $configs)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['refresh_router_tables'])) {
            $config['refresh_router']['tables'] = array_merge($config['refresh_router']['tables'],
                $config['refresh_router_tables']);
            unset($config['refresh_router_tables']);
        }

        if (isset($config['refresh_translator_tables'])) {
            $config['refresh_translator']['tables'] = array_merge($config['refresh_translator']['tables'],
                $config['refresh_translator_tables']);
            unset($config['refresh_translator_tables']);
        }

        return $config;
    }

}
