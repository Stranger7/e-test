<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 25.09.2014
 * Time: 18:40
 */

namespace core;

/**
 * Class Config
 * @package core
 * This class contain only static methods
 */
class Config
{
    const GLOBAL_SECTION            = 'global';
    const SECURITY_SECTION          = 'security';
    const COOKIE_SECTION            = 'cookie';
    const SESSION_SECTION           = 'session';
    const LOGGER_SECTION            = 'logger';
    const ROUTES_SECTION            = 'routes';
    const HTML_DEFAULTS_JS_SECTION  = 'html:defaults:js';
    const HTML_DEFAULTS_CSS_SECTION = 'html:defaults:css';

    // database section signatures
    const DB_PREFIX                 = 'db:';
    const DRIVER_SIGNATURE          = 'driver';
    const DEFAULT_SIGNATURE         = 'default';

    private $items;

    /**
     * load config-file
     * @param string $filename
     * @throws \Exception
     */
    public function __construct($filename)
    {
        $filename = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filename);
        if (!file_exists($filename))
        {
            App::failure(App::INI_FILE_NOT_FOUND, "Can't load INI file");
        }

        $this->items = parse_ini_file($filename, true, INI_SCANNER_RAW);
        if ($this->items === false)
        {
            App::failure(App::INI_FILE_NOT_PARSED, "Can't parse INI file");
        }
    }

    /**
     * If item is empty, then return specified whole section, otherwise item value returned
     * @param string $section
     * @param string $item
     * @param bool $fatal
     * @throws \Exception
     * @return array|string
     */
    public function get($section, $item = '', $fatal = false)
    {
        if (!empty($item))
        {
            if (isset($this->items[$section]) && isset($this->items[$section][$item])) {
                $value = trim($this->items[$section][$item]);
            } else {
                if ($fatal) {
                    throw new \Exception("Param $section.$item not exist in config-file");
                } else {
                    return '';
                }
            }
        } else {
            if (isset($this->items[$section])) {
                $value = $this->items[$section];
            } else {
                if ($fatal) {
                    throw new \Exception("Section $section not exist in config-file");
                } else {
                    return [];
                }
            }
        }
        return $value;
    }

    /**
     * Checks whether there is a section or item in specified section
     * @param string $section
     * @param string $item
     * @return bool
     */
    public function exist($section, $item = '')
    {
        if (!empty($item)) {
            return (isset($this->items[$section]) && isset($this->items[$section][$item]));
        }
        return (isset($this->items[$section]));
    }

    /**
     * Add or update item to hashes of config-file
     * @param string $section
     * @param string $item
     * @param mixed $value
     */
    public function set($section, $item, $value)
    {
        $this->items[$section][$item] = $value;
    }

    /**
     * Set pointer to begin of internal array
     * @return bool|mixed
     */
    public function firstSection()
    {
        return (reset($this->items) === false) ? false : key($this->items);
    }

    /**
     * Move pointer to next position of internal array
     * @return bool|mixed
     */
    public function nextSection()
    {
        return (next($this->items) === false) ? false : key($this->items);
    }
}
