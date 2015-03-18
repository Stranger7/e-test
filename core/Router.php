<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 28.09.2014
 * Time: 22:57
 */

namespace core;

/**
 * Class Router
 * @package core
 */
class Router
{
    private static $restful_methods = ['GET', 'POST', 'PUT', 'DELETE'];

    /**
     * @var array
     * Example:
     * $routes['LoadOrder'] = [
     *      "allowed_methods"      => ["GET","POST"],
     *      "cleared_uri"          => "order/load",
     *      "required_param_count" => 1,
     *      "optional_param_count" => 2,
     *      "pattern"              => "/^(GET|POST):\/order\/load(\/\w+){1,3}$/i",
     *      "class"                => "app\web\Order",
     *      "method"               => "load",
     * ]
     */
    private $routes = [];

    /**
     * @var string
     */
    private $controller_name = '';

    /**
     * @var string
     */
    private $dsn = '';

    /**
     * @var string
     */
    private $method_name = '';

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var \core\generic\Controller
     */
    private $controller;

    public function __construct() {}

    /**
     * Add route to internal array
     * @param string $name
     * @param array $descriptor
     */
    public function addRoute($name, $descriptor)
    {
        $this->routes[$name] = $descriptor;
    }

    /**
     * @param string $name
     * @return array|bool
     */
    public function descriptor($name)
    {
        return (isset($this->routes[$name]) ? $this->routes[$name] : false);
    }

    /**
     * Returns URL for specified route
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    public function route($name, $params = [])
    {
        $url = false;
        if ($descriptor = $this->descriptor($name)) {
            $url = Utils::url()
                . $descriptor['cleared_uri']
                . (!empty($params) ? '/'  : '') . implode('/', $params);
        }
        return $url;
    }

    /**
     * Get all available REST methods
     * @return array
     */
    public static function getRestfulMethods()
    {
        return self::$restful_methods;
    }

    /**
     * Executes a method with the parameters defined in the URL
     * @throws \RuntimeException
     */
    public function execAction()
    {
        if (!method_exists($this->controller_name, $this->method_name)) {
            throw new \RuntimeException("Method {$this->method_name} "
                . "in class {$this->controller_name} not exist", 404);
        }
        $this->checkParameters();
        $this->controller = new $this->controller_name($this->dsn);
        call_user_func_array([$this->controller, $this->method_name], $this->parameters);
    }

    /**
     * Looking for a suitable controller and method also defines the parameters of the method from URI
     * @throws \RuntimeException
     */
    public function getActionFromURI()
    {
        $request_uri = str_replace(
            ['index.php?/', 'index.php?', 'index.php'],
            '',
            $_SERVER['REQUEST_URI']
        );
        $script_path = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
        if ($request_uri === $script_path) {
            $action = '';
        } else {
            $action = trim(substr($request_uri, strlen($script_path)), '/');
        }
        // cut unexpected GET params
        if (!empty($_GET)) {
            $action = substr($action, 0, strlen($action) - strlen($_SERVER['QUERY_STRING']) - 1);
        }
        $method_action = strtoupper($_SERVER['REQUEST_METHOD']) . ':/' . $action;
        foreach($this->routes as $route => $description)
        {
            if (preg_match($description['pattern'], $method_action) > 0)
            {
                $this->controller_name = $description['class'];
                $this->method_name = $description['method'];
                $this->parameters = $this->parseParameters($action, $description);
                return;
            }
        }
        throw new \RuntimeException('Unable to resolve the request ' . $method_action, 404);
    }

    /**
     * Looking for a suitable controller and method also defines the parameters of the method
     * from command line.
     * @throws \RuntimeException
     */
    public function getActionFromCommandLine()
    {
        if (!isset($_SERVER['argv'])) {
            App::failure(500, 'No parameters. $_SERVER[\'argv\'] is empty. o_O');
        }
        $params = $_SERVER['argv'];
        if (count($params) < 3) {
            App::failure(400, $this->makeErrorMessageForInvalidParamsCLI());
        }
        array_shift($params);

        // set controller
        $this->setControllerName($params[0]);
        array_shift($params);

        // set DSN and Method
        if (strpos($params[0], '--dsn') === 0) {
            $a = explode('=', $params[0]);
            if (sizeof($a) < 2) {
                App::failure(400, $this->makeErrorMessageForInvalidParamsCLI());
            }
            $this->dsn = $a[1];
            array_shift($params);
            if (empty($params[0])) {
                App::failure(400, $this->makeErrorMessageForInvalidParamsCLI());
            }
        }
        $this->setMethodName($params[0]);
        array_shift($params);

        // set parameters
        $method_params = [];
        foreach($params as $param)
        {
            $a = explode('=', $param);
            if (sizeof($a) < 2) {
                App::failure(400, $this->makeErrorMessageForInvalidParamsCLI());
            }
            $method_params[$a[0]] = $a[1];
        }
        $this->setParameters($method_params);
    }

