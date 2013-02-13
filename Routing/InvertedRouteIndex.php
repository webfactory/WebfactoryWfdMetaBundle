<?php

namespace Webfactory\Bundle\WfdMetaBundle\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class InvertedRouteIndex {

    protected $defaultIndex = array();
    protected $variableIndex = array();
    protected $initialWorkingSet = array();

    public function __construct(RouteCollection $rc) {
        foreach ($rc->all() as $name => $route) {
            $this->addRoute($name, $route);
        }
    }

    public function lookup(array $params) {
        $workingSet = $this->initialWorkingSet;

        foreach ($params as $key => $value) {
            $defaultMatches = (array)@$this->defaultIndex["$key=$value"];
            $variableMatches = (array)@$this->variableIndex[$key];

            // If we do not find any route, that knows this parameter, we simply ignore it (so it will be appended to as query string)
            if (!$defaultMatches && !$variableMatches)
                continue;

            // From here on, only routes, that know the parameter and that are still in the working set can be a valid result
            $possibleRoutes = array_flip(array_merge($defaultMatches, $variableMatches));
            $workingSet = array_intersect_key($workingSet, $possibleRoutes);

            // We only need the matching information for those routes that are in the working set
            $inWorkingSetCallback = function($routeName) use ($workingSet) { return isset($workingSet[$routeName]); };
            $defaultMatches = array_filter($defaultMatches, $inWorkingSetCallback);
            $variableMatches = array_filter($variableMatches, $inWorkingSetCallback);

            // We add some information to the working set, so that we can weight multiple valid results
            foreach ($defaultMatches as $routeName) {
                $workingSet[$routeName]['numberOfMatchedDefaults']++;
            }
            foreach ($variableMatches as $routeName) {
                $workingSet[$routeName]['numberOfUnboundVariables']--;
            }
        }

        // We'll only allow results, that have no unbound variables
        $workingSet = array_filter($workingSet, function($information) {
            return $information['numberOfUnboundVariables'] == 0;
        });

        // We now sort the results by the weighted information (ascending or descending)
        uasort($workingSet, function($a, $b) {
            foreach (array(
                'numberOfMatchedDefaults' => false,
                'numberOfPathComponents' => true,
                'routePosition' => true
            ) as $weight => $ascendingOrder) {
                if ($a[$weight] == $b[$weight]) continue;

                if ($ascendingOrder) {
                    return $a[$weight] - $b[$weight];
                } else {
                    return $b[$weight] - $a[$weight];
                }
            }

            return 0;
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
            'numberOfPathComponents' => count(explode('/', trim($route->getPattern(), '/'))),
            'routePosition' => count($this->initialWorkingSet),
            'numberOfUnboundVariables' => count($compiled->getVariables())
        );

        foreach ($compiled->getVariables() as $variable)
            $this->variableIndex[$variable][] = $name;

        foreach ($route->getDefaults() as $param => $value)
            $this->defaultIndex["$param=$value"][] = $name;
    }

}