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
        try {

            $request = $this->container->get('request');

            $parameters = $request->get('_route_params');
            $parameters['_locale'] = $locale;
            $parameters['_language'] = \Locale::getPrimaryLanguage($locale);

            return $this->container->get('webfactory.route_searching_router')->generate($parameters);

        } catch(\Exception $e) {

            return $this->container->get('webfactory.route_searching_router')->generate(array('_locale' => $locale));

        }
    }

}
