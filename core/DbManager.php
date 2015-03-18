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
 * Time: 22:46
 */

namespace core;

use core\generic\DbDriver;
use core\interfaces\Singleton;

/**
 * Class Database
 * @package core
 */
class DbManager implements Singleton
{
    /**
     * Array of DbDriver objects
     * @var array
     */
    private $db_pool = [];

    /**
     * @var null|DbDriver
     */
    private $default_db = null;

    /**
     * It is singleton
     */
    protected static $instance;
    private function __construct() {}
    private function __clone() {}

    /**
     * @return array
     */
    public function getDbPool()
    {
        return $this->db_pool;
    }

    /**
     * @return DbManager
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add DbDriver object to internal array
     * @param string $dsn
     * @param \core\generic\DbDriver $driver
     * @param bool $default
     */
    public function add($dsn, DbDriver $driver, $default = false)
    {
        $this->db_pool[$dsn] = $driver;
        if ($default) {
            $this->default_db = $driver;
        }
    }

    /**
     * Returns connection specified by $dsn from pull
     * @param string $dsn
     * @return \core\generic\DbDriver
     * @throws \InvalidArgumentException
     */
    public function getDb($dsn = '')
    {
        if ($dsn === '') {
            if ($this->default_db === null) {
                if (count($this->db_pool) == 1) {
                    reset($this->db_pool);
                    return current($this->db_pool);
                }
                throw new \InvalidArgumentException("Default connection not defined in config file");
            }
            return $this->default_db;
        }
        if (!isset($this->db_pool[$dsn])) {
            throw new \InvalidArgumentException("Invalid database $dsn");
        }
        return $this->db_pool[$dsn];
    }
}
