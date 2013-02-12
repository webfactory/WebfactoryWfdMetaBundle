<?php

namespace Webfactory\Bundle\WfdMetaBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReverseRouter {

    protected $urlGenerator;
    protected $reverseRouteIndex;

    public function __construct(UrlGeneratorInterface $urlGenerator, ReverseRouteIndex $reverseRouteIndex) {
        $this->urlGenerator = $urlGenerator;
        $this->reverseRouteIndex = $reverseRouteIndex;
    }

    public function generate($parameters = array()) {
        return $this->urlGenerator->generate(
            $this->reverseRouteIndex->lookup($parameters),
            $parameters
        );
    }

}