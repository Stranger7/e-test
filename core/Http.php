<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 02.10.2014
 * Time: 1:06
 */

namespace core;

/**
 * Class Http
 * @package core
 */
class Http
{
    private static $codes = [
        200 => '200 OK',
        400 => '400 Bad Request',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported',
        506 => '506 Variant Also Negotiates',
        507 => '507 Insufficient Storage',
        508 => '508 Loop Detected',
        509 => '509 Bandwidth Limit Exceeded',
        510 => '510 Not Extended',
        511 => '511 Network Authentication Required',
    ];

    /**
     * Output specified by $code HTTP-header
     * @param int $code
     * @param array $strings
     */
    public function header($code = 200, $strings = [])
    {
        \header($_SERVER['SERVER_PROTOCOL'] . ' ' . self::getStatusText($code));
        foreach($strings as $string)
        {
            \header($string);
        }
    }

    /**
     * @param string $url
     * @param int $status_code
     */
    public function redirect($url, $status_code = 302)
    {
        \header('Location: ' . $url, true, $status_code);
        echo '<meta http-equiv="Location" content="' . $url . '">';
        echo '<script type="text/javascript">window.location = "' . $url . '"</script>';
        App::terminate('Redirect to ' . $url);
    }

    /**
     * Show forbidden message
     */
    public function forbidden()
    {
        $this->header(403);
        App::view('common/forbidden');
        App::terminate('forbidden');
    }

    /**
     * @param int $code
     * @return string
     */
    public static function getStatusText($code)
    {
        return (isset(self::$codes[$code]) ? self::$codes[$code] : self::$codes[500]);
    }
}
