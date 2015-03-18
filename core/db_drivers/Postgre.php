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
 * Time: 23:51
 */

namespace core\db_drivers
{
    use core\App;
    use core\db_drivers\query_results\PostgreResult;
    use core\db_drivers\sql_builders\PostgreSQLBuilder;
    use core\generic\DbDriver;

    /**
     * Class PostgreSQL
     * @package core\db_drivers
     */
    class Postgre extends DbDriver
    {
        /**
         * @var string
         * Example: host=sheep port=5432 dbname=test user=lamb password=bar
         * See documentation on "pg_connect" function, parameter "connection_string"
         */
        private $connection_string = '';

        /**
         * @var int
         * See documentation on "pg_connect" function, parameter "connect_type"
         */
        private $connect_type = 0;

        /**
         * @var string
         */
        private $options = '';

        public function __construct()
        {
            parent::__construct();
        }

        /*===============================================================*/
        /*            C O N N E C T I O N    M E T H O D S               */
        /*===============================================================*/

        /**
         * function for connecting to database
         */
        public function connect()
        {
            // if already connected then return
            if ($this->conn) return;
            // do connect...
            if ($this->getConnectionString() == '') {
                throw new \InvalidArgumentException('PostgreSQL: empty connection string');
            }
            $this->conn = @pg_connect($this->getConnectionString(), $this->getConnectType());
            if (!$this->conn) {
                throw new \RuntimeException("PostgreSQL: Can't connect to database with connection string "
                    . $this->connectionStringWithoutPassword());
            }
            App::logger()->debug("Connected to database " . $this->connectionStringWithoutPassword());
        }

        /**
         * Close connection
         * @return bool
         */
        public function disconnect()
        {
            if ($this->conn) {
                return pg_close($this->conn);
            }
            return false;
        }

        /**
         * @return string
         */
        public function getConnectionString()
        {
            if (empty($this->connection_string)) {
                $this->makeConnectionString();
            }
            return $this->connection_string;
        }

        private function makeConnectionString()
        {
            $a = [];
            if (!empty($this->getHost()))     $a[] = "host={$this->getHost()}";
            if (!empty($this->getPort()))     $a[] = "port={$this->getPort()}";
            if (!empty($this->getDatabase())) $a[] = "dbname={$this->getDatabase()}";
            if (!empty($this->getUsername())) $a[] = "user={$this->getUsername()}";
            if (!empty($this->getPassword())) $a[] = "password={$this->getPassword()}";
            if (!empty($this->getOptions()))  $a[] = "options='{$this->getOptions()}'";
            $this->connection_string = implode(' ', $a);
        }

        /**
         * @param string $connection_string
         * @return DbDriver
         */
        public function setConnectionString($connection_string)
        {
            $this->connection_string = $connection_string;
            $parts = explode(' ', $connection_string);
            foreach($parts as $param)
            {
                $pair = explode('=', $param, 2);
                $keyword = trim($pair[0]);
                $value = ((sizeof($pair) == 2) ? trim($pair[1]) : '');
                switch ($keyword)
                {
                    case 'host':
                        $this->setHost($value);
                        break;
                    case 'port':
                        $this->setPort($value);
                        break;
                    case 'dbname':
                        $this->setDatabase($value);
                        break;
                    case 'user':
                        $this->setUsername($value);
                        break;
                    case 'password':
                        $this->setPassword($value);
                        break;
                    case 'options':
                        $this->setOptions($value);
                        break;
                }
            }
            return $this;
        }

        /**
         * @return int
         */
        public function getConnectType()
        {
            return $this->connect_type;
        }

        /**
         * @param int $connect_type
         * @return DbDriver
         */
        public function setConnectType($connect_type)
        {
            $this->connect_type = intval($connect_type);
            return $this;
        }

        /**
         * @param string $options
         * @return DbDriver
         */
        public function setOptions($options)
        {
            $this->options = $options;
            return $this;
        }

        /**
         * @return string
         */
        public function getOptions()
        {
            return $this->options;
        }

        /*===============================================================*/
        /*          O V E R R I D D E N      M E T H O D S               */
        /*===============================================================*/

        /**
         * @param resource $result
         * @return \core\db_drivers\query_results\PostgreResult
         */
        protected function queryResult($result)
        {
            return new PostgreResult($result);
        }

        /**
         * @return \core\db_drivers\sql_builders\PostgreSQLBuilder
         */
        protected function sqlBuilder()
        {
            return new PostgreSQLBuilder();
        }

        /**
         * Inserts record to table and returns id of record
         * @param string $table_name
         * @param array $data
         * @param string $id. It is field name
         * @return mixed
         */
        public function insert($table_name, $data, $id = '')
        {
            if (empty($id)) {
                throw new \LogicException('Invalid parameters for createEntry function. ' .
                    'ID field name not specified', 501);
            }
            $row = $this->sqlBuilder()->setDb($this)->insert($table_name, $data, $id)->run()->row();
            if (!$row) {
                throw new \RuntimeException('Internal error. Can\'t create entry');
            }
            return $row->$id;
        }

        /**
         * @param string $sql
         * @return PostgreResult
         */
        protected function doQuery($sql)
        {
            App::logger()->sql($sql);

            $result = @pg_query($this->getConn(), $sql);
            if (!$result) {
                throw new \RuntimeException("Can't execute query $sql: "
                    . pg_last_error($this->getConn()));
            }

            return $this->queryResult($result);
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

            if (!empty($definition['auto_increment'])) {
                $type = 'SERIAL';
            }
            if ($type == 'SERIAL')
            {
                $result .= ' BIGSERIAL';
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
                    $type = "VARCHAR({$definition['size']})";
                } else {
                    $type = ' TEXT';
                }
            } elseif (in_array($type, $this->bd_bool_data_types)) {
                $type = 'BOOLEAN';
            } elseif ($type == 'DATETIME') {
                $type = 'timestamp without time zone';
            }
            $result .= ' ' . $type;
            if (in_array($type, $this->bd_int_data_types)) {
                if (!empty($definition['unsigned'])) {
                    $result .= " CHECK ($field_name > 0)";
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

        /*===============================================================*/
        /*            A U X I L I A R Y      M E T H O D S               */
        /*===============================================================*/

        /**
         * Remove password from connection string for representation in log
         * @return string
         */
        private function connectionStringWithoutPassword()
        {
            return preg_replace(
                '/(password=[.^\S]*)/i',
                'password=****',
                $this->getConnectionString()
            );
        }
    }
}
