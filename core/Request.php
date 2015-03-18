<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08.12.2014
 * Time: 15:42
 */

namespace core;

/**
 * Class Request
 * @package core
 *
 * This class has been copied from the framework CodeIgnter v.3 (See class CI_input) and refactored
 */
class Request
{
    /**
     * IP address of the current user
     *
     * @var    string
     */
    private $ip_address = '';

    /**
     * Allow GET array flag
     *
     * If set to FALSE, then $_GET will be set to an empty array.
     *
     * @var    bool
     */
    private $allow_get_array = true;

    /**
     * Standardize new lines flag
     *
     * If set to TRUE, then newlines are standardized.
     *
     * @var    bool
     */
    private $standardize_newlines;

    /**
     * Enable XSS flag
     *
     * Determines whether the XSS filter is always active when
     * GET, POST or COOKIE data is encountered.
     * Set automatically based on config setting.
     *
     * @var    bool
     */
    private $enable_xss = true;

    /**
     * List of all HTTP request headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Parameters for method DELETE
     *
     * @var array
     */
    private $_DELETE = [];

    /**
     * Parameters for method PUT
     *
     * @var array
     */
    private $_PUT = [];

    /**
     * Enable CSRF flag
     *
     * Enables a CSRF cookie token to be set.
     * Set automatically based on config setting.
     *
     * @var	bool
     */
    protected $enable_csrf = false;

    /**
     * @var Security
     */
    private $security;

    // --------------------------------------------------------------------
    // --------------------------------------------------------------------

    /**
     * Determines whether to globally enable the XSS processing
     * and whether to allow the $_GET array.
     * @param Security $security
     * @throws \Exception
     */
    public function __construct(Security &$security)
    {
        $this->security = $security;

        $this->allow_get_array = Utils::boolValue(
                App::config()->get(Config::GLOBAL_SECTION, 'allow_get_array')
            ) === true;

        $this->enable_xss = Utils::boolValue(
                App::config()->get(Config::SECURITY_SECTION, 'enable_xss_filtering')
            ) === true;

        $this->standardize_newlines = Utils::boolValue(
                App::config()->get(Config::GLOBAL_SECTION, 'standardize_newlines')
            ) === true;

        $this->enable_csrf = Utils::boolValue(
                App::config()->get(Config::SECURITY_SECTION, 'csrf_protection')
            ) === true;

        // Unsupported by PHP RESTful methods
        if ($this->method(true) == 'DELETE') {
            $this->readStreamTo($this->_DELETE);
        } elseif ($this->method(true) == 'PUT') {
            $this->readStreamTo($this->_PUT);
        }

        $this->sanitizeGlobals();
    }

