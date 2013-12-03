<?php
namespace Webfactory\Bundle\WfdMetaBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Erzeugt MetaQuery-Instanzen.
 */
class MetaQueryFactory
{
    /** @var Provider */
    protected $metaProvider;

    /** @var ContainerInterface */
    protected $container;

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