    /**
     * Extracts parameters from URL and returns array with parameters
     *
     * @param string $action
     * @param array $description
     * @return array
     */
    private function parseParameters($action, $description)
    {
        $params = [];
        if ($params_str = substr($action, strlen($description['cleared_uri'])))
        {
            $params = explode('/', trim($params_str, '/'));
        }
        return $params;
    }

    /**
     * @return string
     */
    public function controllerName()
    {
        return $this->controller_name;
    }

    /**
     * @param string $controller_name
     */
    public function setControllerName($controller_name)
    {
        $this->controller_name = str_replace('/', '\\', $controller_name);
    }

    /**
     * @return string
     */
    public function methodName()
    {
        return $this->method_name;
    }

    /**
     * @param string $method_name
     */
    public function setMethodName($method_name)
    {
        $this->method_name = $method_name;
    }

    /**
     * @return string
     */
    public function getActionName()
    {
        return $this->controllerName() . '::' . $this->methodName();
    }

    /**
     * @return generic\Controller
     */
    public function controller()
    {
        return $this->controller;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Presence checking parameters
     */
    private function checkParameters()
    {
        $method = new \ReflectionMethod($this->controllerName(), $this->methodName());
        if (Utils::isCLI()) {
            $this->checkParametersCLI($method->getParameters());
        } else {
            $this->checkParametersWeb($method->getParameters());
        }
    }

    /**
     * @param \ReflectionParameter[] $params
     */
    private function checkParametersCLI($params)
    {
        foreach($params as $param)
        {
            if (!$param->isOptional() && !isset($this->parameters[$param->getName()])) {
                throw new \RuntimeException($this->makeErrorMessageForInvalidParamsCLI(), 400);
            }
        }
    }

    /**
     * @param \ReflectionParameter[] $params
     */
    private function checkParametersWeb($params)
    {
        $i = 0;
        foreach($params as $param)
        {
            if (!$param->isOptional() && !isset($this->parameters[$i])) {
                throw new \RuntimeException($this->makeErrorMessageForInvalidParamsWeb(), 400);
            }
            $i++;
        }
    }

    /**
     * @return string
     */
    private function makeErrorMessageForInvalidParamsCLI()
    {
        if (empty($this->controllerName()) || empty($this->methodName())) {
            return ('Not enough parameters.' . PHP_EOL . 'Syntax:'
                . ' php console.php namespace/controller [--dsn=data_source_name]'
                . ' methods [param1=value1 [ ... ]]' . PHP_EOL);
        }
        $params = (new \ReflectionMethod($this->controllerName(), $this->methodName()))
            ->getParameters();
        $message = 'Not enough parameters.' . PHP_EOL . 'Syntax:' . PHP_EOL
            . 'php crystal.php '
            . $this->controllerName() . ' [--dsn=data_source_name] '
            . $this->methodName();
        /** @var \ReflectionParameter $param */
        foreach($params as $param) {
            if ($param->isOptional()) {
                $message .= ' [' . $param->getName() . '=value]';
            } else {
                $message .= ' ' . $param->getName() . '=value';
            }
        }
        return $message;
    }

    /**
     * @return string
     */
    private function makeErrorMessageForInvalidParamsWeb()
    {
        $params = (new \ReflectionMethod($this->controllerName(), $this->methodName()))
            ->getParameters();
        $message = 'Required parameters: ';
        /** @var \ReflectionParameter $param */
        foreach($params as $param) {
            if ($param->isOptional()) {
                $message .= '[&' . $param->getName() . '=value]';
            } else {
                $message .= '&' . $param->getName() . '=value';
            }
        }
        return $message;
    }
}