    /**
     * Fetch from array
     *
     * Internal method used to retrieve values from global arrays.
     *
     * @param   array   &$array     $_GET, $_POST, $_COOKIE, $_SERVER, etc.
     * @param   string  $index      Index for item to be fetched from $array
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed
     */
    protected function fetchFromArray(&$array, $index = null, $xss_clean = null)
    {
        is_bool($xss_clean) OR $xss_clean = $this->enable_xss;

        // If $index is NULL, it means that the whole $array is requested
        isset($index) OR $index = array_keys($array);

        // allow fetching multiple keys at once
        if (is_array($index))
        {
            $output = array();
            foreach ($index as $key)
            {
                $output[$key] = $this->fetchFromArray($array, $key, $xss_clean);
            }

            return $output;
        }

        if (isset($array[$index]))
        {
            $value = $array[$index];
        }
        elseif (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) // Does the index contain array notation
        {
            $value = $array;
            for ($i = 0; $i < $count; $i++)
            {
                $key = trim($matches[0][$i], '[]');
                if ($key === '') // Empty notation will return the value as array
                {
                    break;
                }
                if (isset($value[$key]))
                {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }
        return ($xss_clean === true)
            ? $this->security->xssClean($value)
            : $value;
    }

    /**
     * Fetch an item from the GET array
     *
     * @param   string  $index      Index for item to be fetched from $_GET
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed
     */
    public function get($index = null, $xss_clean = null)
    {
        return $this->fetchFromArray($_GET, $index, $xss_clean);
    }

    /**
     * Fetch an item from the POST array
     *
     * @param   string  $index      Index for item to be fetched from $_POST
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed
     */
    public function post($index = null, $xss_clean = null)
    {
        return $this->fetchFromArray($_POST, $index, $xss_clean);
    }

    /**
     * Fetch an item from the PUT array
     *
     * @param   string  $index      Index for item to be fetched from $_POST
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed
     */
    public function put($index = null, $xss_clean = null)
    {
        return $this->fetchFromArray($this->_PUT, $index, $xss_clean);
    }

    /**
     * Fetch an item from the DELETE array
     *
     * @param   string  $index      Index for item to be fetched from $_POST
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed
     */
    public function delete($index = null, $xss_clean = null)
    {
        return $this->fetchFromArray($this->_DELETE, $index, $xss_clean);
    }

    /**
     * Fetch an item from the COOKIE array
     *
     * @param   string  $index      Index for item to be fetched from $_COOKIE
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed
     */
    public function cookie($index = null, $xss_clean = null)
    {
        return $this->fetchFromArray($_COOKIE, $index, $xss_clean);
    }

    /**
     * Fetch an item from the SERVER array
     *
     * @param   string  $index      Index for item to be fetched from $_SERVER
     * @param   bool    $xss_clean  Whether to apply XSS filtering
     * @return  mixed
     */
    public function server($index, $xss_clean = null)
    {
        return $this->fetchFromArray($_SERVER, $index, $xss_clean);
    }

    /**
     * Set cookie
     *
     * Accepts an arbitrary number of parameters (up to 7) or an associative
     * array in the first parameter containing all the values.
     *
     * @param   string|mixed[]  $name        Cookie name or an array containing parameters
     * @param   string          $value       Cookie value
     * @param   int             $expire      Cookie expiration time in seconds
     * @param   string          $domain      Cookie domain (e.g.: '.yourdomain.com')
     * @param   string          $path        Cookie path (default: '/')
     * @param   string          $prefix      Cookie name prefix
     * @param   bool            $secure      Whether to only transfer cookies via SSL
     * @param   bool            $http_only   Whether to only makes the cookie accessible
     *                                       via HTTP (no javascript)
     */
    public function setCookie($name,
                              $value = '',
                              $expire = 0,
                              $domain = '',
                              $path = '/',
                              $prefix = '',
                              $secure = false,
                              $http_only = false)
    {
        if (is_array($name))
        {
            // always leave 'name' in last place, as the loop will break otherwise, due to $$item
            $params = ['value', 'expire', 'domain', 'path', 'prefix', 'secure', 'http_only', 'name'];
            foreach ($params as $param)
            {
                if (isset($name[$param]))
                {
                    $$param = $name[$param];
                }
            }
        }

        if ($prefix === '' && (App::config()->get(Config::COOKIE_SECTION, 'prefix') !== ''))
        {
            $prefix = App::config()->get(Config::COOKIE_SECTION, 'prefix');
        }

        if ($domain == '' && (App::config()->get(Config::COOKIE_SECTION, 'domain') != ''))
        {
            $domain = App::config()->get(Config::COOKIE_SECTION, 'domain');
        }

        if ($path === '/' && App::config()->get(Config::COOKIE_SECTION, 'path') !== '/')
        {
            $path = App::config()->get(Config::COOKIE_SECTION, 'path');
        }

        if ($secure === false && Utils::boolValue(App::config()->get(Config::COOKIE_SECTION, 'secure')) === true)
        {
            $secure = Utils::boolValue(App::config()->get(Config::COOKIE_SECTION, 'secure'));
        }

        if ($http_only === false && Utils::boolValue(App::config()->get(Config::COOKIE_SECTION, 'http_only')) !== false)
        {
            $http_only = Utils::boolValue(App::config()->get(Config::COOKIE_SECTION, 'http_only'));
        }

        if (!is_numeric($expire))
        {
            $expire = time() - 86500;
        } else {
            $expire = ($expire > 0) ? time() + $expire : 0;
        }
        setcookie($prefix.$name, $value, $expire, $path, $domain, $secure, $http_only);
    }

    /**
     * Delete cookie.
     * Set the expiration date to 1-st January 1970
     *
     * @param string $name
     */
    public function removeCookie($name)
    {
        $this->setCookie($name, '', time() - 3600);
    }

    /**
     * Fetch the IP Address
     *
     * Determines and validates the visitor's IP address.
     *
     * @return    string    IP address
     */
    public function ip()
    {
        if ($this->ip_address !== '')
        {
            return $this->ip_address;
        }

        $proxy_ips = App::config()->get(Config::GLOBAL_SECTION, 'proxy_ips');
        if (!empty($proxy_ips) && (!is_array($proxy_ips)))
        {
            $proxy_ips = explode(',', str_replace(' ', '', $proxy_ips));
        }

        $this->ip_address = $this->server('REMOTE_ADDR');

        if ($proxy_ips)
        {
            $spoof = '';
            $headers = [
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP',
                'HTTP_X_CLIENT_IP',
                'HTTP_X_CLUSTER_CLIENT_IP'
            ];
            foreach ($headers as $header)
            {
                if (($spoof = $this->server($header)) !== null)
                {
                    // Some proxies typically list the whole chain of IP
                    // addresses through which the client has reached us.
                    // e.g. client_ip, proxy_ip1, proxy_ip2, etc.
                    sscanf($spoof, '%[^,]', $spoof);

                    if (!$this->ipIsValid($spoof))
                    {
                        $spoof = null;
                    }
                    else
                    {
                        break;
                    }
                }
            }

            if ($spoof)
            {
                for ($i = 0, $c = count($proxy_ips); $i < $c; $i++)
                {
                    // Check if we have an IP address or a subnet
                    if (strpos($proxy_ips[$i], '/') === false)
                    {
                        // An IP address (and not a subnet) is specified.
                        // We can compare right away.
                        if ($proxy_ips[$i] === $this->ip_address)
                        {
                            $this->ip_address = $spoof;
                            break;
                        }

                        continue;
                    }

                    // We have a subnet ... now the heavy lifting begins
                    isset($separator) OR $separator = $this->ipIsValid($this->ip_address, 'ipv6') ? ':' : '.';

                    // If the proxy entry doesn't match the IP protocol - skip it
                    if (strpos($proxy_ips[$i], $separator) === false)
                    {
                        continue;
                    }

                    // Convert the REMOTE_ADDR IP address to binary, if needed
                    if (!isset($ip, $ip_format))
                    {
                        if ($separator === ':')
                        {
                            // Make sure we're have the "full" IPv6 format
                            $ip = explode(':',
                                str_replace('::',
                                    str_repeat(':', 9 - substr_count($this->ip_address, ':')),
                                    $this->ip_address
                                )
                            );

                            for ($i = 0; $i < 8; $i++)
                            {
                                $ip[$i] = intval($ip[$i], 16);
                            }

                            $ip_format = '%016b%016b%016b%016b%016b%016b%016b%016b';
                        } else {
                            $ip = explode('.', $this->ip_address);
                            $ip_format = '%08b%08b%08b%08b';
                        }
                        $ip = vsprintf($ip_format, $ip);
                    }
                    // Split the netmask length off the network address
                    $mask_len = 0;
                    sscanf($proxy_ips[$i], '%[^/]/%d', $net_address, $mask_len);

                    // Again, an IPv6 address is most likely in a compressed form
                    if ($separator === ':')
                    {
                        $net_address = explode(
                            ':',
                            str_replace(
                                '::',
                                str_repeat(':', 9 - substr_count($net_address, ':')),
                                $net_address
                            )
                        );
                        for ($i = 0; $i < 8; $i++)
                        {
                            $net_address[$i] = intval($net_address[$i], 16);
                        }
                    }
                    else
                    {
                        $net_address = explode('.', $net_address);
                    }

                    // Convert to binary and finally compare
                    if (strncmp($ip, vsprintf($ip_format, $net_address), $mask_len) === 0)
                    {
                        $this->ip_address = $spoof;
                        break;
                    }
                }
            }
        }
        if ( ! $this->ipIsValid($this->ip_address))
        {
            return $this->ip_address = '0.0.0.0';
        }
        return $this->ip_address;
    }

    /**
     * Validate IP Address
     *
     * @param   string  $ip     IP address
     * @param   string  $which  IP protocol: 'ipv4' or 'ipv6'
     * @return  bool
     */
    public function ipIsValid($ip, $which = '')
    {
        switch (strtolower($which))
        {
            case 'ipv4':
                $which = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $which = FILTER_FLAG_IPV6;
                break;
            default:
                $which = null;
                break;
        }
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $which);
    }

    /**
     * Fetch User Agent string
     *
     * @param null $xss_clean
     * @return null|string User Agent string or null if it doesn't exist
     */
    public function userAgent($xss_clean = null)
    {
        return $this->fetchFromArray($_SERVER, 'HTTP_USER_AGENT', $xss_clean);
    }

    // --------------------------------------------------------------------

    /**
     * Sanitize Globals
     *
     * Internal method serving for the following purposes:
     *
     *    - Unsets $_GET data, if query strings are not enabled
     *    - Cleans POST, COOKIE and SERVER data
     *    - Standardizes newline characters to PHP_EOL
     *
     * @return    void
     */
    protected function sanitizeGlobals()
    {
        // Is $_GET data allowed? If not we'll set the $_GET to an empty array
        if ($this->allow_get_array === false)
        {
            $_GET = [];
        }
        elseif (is_array($_GET) && count($_GET) > 0)
        {
            foreach ($_GET as $key => $val)
            {
                $_GET[$this->cleanInputKeys($key)] = $this->cleanInputData($val);
            }
        }

        // Clean $_POST Data
        if (is_array($_POST) && count($_POST) > 0)
        {
            foreach ($_POST as $key => $val)
            {
                $_POST[$this->cleanInputKeys($key)] = $this->cleanInputData($val);
            }
        }

        // Clean $_COOKIE Data
        if (is_array($_COOKIE) && count($_COOKIE) > 0)
        {
            // Also get rid of specially treated cookies that might be set by a server
            // or silly application, that are of no use to a CI application anyway
            // but that when present will trip our 'Disallowed Key Characters' alarm
            // http://www.ietf.org/rfc/rfc2109.txt
            // note that the key names below are single quoted strings, and are not PHP variables
            unset(
                $_COOKIE['$Version'],
                $_COOKIE['$Path'],
                $_COOKIE['$Domain']
            );

            foreach ($_COOKIE as $key => $val)
            {
                if (($cookie_key = $this->cleanInputKeys($key)) !== false)
                {
                    $_COOKIE[$cookie_key] = $this->cleanInputData($val);
                }
                else
                {
                    unset($_COOKIE[$key]);
                }
            }
        }

        // Sanitize PHP_SELF
        $_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

        // CSRF Protection check
        if (($this->enable_csrf === true) && (!Utils::isCLI()))
        {
            switch($this->method(true)) {
                case 'POST':
                    $this->security->csrfVerify($_POST);
                    break;
                case 'DELETE':
                    $this->security->csrfVerify($this->_DELETE);
                    break;
                case 'GET':
                    $this->security->csrfSetCookie();
                    break;
            }
        }
    }

    /**
     * Clean Input Data
     *
     * Internal method that aids in escaping data and
     * standardizing newline characters to PHP_EOL.
     *
     * @param   string|string[]  $string  Input string(s)
     * @return  string
     */
    protected function cleanInputData($string)
    {
        if (is_array($string))
        {
            $new_array = [];
            foreach (array_keys($string) as $key)
            {
                $new_array[$this->cleanInputKeys($key)] = $this->cleanInputData($string[$key]);
            }
            return $new_array;
        }

        /* We strip slashes if magic quotes is on to keep things consistent

           NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and
             it will probably not exist in future versions at all.
        */
        if (!Utils::isPHP('5.4') && get_magic_quotes_gpc())
        {
            $string = stripslashes($string);
        }

        // Remove control characters
        $string = Utils::removeInvisibleCharacters($string, false);

        // Standardize newlines if needed
        if ($this->standardize_newlines === TRUE)
        {
            return preg_replace('/(?:\r\n|[\r\n])/', PHP_EOL, $string);
        }
        return $string;
    }

    /**
     * Clean Keys
     *
     * Internal method that helps to prevent malicious users
     * from trying to exploit keys we make sure that keys are
     * only named with alpha-numeric text and a few other items.
     *
     * @param   string    $string    Input string
     * @param   bool    $fatal    Whether to terminate script execution
     *                or to return FALSE if an invalid
     *                key is encountered
     * @return    string|bool
     */
    protected function cleanInputKeys($string, $fatal = true)
    {
        if ( ! preg_match('/^[a-z0-9:_\/|-]+$/i', $string))
        {
            if ($fatal === true)
            {
                return false;
            }
            else
            {
                App::failure(App::EXIT_USER_INPUT, 'Disallowed Key Characters.');
            }
        }
        return $string;
    }

    /**
     * Request Headers
     *
     * @param    bool    $xss_clean    Whether to apply XSS filtering
     * @return    array
     */
    public function requestHeaders($xss_clean = false)
    {
        // If header is already defined, return it immediately
        if (!empty($this->headers))
        {
            return $this->headers;
        }

        // In Apache, you can simply call apache_request_headers()
        if (function_exists('apache_request_headers'))
        {
            return $this->headers = apache_request_headers();
        }

        $this->headers['Content-Type'] = isset($_SERVER['CONTENT_TYPE'])
            ? $_SERVER['CONTENT_TYPE']
            : @getenv('CONTENT_TYPE');

        foreach ($_SERVER as $key => $val)
        {
            if (sscanf($key, 'HTTP_%s', $header) === 1)
            {
                // take SOME_HEADER and turn it into Some-Header
                $header = str_replace('_', ' ', strtolower($header));
                $header = str_replace(' ', '-', ucwords($header));

                $this->headers[$header] = $this->fetchFromArray($_SERVER, $key, $xss_clean);
            }
        }
        return $this->headers;
    }

    /**
     * Get Request Header
     *
     * Returns the value of a single member of the headers class member
     *
     * @param    string        $index        Header name
     * @param    bool        $xss_clean    Whether to apply XSS filtering
     * @return    string|null    The requested header on success or null on failure
     */
    public function getRequestHeader($index, $xss_clean = false)
    {
        if (empty($this->headers))
        {
            $this->requestHeaders();
        }

        if (!isset($this->headers[$index]))
        {
            return null;
        }

        return ($xss_clean === TRUE)
            ? $this->security->xssClean($this->headers[$index])
            : $this->headers[$index];
    }

    /**
     * Get Request Method
     *
     * Return the request method
     *
     * @param   bool    $upper  Whether to return in upper or lower case (default: FALSE)
     * @return  string
     */
    public function method($upper = false)
    {
        return ($upper)
            ? strtoupper($this->server('REQUEST_METHOD'))
            : strtolower($this->server('REQUEST_METHOD'));
    }

    private function readStreamTo(&$array)
    {
        $data = file_get_contents('php://input');
        foreach(explode('&', $data) as $pair)
        {
            if (!empty($pair)) {
                $a = explode('=', $pair, 2);
                $array[urldecode($a[0])] = ((count($a) == 2) ? urldecode($a[1]) : '');
            }
        }
    }
}