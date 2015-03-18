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
 * Time: 14:11
 */

namespace core\db_drivers;

use core\App;
use core\db_drivers\query_results\MySQLiResult;
use core\db_drivers\sql_builders\MySQLiSQLBuilder;
use core\generic\DbDriver;

/**
 * Example of ini-file section
 *
 * [db:url_shorter]
 * driver = MySQLi
 * host = localhost
 * username = url_shorter
 * password = 1234
 * database = url_shorter
 * port = 3306
 * auto_connect = no
 */

/**
 * Class MySQLi
 * @package core\db_drivers
 */
class MySQLi extends DbDriver
{
    public function __construct()
    {
        parent::__construct();
        $this->setHost(ini_get("mysqli.default_host"));
        $this->setPort(ini_get("mysqli.default_port"));
        $this->setUsername(ini_get("mysqli.default_user"));
        $this->setPassword(ini_get("mysqli.default_pw"));
        $this->setSocket(ini_get("mysqli.default_socket"));
    }

    /*===============================================================*/
    /*            C O N N E C T I O N    M E T H O D S               */
    /*===============================================================*/

    public function connect()
    {
        // if already connected then return
        if ($this->conn) return;
        // do connect...
        $this->conn = @new \mysqli(
            $this->getHost(),
            $this->getUsername(),
            $this->getPassword(),
            $this->getDatabase(),
            $this->getPort(),
            $this->getSocket()
        );
        if ($this->conn->connect_error) {
            throw new \RuntimeException(
                "Can't connect to database {$this->getDatabase()}."
                . " Error({$this->conn->connect_errno}): "
                . trim($this->conn->connect_error),
                500
            );
        }
    }

    /**
     * Close connection
     * @return bool
     */
    public function disconnect()
    {
        if ($this->conn) {
            return $this->conn->close();
        }
        return false;
    }

    /*===============================================================*/
    /*          O V E R R I D D E N      M E T H O D S               */
    /*===============================================================*/

    /**
     * @param \mysqli_result $result
     * @return MySQLiResult
     */
    protected function queryResult($result)
    {
        return new MySQLiResult($result);
    }

    /**
     * @return \core\db_drivers\sql_builders\MySQLiSQLBuilder
     */
    protected function sqlBuilder()
    {
        return new MySQLiSQLBuilder();
    }

    /**
     * Inserts record to table and returns id of record
     * @param string $table_name
     * @param array $data
     * @return mixed
     */
    public function insert($table_name, $data)
    {
        if (!$this->sqlBuilder()->setDb($this)->insert($table_name, $data)->run()) {
            throw new \RuntimeException('Can\'t create entry: ' . $this->getConn()->error, 500);
        }
        return $this->getConn()->insert_id;
    }

    /**
     * Executes query
     * @param string $sql
     * @return mixed
     */
    protected function doQuery($sql)
    {
        App::logger()->sql($sql);

        // Call method mysqli::query
        $result = $this->getConn()->query($sql);
        if (!$result) {
            throw new \RuntimeException("Can't execute query $sql: "
                . "Error({$this->conn->errno}): {$this->conn->error}");
        }
        return (is_bool($result) ? true : $this->queryResult($result));
    }

    /**
     * Create string of field definition
     *
     * @param string $field_name
     * @param array $definition
     * @return string
     */
    protected function makeFieldDefinition($field_name, $definition)
    {
        $result = $field_name;
        if (empty($definition['type'])) {
            throw new \InvalidArgumentException("Type of field $field_name not specified");
        }
        $type = strtoupper(trim($definition['type']));
        if ($type == 'SERIAL')
        {
            $result .= ' BIGINT UNSIGNED NOT NULL AUTO_INCREMENT';
            if (!empty($definition['primary_key'])) {
                $result .= ' PRIMARY KEY';
            } else {
                $result .= ' UNIQUE';
            }
            return $result;
        }
        if (in_array($type, $this->bd_string_data_types)) {
            if (!empty($definition['size']))
            {
                $result .= " VARCHAR({$definition['size']})";
            } else {
                $result .= ' TEXT';
            }
        } elseif (in_array($type, $this->bd_bool_data_types)) {
            $result .=  ' TINYINT(1)';
        } else {
            $result .= ' ' . $type;
        }
        if (in_array($type, $this->bd_int_data_types)) {
            if (!empty($definition['auto_increment'])) {
                $result .= ' AUTO_INCREMENT';
            }
            if (!empty($definition['unsigned'])) {
                $result .= ' UNSIGNED';
            }
        }
        if (!empty($definition['primary_key'])) {
            $result .= ' PRIMARY KEY';
        } elseif (!empty($definition['unique'])) {
            $result .= ' UNIQUE';
        }
        if (!empty($definition['not_null'])) {
            $result .= ' NOT NULL';
        }
        if (!empty($definition['default'])) {
            $result .= ' DEFAULT ' . $definition['default'];
        }
        return $result;
    }
}
