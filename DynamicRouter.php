<?php

namespace Webfactory\Bundle\DocumentRoutingBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\ConfigCache;
use Webfactory\Bundle\WfdMetaBundle\Provider;

/**
 * DynamicRouter verhält sich wie der Symfony\Bundle\FrameworkBundle\Routing\Router mit der
 * Besonderheit, dass er zusätzlich den wfd_meta.last_touched-Timestamp berücksichtigt
 * und seinen Cache invalidiert, wenn darüber Änderungen der Datenbank bemerkt werden.
 */
class DynamicRouter extends Router {

    protected $metaProvider;
    protected $tableDeps = array();

    public function setWfdMetaProvider(Provider $p) {
        $this->metaProvider = $p;
    }

    public function addTableDependency($tableId) {
        $this->tableDeps[] = $tableId;
    }

    protected function dynamicCache($what) {
        if (null !== $this->$what) {
            return $this->$what;
        }

        if (null === $this->options['cache_dir'] || null === $this->options["{$what}_cache_class"]) {
            return $this->$what = new $this->options["{$what}_class"]($this->getRouteCollection(), $this->context, $this->defaults);
        }

        $ts = $this->metaProvider->getLastTouched($this->tableDeps);
        $class = $this->options["{$what}_cache_class"];
        $dynamicClass = "{$class}_{$ts}";

        $cache = new ConfigCache($this->options['cache_dir'].'/'.$class.'.php', $this->options['debug']);
        if ($cache->isFresh()) {
            require $cache;

            if (class_exists($dynamicClass)) {
                return $this->$what = new $dynamicClass($this->context, $this->defaults);
            }
        }

        $dumper = new $this->options["{$what}_dumper_class"]($this->getRouteCollection());

        $options = array(
            'class'      => $dynamicClass,
            'base_class' => $this->options["{$what}_base_class"],
        );

        $cache->write($dumper->dump($options), $this->getRouteCollection()->getResources());

        require $cache;

        return $this->$what = new $dynamicClass($this->context, $this->defaults);
    }

    public function getMatcher() { return $this->dynamicCache("matcher"); }
    public function getGenerator() { return $this->dynamicCache("generator"); }

}
