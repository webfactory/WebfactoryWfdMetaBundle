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
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class WebfactoryWfdMetaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');

        $xmlLoader = new XmlFileLoader($container, $fileLocator);
        $xmlLoader->load('services.xml');

        $yamlLoader = new YamlFileLoader($container, $fileLocator);
        $yamlLoader->load('legacy_aliases.yml');
    }
}
