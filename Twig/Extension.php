<?php

namespace Webfactory\Bundle\WfdMetaBundle\Twig;

use Symfony\Component\DependencyInjection\Container;

class Extension extends \Twig_Extension {

    protected $container;

    public function __construct(Container $c) {
        $this->container = $c;
    }

    public function getName() {
        return 'webfactory_wfd_meta';
    }

    public function getFunctions() {
        return array(
            'switchLocalePath' => new \Twig_Function_Method($this, 'getSwitchLocalePath')
        );
    }

    public function getSwitchLocalePath($locale) {
        $routeSearchingRouter = $this->container->get('webfactory.route_searching_router');
        $localeParameters = array('_locale' => $locale);
        try {

            return $routeSearchingRouter->generate(array_merge(
                $this->container->get('request')->get('_route_params'),
                $localeParameters
            ));

        } catch(\Exception $e) {

            return $routeSearchingRouter->generate($localeParameters);

        }
    }

}
