<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Erzeugt MetaQuery-Instanzen.
 */
class MetaQueryFactory implements ServiceSubscriberInterface
{
    /** @var Provider */
    private $metaProvider;

    /** @var ContainerInterface */
    private $container;

    public static function getSubscribedServices(): array
    {
        return [
            DoctrineMetadataHelper::class => DoctrineMetadataHelper::class,
        ];
    }

    public function __construct(Provider $provider, ContainerInterface $container)
    {
        $this->metaProvider = $provider;
        $this->container = $container;
    }

    public function create(): MetaQuery
    {
        return new MetaQuery($this->metaProvider, $this->container);
    }
}
