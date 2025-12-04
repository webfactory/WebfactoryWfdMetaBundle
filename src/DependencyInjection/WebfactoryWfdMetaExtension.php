<?php

/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class WebfactoryWfdMetaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $fileLocator = new FileLocator(__DIR__.'/../Resources/config');

        $phpLoader = new PhpFileLoader($container, $fileLocator);
        $phpLoader->load('services.php');

        if ($container->hasParameter('doctrine.entity_managers')) {
            $phpLoader->load('orm.php');
        }

        $yamlLoader = new YamlFileLoader($container, $fileLocator);
        $yamlLoader->load('legacy_aliases.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('webfactory_wfd_meta.expire_wfd_meta_resources', $config['always_expire_wfd_meta_resources']);
    }
}
