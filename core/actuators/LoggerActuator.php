<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10.12.2014
 * Time: 1:25
 */

namespace core\actuators;

use core\App;
use core\Config;
use core\generic\Logger;
use core\interfaces\Actuator;

class LoggerActuator implements Actuator
{
    /**
     * @return Logger
     * @throws \Exception
     */
    public static function run()
    {
        $logger_driver = App::config()->get(Config::LOGGER_SECTION, 'driver');
        if (empty($logger_driver))
        {
            App::failure(500, 'Logger not defined in INI-file');
        }

        /** @var \core\generic\Logger $logger */
        $logger = new $logger_driver();

        // Set levels if specified
        if ($levels = App::config()->get(Config::LOGGER_SECTION, 'levels'))
        {
            $level = Logger::NONE;
            foreach(explode(',', $levels) as $name)
            {
                $level |= Logger::getLevelCode($name);
            }
            $logger->setLevel($level);
        }

        return $logger;
    }
}