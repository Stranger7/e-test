<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 03.10.2014
 * Time: 13:26
 */

namespace core;

/**
* Class Utils
* @package core
* This class contain only static methods.
*/
class Utils
{
    /**
     * @param array $context
     * @return string
     *
     * @deprecated
     */
    public static function contextToString($context = [])
    {
        $result = '';
        foreach ($context as $key => $value) {
            $result .= "{$key}: ";
            $result .= preg_replace(
                [
                    '/=>\s+([a-zA-Z])/im',
                    '/array\(\s+\)/im',
                    '/^  |\G  /m',
                ],[
                    '=> $1',
                    'array()',
                    '    ',
                ],
                str_replace('array (', 'array(', var_export($value, true))
            );
            $result .= PHP_EOL;
        }
        return str_replace(array('\\\\', '\\\''), array('\\', '\''), rtrim($result));
    }

    /**
     * @param array $var
     * @param string $newline
     * @return string
     */
    public static function arrayToString($var=[], $newline='<br>')
    {
        $result = '';
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                if (is_array($value)) {
                    $result .= (is_int($key) ? '' : ($key . ':' . $newline));
                    $result .= Utils::arrayToString($value, $newline);
                } else {
                    $result .= (is_int($key) ? $value : $key . ': ' . $value) . $newline;
                }
            }
        } else {
            $result = $var;
        }
        return $result;
    }

    /**
     * @param $string
     * @return string
     */
    public static function toCamelCase($string)
    {
        return str_replace(' ', '', ucwords(strtolower(str_replace(['_', '\\', '/'], ' ', $string))));
    }

    /**
     * @param $value
     * @return bool
     */
    public static function boolValue($value)
    {
        if (is_bool($value)) return $value;
        if (is_string($value)) return (in_array(strtolower($value), ['yes', 'true', '1', 'on', 't']));
        if (is_numeric($value)) return boolval($value);
        return false;
    }

    /**
     * This function has been copied from the framework "CodeIgniter v.3"
     *
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     *
     * @param	string
     * @param	bool
     * @return	string
     */
    public static function removeInvisibleCharacters($string, $url_encoded = true)
    {
        $not_displayed = [];

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded) {
            $not_displayed[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
            $not_displayed[] = '/%1[0-9a-f]/';	// url encoded 16-31
        }

        $not_displayed[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do {
            $string = preg_replace($not_displayed, '', $string, -1, $count);
        } while ($count);

        return $string;
    }

    /**
     * This function has been copied from the framework "CodeIgniter v.3"
     *
     * Determines if the current version of PHP is greater then the supplied value
     *
     * @param	string
     * @return	bool	TRUE if the current version is $version or higher
     */
    public static function isPHP($version)
    {
        static $is_php;
        $version = (string) $version;

        if (!isset($is_php[$version]))
        {
            $is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $is_php[$version];
    }

    /**
     * Is CLI?
     * Test to see if a request was made from the command line.
     * @return 	bool
     */
    public static function isCLI()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Is HTTPS?
     *
     * Determines if the application is accessed via an encrypted
     * (HTTPS) connection.
     *
     * @return	bool
     */
    public static function isHTTPS()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        {
            return true;
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        {
            return true;
        }
        elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
        {
            return true;
        }
        return false;
    }

    /**
     * Is AJAX request?
     *
     * Test to see if a request contains the HTTP_X_REQUESTED_WITH header.
     *
     * @return     bool
     */
    public static function isAjaxRequest()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * @param string $entity
     * @return string
     */
    public static function url($entity = '')
    {
        $base = (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['SCRIPT_NAME'])
            ? (
                '//'
                . $_SERVER['HTTP_HOST']
                . str_replace(
                    'index.php',
                    '',
                    str_replace(
                        '//index.php',
                        '/index.php',
                        $_SERVER['SCRIPT_NAME']
                    )
                )
            )
            : '/'
        );
        return $base . (substr($base, -1) == '/' ? '' : '/') . ltrim($entity, '/');
    }

    /**
     * @return string
     */
    public static function currentUrl()
    {
        return '//' . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
    }

    /**
     * @param string $prefix
     * @param string $table_name
     * @return string
     */
    public static function tableNameWithPrefix($prefix, $table_name)
    {
        return (!empty($prefix) ? $prefix . $table_name : $table_name);
    }
}
