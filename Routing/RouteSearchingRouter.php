<?php

namespace Webfactory\Bundle\WfdMetaBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouteSearchingRouter {

    protected $urlGenerator;
    protected $invertedRouteIndex;

    public function __construct(UrlGeneratorInterface $urlGenerator, InvertedRouteIndex $invertedRouteIndex) {
        $this->urlGenerator = $urlGenerator;
        $this->invertedRouteIndex = $invertedRouteIndex;
    }

    public function generate($parameters = array()) {
        return $this->urlGenerator->generate(
            $this->invertedRouteIndex->lookup($parameters),
            $parameters
        );
    }

}