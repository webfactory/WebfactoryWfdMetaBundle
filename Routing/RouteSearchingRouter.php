<?php

namespace Webfactory\Bundle\WfdMetaBundle\Routing;

use Symfony\Component\DependencyInjection\ContainerInterface;

class RouteSearchingRouter {

    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function generate($parameters = array(), $absolute = false) {
        $request = $this->container->get('request');
        $currentParameters = $request->get('_route_params');

        if (!isset($parameters['_locale']) && isset($currentParameters['_locale'])) {
            $parameters['_locale'] = $currentParameters['_locale'];
        }

        return $this->container->get('router')->generate(
            $this->container->get('webfactory.inverted_route_index')->lookup($parameters),
            $parameters,
            $absolute
        );
    }

}