<?php
/**
 * @author quantm.tb@gmail.com
 * @date: 2/2/2017 9:00 AM
 */

namespace Q;


use Q\Interfaces\Response;
use Q\Request;
use Q\Response\JsonResponse;
use Q\Response\RedirectResponse;
use Q\Router\RouteCollection;


class Application
{
    protected $routeCollection;
    private $requestUri;
    private $requestMethod;
    private $baseControllerNamespace;
    private $event;

    /**
     * Define APP_BASE_URI, FULL_BASE_URI
     * Application constructor.
     */
    public function __construct()
    {
        $scriptNames = explode('/', $_SERVER['SCRIPT_NAME']);
        array_pop($scriptNames);
        $baseDir = implode('/', $scriptNames);

        define('APP_BASE_URI', '/' . ltrim($baseDir, '/'));
        $requestScheme = isset( $_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
        define('FULL_BASE_URI', $requestScheme . '://' .  $_SERVER['HTTP_HOST'] . APP_BASE_URI);
        $this->requestUri = substr($_SERVER['REQUEST_URI'], strlen($baseDir), strlen($_SERVER['REQUEST_URI']));
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->event = new Event('Application');
        $this->routeCollection = new RouteCollection();
    }

    public function setBaseControllerNamespace($namespace) {
        $this->baseControllerNamespace = $namespace;

    }

    /**
     * @param $route
     * @param $handler
     * @param $name
     */
    public function get($route, $handler, $name = null) {
        $this->routeCollection->get($route, $handler, $name);
    }

    /**
     * @param $route
     * @param $handler
     * @param $name
     */
    public function post($route, $handler, $name = null) {
        $this->routeCollection->post($route, $handler, $name);
    }

    /**
     * @param $route
     * @param $handler
     * @param $name
     */
    public function patch($route, $handler, $name = null) {
        $this->routeCollection->patch($route, $handler, $name);
    }


    /**
     * @param $route
     * @param $handler
     * @param $name
     */
    public function put($route, $handler, $name = null) {
        $this->routeCollection->put($route, $handler, $name);
    }

    /**
     * @param $route
     * @param $handler
     * @param $name
     */
    public function delete($route, $handler, $name = null) {
        $this->routeCollection->delete($route, $handler, $name);
    }

    /**
     * @param $route
     * @param $handler
     * @param $name
     */
    public function all($route, $handler, $name = null) {
        $this->routeCollection->all($route, $handler, $name);
    }

    /**
     * @param $name
     * @param array $params
     * @return mixed
     */
    public function decodeRoute($name, $params = []) {
        return $this->routeCollection->decode($name, $params);
    }

    /**
     * Route group
     * Example: $app->group('/admin', function(RouteCollection $route) {
            $route->get('/customers/{action}', $handler)
     * });
     * @param $prefix
     * @param $callback
     */
    public function group($prefix, $callback) {
        $route = new RouteCollection($prefix);
        $callback($route);
        $this->routeCollection->merge($route);
    }

    /**
     * @param $config
     * @param string $prefix
     */
    public function loadRoute($config, $prefix = '') {
        $route = new RouteCollection($prefix);
        include ROOT . '/routes/' . $config . '.php';

        $this->routeCollection->merge($route);

    }

    protected function beforeDispatch(Request $request, $resolve, $reject)
    {
        $resolve();
    }

    /**
     * @param $eventName
     * @param $handler
     */
    public function on($eventName, $handler) {
        $this->event->on($eventName, $handler);
    }

    private function render($result)
    {
        if ($result === null) {
            return;
        }

        if ($result instanceof Response) {
            $result->render();
        } else if (is_array($result) || $result instanceof \stdClass) {
            $response = new JsonResponse($result);
            $response->render();
        } else {
            echo $result;
        }
    }


    /**
     * Dispatch route
     */
    public function dispatch() {

        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            foreach ($this->routeCollection->getCollection() as $route) {
                if ($route['method'] === 'ALL') {
                    $r->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'], $route['route'], $route['handler']);
                } else {
                    $r->addRoute($route['method'], $route['route'], $route['handler']);
                }

            }
        });

        // Fetch method and URI from somewhere
        $httpMethod = $this->requestMethod;
        $uri = $this->requestUri;

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }


        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                http_response_code(404);
                echo 'Page not found';
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                http_response_code(405);
                echo 'Method not allowed';
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $request = new Request($vars, $this->requestUri);
                try {
                    $this->beforeDispatch($request, function($request) use($handler) {
                        $result = null;
                        if ($handler instanceof \Closure) {
                            $result = $handler($request);
                        } else if (is_string($handler)) {
                            if (strpos($handler, '@') !== false) {
                                list($controller, $action) = explode('@', $handler);
                                $controllerClass = $this->baseControllerNamespace . $controller;
                                $instance = new $controllerClass();
                                $result = $instance->$action($request);
                            } else {
                                $controllerClass =  $this->baseControllerNamespace . $handler;

                                $instance = new $controllerClass();
                                $result = $instance($request);
                            }
                        }

                        $this->render($result);

                    }, function($rejectResponse) use($handler, $request) {
                        $this->render($rejectResponse);
                        $this->event->emit('shutdown', [$request, $rejectResponse]);
                    });

                } catch (\Exception $e) {
                    if (! $this->event->emit('exception', [$request, $e])) {
                        if (config('app')['debug']) {
                            echo "<strong>". get_class($e) . ': '. $e->getMessage() . "</strong><br>";
                            echo "<pre>" . $e->getTraceAsString() . "</pre>";
                        } else {
                            echo "<strong>" . $e->getMessage() . "</strong><br>";
                        }
                    };
                }


                break;
        }

        die;
    }
}
