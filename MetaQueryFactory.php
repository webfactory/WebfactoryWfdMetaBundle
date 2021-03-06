<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Erzeugt MetaQuery-Instanzen.
 */
class MetaQueryFactory
{
    /** @var Provider */
    private $metaProvider;

    /** @var ContainerInterface */
    private $container;

    public function __construct(Provider $provider, ContainerInterface $container)
    {
        $this->metaProvider = $provider;
        $this->container = $container;
    }

    public function create()
    {
        return new MetaQuery($this->metaProvider, $this->container);
    }
}
