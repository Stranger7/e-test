<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 30.09.2014
 * Time: 11:14
 */

namespace core\actuators;

use core\App;
use core\Config;
use core\interfaces\Actuator;
use core\Router;

/**
 * Static Class RouterInitializer
 * @package core\loaders
 */
class RouterActuator implements Actuator
{
    /**
     * @var string
     */
    private static $description = '';

    /**
     * Parse config-file and creating of route array
     * @return \core\Router
     */
    public static function run()
    {
        $router = new Router();
        foreach(App::config()->get(Config::ROUTES_SECTION) as $route => $description)
        {
            $router->addRoute($route, self::parseRoute($description));
        }
        App::logger()->debug("Routes for [" . App::name(). "] parsed");

        return $router;
    }

    /**
     * Parse string of route from config file
     *
     * @param string $description
     * examples of $description:
     *   / => public\Home::index
     *   /orders => app\web\Order::index
     *   GET:/orders/create => app\web\Orders::create
     *
     *   Called Orders::edit(). Allowed only PUT and POST HTTP-methods
     *       PUT|POST:/orders/edit => app\web\Orders::edit
     *
     *   Called Orders::preview($param1, $param2)
     *       PUT|POST:/orders/preview?/%1/%2 => app\web\Orders::preview
     *
     *   Called Orders::preview($param1, $param2 = 'default value')
     *       PUT|POST:/orders/preview?/%1[/%2] => app\web\Orders::preview
     *
     *   Called Orders::preview($param1 = 'default value 1', $param2 = 'default value 2')
     *       PUT|POST:/orders/preview?[/%1][/%2] => app\web\Orders::preview
     *
     * @return array
     * Example:
     * [
     *      "allowed_methods"      => ["GET","POST"],
     *      "cleared_uri"          => "order/load",
     *      "required_param_count" => 1,
     *      "optional_param_count" => 2,
     *      "pattern"              => "/^(GET|POST):\/order\/load(\/\w+){1,3}$/i",
     *      "class"                => "app\web\Order",
     *      "method"               => "load",
     * ]
     */
    public static function parseRoute($description)
    {
        list($uri, $action) = self::splitToUriAndAction($description);
        return array_merge(self::parseUri($uri), self::parseAction($action));
    }

    /**
     * @param string $description
     * @return array
     */
    private static function splitToUriAndAction($description)
    {
        self::$description = $description;
        $a = explode('=>', $description);
        if (sizeof($a) != 2) {
            throw new \RuntimeException('Invalid route format: ' . $description, 500);
        }
        return [trim($a[0]), trim($a[1])];
    }

    /**
     * @param string $uri
     * @return array
     */
    private static function parseUri($uri)
    {
        $allowed_methods = Router::getRestfulMethods();
        $a = explode(':', $uri, 2);
        if (sizeof($a) == 2)
        {
            $allowed_methods = explode('|', trim(strtoupper($a[0])));
            // Verify $allowed_methods. They must belong to Router::$restful_methods
            array_walk($allowed_methods, function($item) {
                if (!in_array($item, Router::getRestfulMethods())) {
                    throw new \RuntimeException('Invalid request method ' . $item);
                }
            });
            $request_uri = $a[1];
        } else {
            $request_uri = $uri;
        }
        list($cleared_uri,
            $required_param_count,
            $optional_param_count) = self::extractUriAndParamCount(trim(trim($request_uri), '/'));

        return [
            'allowed_methods'      => $allowed_methods,
            'cleared_uri'          => $cleared_uri,
            'required_param_count' => $required_param_count,
            'optional_param_count' => $optional_param_count,
            'pattern'              => self::createPattern(
                $allowed_methods,
                $cleared_uri,
                $required_param_count,
                $optional_param_count
            )
        ];
    }

    /**
     * @param array $allowed_methods
     * @param string $request_uri
     * @param int $required_param_count
     * @param int $optional_param_count
     * @return string
     */
    private static function createPattern($allowed_methods,
                                          $request_uri,
                                          $required_param_count,
                                          $optional_param_count)
    {
        // Add sub-mask for $allowed_methods
        $pattern = '/^(';
        for($i = 0; $i < count($allowed_methods); $i++) {
            $pattern .= $allowed_methods[$i] .
                (($i == (count($allowed_methods)-1)) ? '' : '|');
        }
        $pattern .= '):\/';

        // Add module
        $pattern .= str_replace('/', '\/', $request_uri);

        // Add parameters sub-mask
        $pattern .= '(\/\w+)'
            . '{' . $required_param_count . ',' . ($required_param_count + $optional_param_count) . '}';
        return ($pattern . '$/i');
    }

    /**
     * @param string $uri
     * @return array
     */
    private static function extractUriAndParamCount($uri)
    {
        $cleared_uri = '';
        $required_param_count = 0;
        $optional_param_count = 0;
        if (empty($uri)) {
            $a = [];
        } elseif($uri[0] == '?') {
            $a[0] = '';
            $a[1] = substr($uri, 1);
        } else {
            $a = preg_split('/^(.*)\?(.*)$/U', $uri, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        }
        //example: $uri = '/orders/preview?/%1/%2[/%3][/%4]';
        if (!empty($a)) {
            $cleared_uri = $a[0];
            if (count($a) == 2) {
                $pattern = '/(\[\/\%\d+\])/U';
                $b = preg_split($pattern, $a[1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                $required_param_count = self::parseRequiredParams($b[0]);
                if ($required_param_count > 0) {
                    array_shift($b);
                }
                foreach($b as $seg) {
                    if (!self::isOptionalParam($seg)) {
                        throw new \RuntimeException('Invalid route: ' . self::$description . '. See segment ' . $seg);
                    }
                }
                $optional_param_count = count($b);
            } elseif(count($a) > 2) {
                throw new \RuntimeException('Invalid route: ' . self::$description . '. see URI ' . $uri);
            }
        }
        return [
            $cleared_uri,
            $required_param_count,
            $optional_param_count
        ];
    }

    /**
     * @param string $params
     * @return int
     */
    private static function parseRequiredParams($params)
    {
        $pattern = '/^(\/\%\d+){1,}$/';
        if (preg_match($pattern, $params)) {
            return count(explode('/%', $params)) - 1;
        } else {
            if (!self::isOptionalParam($params)) {
                throw new \RuntimeException('Invalid route: ' . self::$description . '. See segment ' . $params);
            }
        }
        return 0;
    }

    /**
     * Check matching to [/%4]
     *
     * @param string $param
     * @return int
     */
    private static function isOptionalParam($param)
    {
        return preg_match('/^(\[\/\%\d+\])$/', $param);
    }

    /**
     * @param string $action
     * @return array
     */
    private static function parseAction($action)
    {
        $a = explode('::', $action);
        if (sizeof($a) != 2)
        {
            throw new \RuntimeException('Invalid action format: ' . $action, 500);
        }
        return [
            'class'  => trim($a[0]),
            'method' => trim($a[1])
        ];
    }
}
