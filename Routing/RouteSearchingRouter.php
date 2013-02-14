<?php

namespace Webfactory\Bundle\WfdMetaBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

class RouteSearchingRouter {

    protected $router;
    protected $invertedRouteIndex;

    public function __construct(Router $router, InvertedRouteIndex $invertedRouteIndex) {
        $this->router = $router;
        $this->invertedRouteIndex = $invertedRouteIndex;
    }

    public function generate($parameters = array(), $absolute = false) {
        if (!isset($parameters['_locale']) && ($locale = $this->router->getContext()->getParameter('_locale'))) {
            $parameters['_locale'] = $locale;
        }

        return $this->router->generate(
            $this->invertedRouteIndex->lookup($parameters),
            $parameters,
            $absolute
        );
    }

}