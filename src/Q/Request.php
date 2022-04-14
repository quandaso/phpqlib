<?php
/**
 * @author quantm.tb@gmail.com
 * @date: 2/3/2017 10:09 AM
 */

namespace Q;


class Request implements \ArrayAccess, \JsonSerializable
{

    private $method;
    private $uri;
    private $data;
    private $routeParams;


    /**
     * Request constructor.
     * @param $routeParams array
     * @param $uri array
     * @param $requestMethod string
     * @param $headers string
     */
    public function __construct($routeParams = array(), $uri = null, $requestMethod = null, $headers = null)
    {
        $this->method = $requestMethod ? $requestMethod : $_SERVER['REQUEST_METHOD'];
        $this->data = $this->params();
        $this->routeParams = $routeParams;
        $this->uri = $uri ? $uri : $_SERVER['REQUEST_URI'];
        //$this->headers = $headers ? $headers : $this->headers();
    }

    /**
     * Check if request is $match
     * @param $pattern string
     * @return bool
     */
    public function is($pattern) {
        if ($pattern == $this->uri) {
            return true;
        }
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool) preg_match('#^'.$pattern.'\z#u', $this->uri);
    }

    /**
     * @return string
     */
    public function method() {
        return $this->method;
    }

    public function uri() {
        return   $this->uri;
    }

    /**
     * Gets client IP
     * @return mixed
     */
    public function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * @return bool
     */
    public function isGET()
    {
        return $this->method === 'GET';
    }

    /**
     * @return bool
     */
    public function isPOST()
    {
        return $this->method === 'POST';
    }

    /**
     * @return bool
     */
    public function isAJAX()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    /**
     * @return bool
     */
    public function isPostAJAX() {
        return $this->method === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    /**
     * @return bool
     */
    public function isPUT()
    {
        return $this->method === 'PUT';
    }

    /**
     * @return bool
     */
    public function isDELETE() {
        return $this->method === 'DELETE';
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function routeParam($key, $default = null) {
        if (isset($this->routeParams[$key])) {
            return $this->routeParams[$key];
        }

        return $default;
    }

    /**
     * @return array
     */
    public function routeParams() {
        return $this->routeParams;
    }

     /**
     * @param $key
     * @param null $default
     * @param bool $trim trim param
     * @return null
     */
    public function param($key, $default = null, $trim = true)
    {
        $params = $this->params();
        if (isset($params[$key])) {
            if (is_string($params[$key])) {
                return $trim ? trim($params[$key]) : $params[$key];
            }
            return $params[$key];
        }

        return $default;
    }



    /**
     * @param $keys array|null Returns only keys
     * @return mixed
     */
    public function params(array $keys = null)
    {
        static $params;

        if (!isset ($params)) {
            if ($this->isGET()) {
                $params = $_GET;
            } else if ($this->isPOST()) {
                if (!empty($_POST)) {
                    $params = $_POST;
                } else {
                    $params = json_decode(file_get_contents("php://input"), true);
                }
            } else {
                parse_str(file_get_contents("php://input"), $params);
            }
        }

        if ($keys === null) {
            return $params;
        }

        $whitelistParams = array();
        foreach ($keys as $key) {
            if (isset($params[$key])) {
                $whitelistParams[$key] = $params[$key];
            }

        }

        return $whitelistParams;
    }

    /**
     * Gets request headers
     * @return array|false
     */
    public function headers()
    {
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        return getallheaders();
    }

    public function __get($name) {
        return $this->data[$name];
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function jsonSerialize () {
        return $this->data;
    }
}
