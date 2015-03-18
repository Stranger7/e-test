<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 29.09.2014
 * Time: 22:28
 */

namespace core;

use core\actuators\DBActuator;
use core\actuators\LoggerActuator;
use core\actuators\RouterActuator;

/**
 * Class Initializer
 * @package core
 */
class Actuator
{
    public static function logger()
    {
        return LoggerActuator::run();
    }

    /**
     * Parse Config-file and DbDriver objects creating.
     */
    public static function databases()
    {
        DBActuator::run();
    }

    /**
     * Extracts routes from config-file
     * @return \core\Router
     */
    public static function router()
    {
        return RouterActuator::run();
    }
}
