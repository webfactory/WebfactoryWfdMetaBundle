<?php

namespace Webfactory\Bundle\WfdMetaBundle\Caching;

use Webfactory\Bundle\WfdMetaBundle\Caching\Annotation\Send304IfNotModified;
use Webfactory\Bundle\WfdMetaBundle\Provider;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Annotations\Reader;
use Webfactory\Bundle\WfdMetaBundle\Caching\Annotation\ValidUntilLastTouched;

class EventListener {

    protected $reader;
    protected $provider;
    protected $debug;
    protected $lastTouchedResults;

    public function __construct(Reader $reader, Provider $provider, $debug) {
        $this->reader = $reader;
        $this->provider = $provider;
        $this->debug = $debug;
        $this->lastTouchedResults = new \SplObjectStorage();

    }

    public function onKernelController(FilterControllerEvent $event) {

        $lastTouched = $this->findLastTouched($event->getController());

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

    protected function getLastTouched(Send304IfNotModified $configuration) {
        if ($ts = $this->provider->getLastTouched(
            $configuration->getTables()
        )
        ) {
            return new \DateTime("@$ts");
        }
    }

    protected function findLastTouched($callback) {
        $lastTouched = false;

        foreach ($this->findAnnotations($callback) as $configuration) {
            $lastTouched = max($lastTouched, $this->getLastTouched($configuration));
        }

        return $lastTouched;
    }

    /**
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
