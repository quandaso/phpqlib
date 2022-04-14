<?php

/**
 * @author H110
 * @date: 6/24/2017 09:24
 */
namespace Q\Router;

class RouteCollection
{
    private $routeCollection = [];
    private $routeCollectionReversed = [];
    private $prefix = null;

    public function __construct($prefix = null)
    {
        $this->prefix = (string)$prefix;
    }

    /**
     * Merges 2 collections
     * @param RouteCollection $route
     */
    public function merge(RouteCollection $routeCollection) {

        foreach ($routeCollection->routeCollection as $route) {
            $this->routeCollection[] = $route;
        }

        $this->routeCollectionReversed = array_merge($this->routeCollectionReversed, $routeCollection->routeCollectionReversed);
    }

    /**
     * @return array
     */
    public function getCollection() {
        return $this->routeCollection;
    }

    public function getCollectionReversed() {
        return $this->routeCollectionReversed;
    }

    /**
     * @param $route
     * @param $handler
     * @param $name
     */
    public function get($route, $handler, $name = null) {
        if (!$name && is_string($handler)) {
            $name = $handler;
        }
        if ($name) {
            $this->routeCollectionReversed[$name] = $route;
        }

        $this->routeCollection[] = [
            'method' => 'GET',
            'route' => $this->prefix . $route,
            'handler' => $handler
        ];
    }

    /**
     * @param $route
     * @param $handler
     */
    public function post($route, $handler, $name = null) {
        if (!$name && is_string($handler)) {
            $name = $handler;
        }
        if ($name) {
            $this->routeCollectionReversed[$name] = $route;
        }
        $this->routeCollection[] = [
            'method' => 'POST',
            'route' => $this->prefix . $route,
            'handler' => $handler
        ];
    }

    /**
     * @param $route
     * @param $handler
     */
    public function patch($route, $handler, $name = null) {
        if (!$name && is_string($handler)) {
            $name = $handler;
        }
        if ($name) {
            $this->routeCollectionReversed[$name] = $route;
        }
        $this->routeCollection[] = [
            'method' => 'PATCH',
            'route' => $this->prefix . $route,
            'handler' => $handler
        ];
    }


    /**
     * @param $route
     * @param $handler
     */
    public function put($route, $handler, $name = null) {
        if (!$name && is_string($handler)) {
            $name = $handler;
        }
        if ($name) {
            $this->routeCollectionReversed[$name] = $route;
        }
        $this->routeCollection[] = [
            'method' => 'PUT',
            'route' => $this->prefix . $route,
            'handler' => $handler
        ];
    }

    /**
     * @param $route
     * @param $handler
     */
    public function delete($route, $handler, $name = null) {
        if (!$name && is_string($handler)) {
            $name = $handler;
        }
        if ($name) {
            $this->routeCollectionReversed[$name] = $route;
        }
        $this->routeCollection[] = [
            'method' => 'DELETE',
            'route' => $this->prefix . $route,
            'handler' => $handler
        ];
    }

    /**
     * @param $route
     * @param $handler
     */
    public function all($route, $handler, $name = null) {
        if (!$name && is_string($handler)) {
            $name = $handler;
        }

        if ($name) {
            $this->routeCollectionReversed[$name] = $route;
        }

        $this->routeCollection[] = [
            'method' => 'ALL',
            'route' => $this->prefix . $route,
            'handler' => $handler
        ];
    }

    /**
     * @param $name
     * @param array $params
     * @return mixed
     */
    public function decode($name, $params = []) {
        $url = null;

        if (isset($this->routeCollectionReversed[$name])) {
            $url = $this->routeCollectionReversed[$name];

            $url = preg_replace_callback('/{\w+}/', function($match) use(&$params) {
                $k = trim($match[0], '{}');
                if (isset($params[$k])) {
                    $r = $params[$k];
                    unset($params[$k]);
                    return $r;
                }

            }, $url);

            if (!empty($params)) {
                return $url . '?' . http_build_query($params);
            }

        }
        return $url;
    }

}