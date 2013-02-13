<?php

namespace Webfactory\Bundle\WfdMetaBundle\Routing;
use Webfactory\Bundle\WfdMetaBundle\Provider;
use Webfactory\Bundle\WfdMetaBundle\Routing\InvertedRouteIndex;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Webfactory\Bundle\WfdMetaBundle\Util\ExpirableConfigCache;

class InvertedRouteIndexFactory {

    protected $metaProvider;
    protected $container;
    protected $logger;
    protected $tableDeps = array();

    public function __construct(Provider $metaProvider, ContainerInterface $container, LoggerInterface $logger) {
        $this->metaProvider = $metaProvider;
        $this->container = $container;
        $this->logger = $logger;
    }

    public function addWfdTableDependency($tables) {
        $this->tableDeps += array_fill_keys((array)$tables, true);
    }

    public function debug($msg) {
        if ($this->logger)
            $this->logger->debug("$msg (PID " . getmypid() . ", microtime " . microtime() . ")");
    }

    public function createIndex() {
        $container = $this->container;
        $ts = $this->metaProvider->getLastTouched(array_keys($this->tableDeps));

        $cache = new ExpirableConfigCache(
            $container->getParameter('kernel.cache_dir') . "/webfactory/reverse_route_index.php",
            $container->getParameter('kernel.debug'),
            $ts
        );

        if (!$cache->isFresh()) {
            $cs = new \Webfactory\Bundle\WfdMetaBundle\Util\CriticalSection();
            $cs->setLogger($this->logger);

            $self = $this;
            $index = null;
            $cs->execute(__FILE__, function () use ($self, $cache, $container) {
                if (!$cache->isFresh()) {
                    $self->debug("Building the reverse route index");

                    $index = new InvertedRouteIndex($container->get('router')->getRouteCollection());

                    $cache->write("<?php return unserialize(<<<EOD\n" . serialize($index) . "\nEOD\n);");

                    $self->debug("Finished building the reverse route index");
                } else {
                    $self->debug("Had to wait for the cache to be initialized by another process");
                }
            });
        }

        $this->debug("Loading the cached reverse route index");
        $index = require $cache;
        $this->debug("Finished loading the cached reverse route index");

        return $index;
    }

}
