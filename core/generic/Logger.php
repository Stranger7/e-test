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
 * Time: 23:11
 */

namespace core\generic;

/**
 * Class Logger
 * @package core\generic
 */
abstract class Logger
{
    /**
     * Log levels
     */
    const NONE       = 0x0000;
    const INFO       = 0x0001;
    const WARNING    = 0x0002;
    const ERROR      = 0x0004;
    const CRITICAL   = 0x0008;
    const DEBUG      = 0x0010;
    const EMERGENCY  = 0x0020;
    const NOTICE     = 0x0040;
    const SQL        = 0x0080;

    /**
     * self::ALL = self::INFO | self::WARNING | self::ERROR | self::CRITICAL |
     *             self::DEBUG | self::EMERGENCY | self::NOTICE | self::SQL;
     */
    const ALL        = 0x00ff;

    protected static $level_names = [
        self::INFO      => 'INFO',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::CRITICAL  => 'CRITICAL',
        self::DEBUG     => 'DEBUG',
        self::EMERGENCY => 'EMERGENCY',
        self::NOTICE    => 'NOTICE',
        self::SQL       => 'SQL',
        self::ALL       => 'ALL'
    ];

    protected $level          = self::INFO;

    protected $date_format    = 'Y-m-d';
    protected $time_format    = 'H:i:s.u';

    /**
     * First string in batch of messages
     * @var string
     */
    private $title = '';

    /**
     * Whether running logger
     * @var bool
     */
    private $started = false;

    /*===============================================================*/
    /*                         M E T H O D S                         */
    /*===============================================================*/

    public function __construct()
    {}

    /**
     * @return boolean
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Called to start the process of logging
     */
    public function start()
    {
        $this->started = true;
    }

    /**
     * Called to stop the logging
     */
    public function stop()
    {
        $this->started = false;
    }

    /**
     * Specifies of the title
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $level
     */
    public function setLevel($level)
    {
        $this->level = intval($level);
    }

    /**
     * Add to log message with INFO level
     * @param string $message
     * @param array $context
     */
    public function info($message, $context = [])
    {
        if ($this->level & self::INFO) {
            $this->log(self::INFO, $message, $context);
        }
    }

    /**
     * Add to log message with WARNING level
     * @param string $message
     * @param array $context
     */
    public function warning($message, $context = [])
    {
        if ($this->level & self::WARNING) {
            $this->log(self::WARNING, $message, $context);
        }
    }

    /**
     * Add to log message with ERROR level
     * @param string $message
     * @param array $context
     */
    public function error($message, $context = [])
    {
        if ($this->level & self::ERROR) {
            $this->log(self::ERROR, $message, $context);
        }
    }

    /**
     * Add to log message with CRITICAL level
     * @param string $message
     * @param array $context
     */
    public function critical($message, $context = [])
    {
        if ($this->level & self::CRITICAL) {
            $this->log(self::CRITICAL, $message, $context);
        }
    }

    /**
     * Add to log message with DEBUG level
     * @param string $message
     * @param array $context
     */
    public function debug($message, $context = [])
    {
        if ($this->level & self::DEBUG) {
            $this->log(self::DEBUG, $message, $context);
        }
    }

    /**
     * Add to log message with EMERGENCY level
     * @param string $message
     * @param array $context
     */
    public function emergency($message, $context = [])
    {
        if ($this->level & self::EMERGENCY) {
            $this->log(self::EMERGENCY, $message, $context);
        }
    }

    /**
     * Add to log message with NOTICE level
     * @param string $message
     * @param array $context
     */
    public function notice($message, $context = [])
    {
        if ($this->level & self::NOTICE) {
            $this->log(self::NOTICE, $message, $context);
        }
    }

    /**
     * Add to log message with SQL level
     * @param string $message
     * @param array $context
     */
    public function sql($message, $context = [])
    {
        if ($this->level & self::SQL) {
            $this->log(self::SQL, $message, $context);
        }
    }

    /**
     * Add to log message with specified level
     * @param $level
     * @param $message
     * @param array $context
     */
    public function log($level, $message, $context = [])
    {
        if ($this->isStarted() && ($this->level & $level))
        {
            $this->write($this->formatMessage($level, $message, $context));
        }
    }

    /**
     * Returns level name
     * @param string $level
     * @return string
     */
    public function getLevelName($level)
    {
        if (!isset(self::$level_names[$level])) {
            throw new \InvalidArgumentException("Invalid level code ($level)");
        }
        return str_pad('[' . self::$level_names[$level] . ']', 7);
    }

    /**
     * Routine for message adding to log
     * MUST be overridden
     *
     * @param $message
     */
    abstract protected function write($message);

    /**
     * Routine for message formatting
     * MUST be overridden
     *
     * @param int $level
     * @param string $message
     * @param array $context
     * @return string
     */
    abstract protected function formatMessage($level, $message, $context = []);

    /**
     * Returns string with current datetime according specified format
     * @param string $format
     * @return string
     */
    protected function getTimestamp($format)
    {
        $original_time = microtime(true);
        $micro = sprintf("%06d", ($original_time - floor($original_time)) * 1000000);
        $date = new \DateTime(date('Y-m-d H:i:s.'.$micro, $original_time));
        return $date->format($format);
    }

    /**
     * Return IP-address
     * @return string
     */
    protected function getIPAddress()
    {
        return ((isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '');
    }

    /**
     * Add indent to each string
     * @param string $string
     * @param string $indent
     * @return string
     */
    protected function indent($string, $indent = '    ')
    {
        return $indent.str_replace("\n", "\n".$indent, $string);
    }

    /**
     * Returns suffixed string
     * @param string $string
     * @param string $suffix
     * @return string
     */
    protected function withSuffix($string, $suffix = ' ')
    {
        return (!empty($string) ? $string . $suffix : '');
    }

    public static function getLevelCode($level_name)
    {
        $a = array_flip(self::$level_names);
        $level_name = trim(strtoupper($level_name));
        return (isset($a[$level_name]) ? $a[$level_name] : self::NONE);
    }
}
