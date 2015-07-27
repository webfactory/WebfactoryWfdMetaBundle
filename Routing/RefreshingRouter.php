<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Routing;

use Webfactory\Bundle\WfdMetaBundle\MetaQuery;
use Webfactory\Bundle\WfdMetaBundle\Util\CriticalSection;
use Webfactory\Bundle\WfdMetaBundle\Util\ExpirableConfigCache;

/**
 * RefreshingRouter ist wie Symfony\Bundle\FrameworkBundle\Routing\Router mit der
 * Besonderheit, dass er zusätzlich den wfd_meta.last_touched-Timestamp berücksichtigt
 * und seinen Cache invalidiert, wenn darüber Änderungen der Datenbank bemerkt werden.
 */
class RefreshingRouter extends \Symfony\Bundle\FrameworkBundle\Routing\Router {

    /** @var MetaQuery */
    protected $metaQuery;

    public function setMetaQuery(MetaQuery $metaQuery) {
        $this->metaQuery = $metaQuery;
    }

    public function addWfdTableDependency($tables)
    {
        trigger_error(
            'The addWfdTableDependency() setter is deprecated. Configure the MetaQuery instead.',
            E_USER_DEPRECATED
        );

        $this->metaQuery->addTable($tables);
    }

    /*
        --------- Aus der Basisklasse überschrieben Methoden, um das Caching-Verhalten zu ändern ---------

        getMatcher() und getGenerator() sind in der Basisklasse sehr ähnlich implementiert. Um
        nicht so viel Code zu duplizieren, ist die Gemeinsamkeit in createAndCache() ausgelagert.

        createAndCache() enthält die Besonderheit dieses Routers.
    */

    public function getMatcher() {
        return $this->createAndCache("matcher");
    }

    public function getGenerator() {
        return $this->createAndCache("generator");
    }

    /* ------------- Ende --------------- */


    protected function createAndCache($what) {
        if (null !== $this->$what) {
            return $this->$what;
        }

        $cacheClass = $this->options["{$what}_cache_class"];

        if (null === $this->options['cache_dir'] || null === $cacheClass) {
            return $this->$what = new $this->options["{$what}_class"]($this->getRouteCollection(), $this->context);
        }

        $cache = new ExpirableConfigCache(
            $this->options['cache_dir'] . '/' . $cacheClass . '.php',
            $this->options['debug'],
            $this->metaQuery->getLastTouched()
        );

        if (!$cache->isFresh()) {

            $cs = new CriticalSection(); $cs->setLogger($this->logger);
            $self = $this;
            $cs->execute(__FILE__, function() use ($what, $self, $cache) {
                if (!$cache->isFresh()) {
                    $self->dumpIntoCache($cache, $what);
                } else {
                    $self->debug("Cache has been refreshed while we were waiting.");
                }
            });
        }

        if (!class_exists($cacheClass)) require $cache;
        return $this->$what = new $cacheClass($this->context);
    }


    protected $logger;
    public function setLogger(\Symfony\Component\HttpKernel\Log\LoggerInterface $l) {
        $this->logger = $l;
    }

    public function debug($msg) {
        if ($this->logger) $this->logger->debug("$msg (PID " . getmypid() . ", microtime " . microtime() . ")");
    }


    // public, weil im Callback ausgeführt (PHP 5.3)
    public function dumpIntoCache(ExpirableConfigCache $cache, $what) {
        $routeCollection = $this->getRouteCollection();

        $dumperClass = $this->getOption("{$what}_dumper_class");
        $dumper = new $dumperClass($routeCollection);

        $cache->write($dumper->dump(array(
            'class' => $this->options["{$what}_cache_class"],
            'base_class' => $this->getOption("{$what}_base_class")
        )), $routeCollection->getResources());

        $this->debug("$dumperClass dumped the RouteCollection.");

        /*
        * Weil wir jetzt schon eine initialisierte RouteCollection auf dieser
        * Klasse haben und noch in der critical section sind, sichern
        * wir schnell auch noch "das andere" matcher|generator-Ding,
        * das ebenfalls von der RouteCollection abhängt.
        */
        $this->createAndCache($what == 'matcher' ? 'generator' : 'matcher');
    }
}
