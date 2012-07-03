<?php

namespace Webfactory\Bundle\WfdMetaBundle\Caching;

use Webfactory\Bundle\WfdMetaBundle\Provider;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Annotations\Reader;
use Webfactory\Bundle\WfdMetaBundle\Caching\Annotation\ValidUntilLastTouched;

class EventListener {

    protected $reader;
    protected $provider;
    protected $mostRecentLastTouchedMap;

    public function __construct(Reader $reader, Provider $provider) {
        $this->reader = $reader;
        $this->provider = $provider;
        $this->mostRecentLastTouchedMap = new \SplObjectStorage();
    }

    public function onKernelController(FilterControllerEvent $event) {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $request = $event->getRequest();
        $response = new Response();

        $object = new \ReflectionObject($controller[0]);
        $method = $object->getMethod($controller[1]);

        $mostRecentLastTouched = false;

        foreach ($this->reader->getMethodAnnotations($method) as $configuration) {
            if ($configuration instanceof ValidUntilLastTouched) {
                if ($lt = $this->getLastTouched($configuration)) {
                    if ($lt > $mostRecentLastTouched) {
                        $mostRecentLastTouched = $lt;
                    }
                }
            }
        }

        if ($mostRecentLastTouched !== false) {
            $response->setLastModified($mostRecentLastTouched);
            if ($response->isNotModified($request)) {
                $event->setController(function() use($response) {
                    return $response;
                });
            } else {
                $this->mostRecentLastTouchedMap[$request] = $mostRecentLastTouched;
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (isset($this->mostRecentLastTouchedMap[$request])) {
            $response->setPublic();
            $response->setLastModified($this->mostRecentLastTouchedMap[$request]);
        }
    }

    protected function getLastTouched(ValidUntilLastTouched $configuration) {
        if ($ts = $this->provider->getLastTouched(
            array_map(
                function($constant) {
                    return constant($constant);
                },
                (array) $configuration->getTableIdConstants()
            )
        ))
            return new \DateTime("@$ts");
    }

}