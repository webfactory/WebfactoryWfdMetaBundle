<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Caching;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Webfactory\Bundle\WfdMetaBundle\Caching\Annotation\Send304IfNotModified;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;

/**
 * @deprecated Use WebfactoryHttpCachingBundle instead.
 */
class EventListener
{

    protected $reader;

    /** @var MetaQueryFactory */
    protected $metaQueryFactory;

    protected $debug;

    /** @var \SplObjectStorage */
    protected $lastTouchedResults;

    public function __construct(Reader $reader, MetaQueryFactory $metaQueryFactory, $debug)
    {
        $this->reader = $reader;
        $this->metaQueryFactory = $metaQueryFactory;
        $this->debug = $debug;
        $this->lastTouchedResults = new \SplObjectStorage();
    }

    public function onKernelController(FilterControllerEvent $event)
    {

        $controller = $event->getController();
        $request = $event->getRequest();

        $annotation = $this->findAnnotation($controller);

        if (!$annotation) {
            return;
        }

        $lastTouched = $annotation->calculateLastModified($this->metaQueryFactory);

        if (!$lastTouched) {
            return;
        }

        $this->lastTouchedResults[$request] = $lastTouched;

        /*
         * Für kernel.debug = 1 senden wir niemals
         * 304-Responses, anstatt den Kernel auszuführen:
         *
         * Das Ergebnis hängt auch von vielen Dingen außerhalb
         * wfd_meta ab (z. B. template-Code), die wir hier nicht
         * berücksichtigen können.
         */
        if ($this->debug) {
            return;
        }

        $response = new Response();
        $response->setLastModified($lastTouched);

        if ($response->isNotModified($request)) {
            $event->setController(function () use ($response) {
                return $response;
            });
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (isset($this->lastTouchedResults[$request])) {
            $response->setLastModified($this->lastTouchedResults[$request]);
        }
    }

    /**
     * @param $callback array A PHP callback (array) pointing to the method to reflect on.
     * @return Send304IfNotModified|null The annotation, if found. Null otherwise.
     */
    protected function findAnnotation($callback)
    {
        if (is_array($callback)) {

            $object = new \ReflectionObject($callback[0]);
            $method = $object->getMethod($callback[1]);

            foreach ($this->reader->getMethodAnnotations($method) as $configuration) {
                if ($configuration instanceof Send304IfNotModified) {
                    return $configuration;
                }
            }
        }

        return null;
    }
}
