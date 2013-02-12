<?php

namespace Webfactory\Bundle\WfdMetaBundle\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ReverseRouteIndex {

    protected $index = array();
    protected $initialWorkingSet = array();

    public function index(RouteCollection $rc) {
        foreach ($rc->all() as $name => $route) {
            $this->addRoute($name, $route);
        }
    }

    public function lookup(array $params) {
        $workingSet = $this->initialWorkingSet;

        foreach ($params as $key => $value) {
            $defaultMatches = (array)@$this->index["$key=$value"];
            $variableMatches = (array)@$this->index["$key=*"];

            // If we do not find any route, that knows this parameter, we simply ignore it (so it will be appended to as query string)
            if (!$defaultMatches && !$variableMatches)
                continue;

            // From here on, only routes, that know the parameter can be a valid result
            $possibleRoutes = array_flip(array_merge($defaultMatches, $variableMatches));
            $workingSet = array_intersect_key($workingSet, $possibleRoutes);

            // We add some information to the working set, so that we can weight multiple valid results
            foreach ($defaultMatches as $routeName) {
                if (isset($workingSet[$routeName])) {
                    $workingSet[$routeName]['numberOfMatchedDefaults']++;
                }
            }
            foreach ($variableMatches as $routeName) {
                if (isset($workingSet[$routeName])) {
                    $workingSet[$routeName]['numberOfUnboundVariables']--;
                    $workingSet[$routeName]['numberOfMatchedVariables']++;
                }
            }
        }

        // We'll only allow results, that have no unbound variables
        $workingSet = array_filter($workingSet, function($information) {
            return $information['numberOfUnboundVariables'] == 0;
        });

        // We now sort the results by the weighted information
        uasort($workingSet, function($a, $b) {
            foreach (array('numberOfMatchedDefaults', 'numberOfMatchedVariables') as $weight) {
                if ($a[$weight] == $b[$weight]) continue;
                return $b[$weight] - $a[$weight];
            }

            return $a['routePosition'] - $b['routePosition'];
        });

        // The first result in the working set (if any) is our best result
        foreach ($workingSet as $routeName => $information) {
            return $routeName;
        }
    }

    protected function addRoute($name, Route $route) {
        $compiled = $route->compile();
        $this->initialWorkingSet[$name] = array(
            'numberOfMatchedDefaults' => 0,
            'numberOfMatchedVariables' => 0,
            'routePosition' => count($this->initialWorkingSet),
            'numberOfUnboundVariables' => count($compiled->getVariables())
        );

        foreach ($compiled->getVariables() as $variable)
            $this->addIndex("$variable=*", $name);

        foreach ($route->getDefaults() as $param => $value)
            $this->addIndex("$param=$value", $name);
    }

    protected function addIndex($key, $routeName) {
        $this->index[$key][] = $routeName;
    }

}