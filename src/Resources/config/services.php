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

    $services->set(\Webfactory\Bundle\WfdMetaBundle\Provider::class);

    $services->set(\Webfactory\Bundle\WfdMetaBundle\MetadataFacade::class)
        ->args([
            service(\Webfactory\Bundle\WfdMetaBundle\Provider::class),
            service(\Webfactory\Bundle\WfdMetaBundle\DoctrineMetadataHelper::class)->nullOnInvalid(),
        ]);

    $services->set(\Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory::class);

    $services->set(\Webfactory\Bundle\WfdMetaBundle\MetaQuery::class)
        ->abstract()
        ->factory([service(\Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory::class), 'create']);

    $services->set(\Webfactory\Bundle\WfdMetaBundle\Caching\EventListener::class)
        ->args([
            service(\Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory::class),
            '%kernel.debug%',
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.controller', 'method' => 'onKernelController', 'priority' => -200])
        ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse']);

    $services->set(\Webfactory\Bundle\WfdMetaBundle\Controller\TemplateController::class)
        ->args([
            service('twig'),
            service(\Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory::class),
            '%kernel.debug%',
        ])
        ->tag('controller.service_arguments');

    $services->set(\Webfactory\Bundle\WfdMetaBundle\Config\WfdMetaConfigCacheFactory::class)
        ->decorate('config_cache_factory')
        ->args([
            service('Webfactory\Bundle\WfdMetaBundle\Config\WfdMetaConfigCacheFactory.inner'),
            service(\Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory::class),
            inline_service(\Symfony\Component\Lock\LockFactory::class)
                ->args([inline_service(\Symfony\Component\Lock\Store\FlockStore::class)])
                ->call('setLogger', [service('logger')->ignoreOnInvalid()])
                ->tag('monolog.logger', ['channel' => 'webfactory_wfd_meta']),
            '%webfactory_wfd_meta.expire_wfd_meta_resources%',
        ]);
};
