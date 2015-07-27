<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Helper class to re-configure a MetaQuery instance in the DIC,
 * see {@see MetaQueryConfigurator::configure()}.
 */
class MetaQueryConfigurator
{
    /**
     * In the given ContainerBuilder, reconfigure the MetaQuery service instance
     * pointed to by $serviceId to track changes in the tables given in $config.
     *
     * $config is an array that can contain two keys at the top level: "tables" and
     * "entities".
     *
     * Below those, a list (an array) of entries contains the table names and Doctrine
     * ORM entity names respectively that are to be tracked.
     *
     * This $config structure matches the configuration structure for the bundle.
     *
     * @param array $config The tables and entities to track as previously described.
     * @return void
     */
    public function configure(ContainerBuilder $container, $serviceId, array $config)
    {
        /** @var $definition Definition */
        $definition = $container->getDefinition($serviceId);

        if ($config['tables']) {
            $definition->addMethodCall('addTable', array($config['tables']));
        }

        if ($config['entities']) {
            $definition->addMethodCall('addEntity', array($config['entities']));
        }
    }
}
