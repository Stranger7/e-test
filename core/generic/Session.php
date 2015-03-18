<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 07.12.2014
 * Time: 20:15
 */

namespace core\generic;

use core\App;
use core\Config;
use core\Request;
use core\Utils;

/**
 * Abstract class Session
 * @package core\generic
 */
abstract class Session
{
    /**
     * Session ID
     * @var string
     */
    protected $id = '';

    /**
     * Timestamp of session creation
     * @var int
     */
    protected $created;

    /**
     * Last activity
     * @var int
     */
    protected $updated;

    /**
     * @var string
     */
    protected $user_agent = '';

    /**
     * @var string
     */
    protected $ip_address = '';

    /**
     * Session data
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $cookie_name = 'crystal_session';

    /**
     * the number of SECONDS you want the session to last
     * @var int
     */
    protected $expiration = 7200;

    /**
     * Whether to match the User Agent when reading the session data
     * @var bool
     */
    protected $match_user_agent = true;

    /**
     * Whether to match the user's IP address when reading the session data
     * @var bool
     */
    protected $match_ip = true;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Whether live session
     * @var bool
     */
    private $is_live;

    /*===============================================================*/
    /*                         M E T H O D S                         */
    /*===============================================================*/

    public function __construct()
    {
        $this->cookie_name = ($item = App::config()->get(Config::SESSION_SECTION, 'cookie_name'))
            ? $item
            : $this->cookie_name;

        $this->expiration = ($item = App::config()->get(Config::SESSION_SECTION, 'expiration'))
            ? intval($item)
            : $this->expiration;

        $this->match_user_agent = ($item = App::config()->get(Config::SESSION_SECTION, 'match_user_agent'))
            ? Utils::boolValue($item)
            : $this->match_user_agent;

        $this->match_ip = ($item = App::config()->get(Config::SESSION_SECTION, 'match_ip'))
            ? Utils::boolValue($item)
            : $this->match_ip;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param string $item
     * @return mixed|null
     */
    public function get($item)
    {
        return isset($this->data[$item]) ? $this->data[$item] : null;
    }

    /**
     * @param string $item
     * @param mixed $value
     */
    public function set($item, $value)
    {
        $this->data[$item] = $value;
    }

    /**
     * Unset specified item
     *
     * @param string $item
     */
    public function remove($item)
    {
        if (isset($this->data[$item])) unset($this->data[$item]);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function start(Request $request)
    {
        $this->request = $request;

        if ($this->id = $this->request->cookie($this->cookie_name))
        {
            $do_destroy = false;
            for(;;) {
                if (!$this->load()) {
                    $do_destroy = true;
                    break;
                }
                if (($this->updated + $this->expiration) < time())
                {
                    App::logger()->debug('Session [' . __CLASS__ . '] with id ' . $this->id .  ' expired: '
                        . date('Y-m-d H:i:s', $this->updated) . ' ' . $this->expiration);
                    $do_destroy = true;
                    break;
                }
                if ($this->match_ip && $this->ip_address !== $this->request->ip())
                {
                    $do_destroy = true;
                    App::logger()->debug('Session: ip address mismatched: '
                        . $this->ip_address . ' <> ' . $this->request->ip());
                    break;
                }
                if ($this->match_user_agent && $this->user_agent !== $this->request->userAgent())
                {
                    App::logger()->debug('Session: user agent mismatched');
                    $do_destroy = true;
                }
                break;
            }
            if ($do_destroy) {
                $this->request->removeCookie($this->cookie_name);
                $this->destroy();
                App::logger()->debug('Session destroyed with cookie "' . $this->cookie_name . '"');
                return $this->create();
            } else {
                // refresh cookie
                $this->request->setCookie($this->cookie_name, $this->id, $this->expiration);
            }
            $this->is_live = true;
            return true;
        } else {
            return $this->create();
        }
    }

    /**
     * Assigns Id, IP-address, user agent, etc
     * @return bool
     */
    protected function create()
    {
        $this->id = $this->makeSessionId();
        $this->created = time();
        $this->updated = time();
        $this->ip_address = $this->request->ip();
        $this->user_agent = $this->request->userAgent();
        $this->data = [];

        $this->request->setCookie($this->cookie_name, $this->id, $this->expiration);
        $this->is_live = true;

        App::logger()->debug('Session [' . __CLASS__ . '] created. Id: ' . $this->id
            . ' with cookie "' . $this->cookie_name . '"');

        return true;
    }

    /**
     * @return boolean
     */
    public function isLive()
    {
        return $this->is_live;
    }

    /**
     * Save data to storage
     * @return bool
     */
    protected function save()
    {
        return $this->is_live;
    }

    /**
     * Load data from storage
     * @return bool
     */
    abstract protected function load();

    /**
     * Garbage collector
     * @return bool
     */
    abstract protected function gc();

    /**
     * Delete session
     * @return bool
     */
    public function destroy()
    {
        $this->is_live = false;
        $this->data = [];
        return true;
    }

    /**
     * Operations performed at the end of the script
     * @return bool
     */
    abstract public function close();


    /**
     * Generate a new session id
     *
     * @return	string	Hashed session id
     */
    protected function makeSessionId()
    {
        $new_session_id = '';
        do {
            $new_session_id .= mt_rand();
        } while (strlen($new_session_id) < 32);

        // To make the session ID even more secure we'll combine it with the user's IP
        $new_session_id .= $this->request->ip();

        // Turn it into a hash and return
        return md5(uniqid($new_session_id, true));
    }

    /**
     * Initialization data from the storage
     * @uses-by Session::load
     * @param mixed $data
     * @return bool
     */
    abstract protected function deployFromStorage($data);

    /**
     * Prepare data for storing
     * $uses-by Session::save
     * @param string $operation. May be 'create' or 'update'.
     * @return mixed
     */
    abstract protected function getDataForStore($operation = 'create');
}