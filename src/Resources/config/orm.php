<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $services->set(\Webfactory\Bundle\WfdMetaBundle\DoctrineMetadataHelper::class)
        ->args([
            inline_service()->factory([service('doctrine.orm.default_entity_manager'), 'getMetadataFactory']),
        ]);
};
