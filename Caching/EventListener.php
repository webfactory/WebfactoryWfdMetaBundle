<?php

namespace Webfactory\Bundle\WfdMetaBundle\Caching;

use Webfactory\Bundle\WfdMetaBundle\Caching\Annotation\Send304IfNotModified;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Annotations\Reader;

class EventListener {

    protected $reader;

    /** @var MetaQueryFactory */
    protected $metaQueryFactory;

    protected $debug;
    protected $lastTouchedResults;

    public function __construct(Reader $reader, MetaQueryFactory $metaQueryFactory, $debug) {
        $this->reader = $reader;
        $this->metaQueryFactory = $metaQueryFactory;
        $this->debug = $debug;
        $this->lastTouchedResults = new \SplObjectStorage();
    }

    public function onKernelController(FilterControllerEvent $event) {

        $lastTouched = $this->calculateLastTouched($event->getController());

        if (false !== $lastTouched) {

            $request = $event->getRequest();
            $this->lastTouchedResults[$request] = $lastTouched;
            /*
             * Für kernel.debug = 1 senden wir niemals
             * 304-Responses, anstatt den Kernel auszuführen:
             *
             * Das Ergebnis hängt auch von vielen Dingen außerhalb
             * wfd_meta ab (z. B. template-Code), die wir hier nicht
             * berücksichtigen können.
             */
            if (!$this->debug) {

                $response = new Response();
                $response->setLastModified($lastTouched);

                if ($response->isNotModified($request)) {
                    $event->setController(function () use ($response) {
                        return $response;
                    });
                }
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (isset($this->lastTouchedResults[$request])) {
            $response->setLastModified($this->lastTouchedResults[$request]);
        }
    }

    protected function createMetaQuery() {
        return $this->metaQueryFactory->create();
    }

    protected function calculateLastTouched($callback) {
        $resetInterval = 0;

        foreach ($this->findAnnotations($callback) as $annotation) {
            $resetInterval = max($resetInterval, $annotation->getResetInterval());
        }

        if ($resetInterval === 0) {
            $resetInterval = 60 * 60 * 24 * 28; //Default: 28 Tage
        }

        if ($ts = $this->loadLastTouched($callback)) {
            $ts = time() - ((time() - $ts) % $resetInterval);
            return new \DateTime("@$ts");
        }

        return false;
    }

    protected function loadLastTouched($callback){
        $metaQuery = $this->createMetaQuery();

        foreach ($this->findAnnotations($callback) as $annotation) {
            $annotation->configure($metaQuery);
        }

        if ($ts = $metaQuery->getLastTouched()) {
            return $ts;
        }

        return false;
    }

    /**
     * @param $callback A PHP callback (array) pointing to the method to reflect on.
     * @return Send304IfNotModified[]
     */
    protected function findAnnotations($callback) {
        $r = array();

        if (is_array($callback)) {

            $object = new \ReflectionObject($callback[0]);
            $method = $object->getMethod($callback[1]);

            foreach ($this->reader->getMethodAnnotations($method) as $configuration) {
                if ($configuration instanceof Send304IfNotModified) {
                    $r[] = $configuration;
                }
            }
        }

        return $r;
    }
}
