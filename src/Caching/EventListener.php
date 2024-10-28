<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\Bundle\WfdMetaBundle\Caching;

use ReflectionObject;
use SplObjectStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Webfactory\Bundle\WfdMetaBundle\Caching\Attribute\Send304IfNotModified;
use Webfactory\Bundle\WfdMetaBundle\MetaQueryFactory;

/**
 * @deprecated Use WebfactoryHttpCachingBundle instead.
 */
class EventListener
{
    /** @var MetaQueryFactory */
    protected $metaQueryFactory;

    protected $debug;

    /** @var SplObjectStorage */
    protected $lastTouchedResults;

    public function __construct(MetaQueryFactory $metaQueryFactory, $debug)
    {
        $this->metaQueryFactory = $metaQueryFactory;
        $this->debug = $debug;
        $this->lastTouchedResults = new SplObjectStorage();
    }

    public function onKernelController(ControllerEvent $event)
    {
        $attributes = $event->getAttributes(Send304IfNotModified::class);

        if (!$attributes) {
            return;
        }

        $lastTouched = $attributes[0]->calculateLastModified($this->metaQueryFactory);

        if (!$lastTouched) {
            return;
        }

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
        if ($this->debug) {
            return;
        }

        $response = new Response();
        $response->setLastModified($lastTouched);

        if ($response->isNotModified($request)) {
            $event->setController(function () use ($response) {
                return $response;
            }, $event->getAttributes());
        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (isset($this->lastTouchedResults[$request])) {
            $response->setLastModified($this->lastTouchedResults[$request]);
        }
    }
}
