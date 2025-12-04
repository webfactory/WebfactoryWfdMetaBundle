<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $services->set(\Webfactory\Bundle\WfdMetaBundle\DoctrineMetadataHelper::class)
        ->args([expr('
                service("doctrine.orm.entity_manager").getMetadataFactory()
            ')]);
};
