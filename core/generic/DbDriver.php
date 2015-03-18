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
 * Time: 23:36
 */

namespace core\generic;

abstract class DbDriver
{
    const INDEX        = 'INDEX';
    const UNIQUE_INDEX = 'UNIQUE INDEX';
    const PRIMARY_KEY  = 'PRIMARY KEY';

    /**
     * @var resource|\mysqli
     */
    protected $conn = null;

    private $host = '';
    private $port = '';
    private $socket = '';
    private $username = '';
    private $password = '';
    private $database = '';
    private $table_prefix = '';

    protected $bd_int_data_types = [
        'SERIAL',
        'TINYINT',
        'SMALLINT',
        'INT',
        'INTEGER',
        'BIGINT'
    ];

    protected $bd_float_data_types = [
        'DOUBLE',
        'FLOAT'
    ];

    protected $bd_date_data_types = [
        'DATE',
        'DATETIME',
        'TIMESTAMP'
    ];

    protected $bd_string_data_types = [
        'TEXT',
        'VARCHAR',
        'STRING'
    ];

    protected $bd_bool_data_types = [
        'BOOL',
        'BOOLEAN'
    ];

    /*===============================================================*/
    /*                         M E T H O D S                         */
    /*===============================================================*/

    public function __construct() {}

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__;
    }

    /**
     * Create connection with database
     */
    abstract public function connect();

    /**
     * Close connection
     * @return bool
     */
    abstract public function disconnect();

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return DbDriver
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     * @return DbDriver
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return DbDriver
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return DbDriver
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string $database
     * @return DbDriver
     */
    public function setDatabase($database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->table_prefix;
    }

    /**
     * @param string $table_prefix
     * @return DbDriver
     */
    public function setTablePrefix($table_prefix)
    {
        $this->table_prefix = $table_prefix;
        return $this;
    }

    /**
     * Add schema name if specified
     *
     * @param string $table_name
     * @return string
     */
    public function getTableName($table_name)
    {
        return (!empty($this->table_prefix) ? $this->table_prefix . '.' . $table_name : $table_name);
    }

    /**
     * @return string
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param string $socket
     * @return DbDriver
     */
    public function setSocket($socket)
    {
        $this->socket = $socket;
        return $this;
    }

    /**
     * @return resource|\MySQLi
     */
    protected function getConn()
    {
        if (empty($this->conn)) {
            $this->connect();
        }
        return $this->conn;
    }

    /**
     * @param mixed $result
     * @return \core\db_drivers\query_results\QueryResult
     */
    abstract protected function queryResult($result);

    /**
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    abstract protected function sqlBuilder();

    /**
     * @param array $fields
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function select($fields = [])
    {
        return $this->sqlBuilder()->setDb($this)->select($fields);
    }

    /**
     * Inserts record to table and returns id of record
     * @param string $table_name
     * @param array $data
     * @return mixed. Primary key value
     */
    abstract public function insert($table_name, $data);

    /**
     * Updates record
     * @param string $table_name
     * @param array $data
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function update($table_name, $data)
    {
        return $this->sqlBuilder()->setDb($this)->update($table_name, $data);
    }

    /**
     * Deletes record
     * @param $table_name
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function delete($table_name)
    {
        return $this->sqlBuilder()->setDb($this)->delete($table_name);
    }

    /**
     * @param string $sql
     * @param array $binds
     * @return \core\db_drivers\query_results\QueryResult|null
     */
    public function query($sql, $binds = [])
    {
        return empty($binds)
            ? $this->doQuery($sql)
            : $this->sqlBuilder()->setDb($this)->custom($sql, $binds)->run();
    }

    /**
     * Performs query
     * @param string $sql
     * @return null|\core\db_drivers\query_results\QueryResult
     */
    abstract protected function doQuery($sql);

    /*===============================================================*/
    /*                    T R A N S A C T I O N                      */
    /*===============================================================*/

    /**
     * Start transaction.
     * Supported in MySQL and PostgreSQL
     */
    public function beginTransaction()
    {
        $this->doQuery('BEGIN');
    }

    /**
     * Commit transaction.
     * Supported in MySQL and PostgreSQL
     */
    public function commitTransaction()
    {
        $this->doQuery('COMMIT');
    }

    /**
     * Rollback transaction.
     * Supported in MySQL and PostgreSQL
     */
    public function rollbackTransaction()
    {
        $this->doQuery('ROLLBACK');
    }

    /*===============================================================*/
    /*                    C R E A T E    T A B L E                   */
    /*===============================================================*/

    /**
     * @param string $table_name
     * @param array $description
     * @return bool
     */
    public function createTable($table_name, $description)
    {
        if (empty($table_name)) {
            throw new \InvalidArgumentException('Table name not specified');
        }
        if (empty($description['fields'])) {
            throw new \InvalidArgumentException('Describes the fields are not defined');
        }
        $sql = 'CREATE' . ' TABLE ' . $table_name;
        $definitions = [];

        // Field definitions
        foreach($description['fields'] as $field => $definition)
        {
            $definitions[] = $this->makeFieldDefinition($field, $definition);
        }

        // Primary key expression
        if (!empty($description['primary_key'])) {
            $definitions[] = $this->makeIndexExpression($description['primary_key'], 'PRIMARY KEY');
        }

        // Unique index expression
        if (!empty($description['unique'])) {
            foreach($description['unique'] as $index_fields) {
                $definitions[] = $this->makeIndexExpression($index_fields, 'UNIQUE');
            }
        }

        // Foreign keys
        if (!empty($description['foreign_keys'])) {
            foreach($description['foreign_keys'] as $foreign_key) {
                $definitions[] = $this->makeForeignKeyExpression($foreign_key);
            }
        }

        // Concat SQL
        $sql .= ' (' . implode(', ', $definitions) . ')';
        if (!empty($description['options'])) {
            $sql .= ' ' . $description['options'];
        }
        return $this->doQuery($sql);
    }

    /**
     * Create string of field definition
     *
     * @param string $field_name
     * @param array $definition
     * @return string
     */
    abstract protected function makeFieldDefinition($field_name, $definition);

    /**
     * Create primary key or unique clause
     *
     * @param string|array $fields
     * @param string $type
     * @return string
     */
    protected function makeIndexExpression($fields, $type)
    {
        if (!is_array($fields))
        {
            $fields = [$fields];
        }
        return $type . ' (' . implode(',' , $fields) . ')';
    }

    /**
     * Create foreign key expression
     *
     * @param array $foreign_key
     * @return string
     */
    protected function makeForeignKeyExpression($foreign_key)
    {
        if (empty($foreign_key['columns'])) {
            throw new \InvalidArgumentException('Columns of foreign key not specified');
        }
        $columns = $foreign_key['columns'];
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        if (empty($foreign_key['ref_table'])) {
            throw new \InvalidArgumentException('Referenced table for foreign key not specified');
        }

        if (empty($foreign_key['ref_columns'])) {
            throw new \InvalidArgumentException('Referenced columns for foreign key not specified');
        }
        $ref_columns = $foreign_key['ref_columns'];
        if (!is_array($ref_columns)) {
            $ref_columns = [$ref_columns];
        }

        $on_update = empty($foreign_key['on_update']) ? 'RESTRICT' : $foreign_key['on_update'];
        $on_delete = empty($foreign_key['on_delete']) ? 'RESTRICT' : $foreign_key['on_delete'];

        return (
            'FOREIGN KEY'
            . ' (' . implode(',', $columns) . ')'
            . ' REFERENCES ' . $foreign_key['ref_table'] . ' (' . implode(',', $ref_columns) . ')'
            . ' ON UPDATE ' . $on_update
            . ' ON DELETE ' . $on_delete
        );
    }

    /*===============================================================*/
    /*                      D R O P     T A B L E                    */
    /*===============================================================*/

    /**
     * @param string $table_name
     * @return bool
     */
    public function dropTable($table_name)
    {
        if (empty($table_name)) {
            throw new \InvalidArgumentException('Table name not specified');
        }
        $sql = 'DROP' . ' TABLE ' . $table_name . ' CASCADE';
        return $this->doQuery($sql);
    }

    /*===============================================================*/
    /*                   C R E A T E     I N D E X                   */
    /*===============================================================*/

    /**
     * @param string $table_name
     * @param array $fields
     * @param string $index_type
     * @return \core\db_drivers\query_results\QueryResult|null
     */
    public function createIndex($table_name, $fields, $index_type = self::INDEX)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $sql = 'CREATE ' . $index_type
            . ' ' . str_replace('.', '_', $table_name) . '_' . implode('_', $fields)
            . ' ON ' . $table_name
            . ' (' . implode(', ', $fields) . ')';
        return $this->doQuery($sql);
    }
}
