<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 20.12.2014
 * Time: 23:31
 */

namespace core\db_drivers\sql_builders;

use core\generic\DbDriver;
use core\Utils;

/**
 * Class SqlBuilder
 * @package core\db_drivers\sql_builders
 *
 * Example of usage for select:
 * $sql = (new MySQLiSqlBuilder)
 *     ->select(['id', 'name'])
 *     ->from('users')
 *     ->where('name = ?', 'John')
 *     ->compile();
 *
 * Example of usage for insert:
 * $sql = (new MySQLiSqlBuilder)->insert('users', $data)->compile();
 *
 * Example of usage for update:
 * $sql = (new MySQLiSqlBuilder)->update('users', $data)->where('name = ?', 'John')->compile();
 *
 * Example of usage for delete:
 * $sql = (new MySQLiSqlBuilder)->delete('users')->where('name = ?', 'John')->compile();
 */
abstract class SqlBuilder
{
    const INSERT_QUERY = 1;
    const UPDATE_QUERY = 2;
    const DELETE_QUERY = 3;
    const SELECT_QUERY = 4;
    const CUSTOM_QUERY = 5;

    /**
     * @var string
     */
    protected $bind_marker = '?';

    /**
     * ESCAPE character
     *
     * @var	string
     */
    protected $like_escape_chr = '!';

    /**
     * @var string
     *
     * Name of table for insert|update|delete
     */
    protected $table_name = '';

    /**
     * @var array
     *
     * Data for insert|update
     */
    protected $ins_upd_data = [];

    /**
     * @var string
     */
    protected $custom_sql = '';

    /**
     * @var array
     */
    protected $select = [];

    /**
     * @var array
     */
    protected $from = [];

    /**
     * @var array
     */
    protected $join = [];

    /**
     * @var array
     */
    protected $where = [];

    /**
     * @var array
     */
    protected $order_by = [];

    /**
     * @var bool
     */
    protected $limit = false;

    /**
     * @var bool
     */
    protected $offset = false;

    /**
     * @var array
     */
    protected $binds = [];

    /**
     * @var int
     */
    protected $query_type = 0;

    /**
     * @var \core\generic\DbDriver
     */
    protected $db;

    /**
     * @var mixed
     *
     * Name of id field
     */
    protected $id_field_name;

    /*===============================================================*/
    /*                 I M P L E M E N T A T I O N                   */
    /*===============================================================*/

    /**
     * @return SqlBuilder
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * @param \core\generic\DbDriver $db
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function setDb(DbDriver $db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @return string
     *
     * Make SQL string using escaping
     */
    public function compile()
    {
        $qs = '';
        switch ($this->query_type) {
            case self::INSERT_QUERY:
                $this->binds = array_values($this->ins_upd_data);
                $qs = $this->insertPattern();
                break;
            case self::UPDATE_QUERY:
                $this->binds = array_merge(array_values($this->ins_upd_data), $this->binds);
                $qs = $this->updatePattern()
                    . $this->compileWhereExpr();
                break;
            case self::DELETE_QUERY:
                $qs = $this->deletePattern()
                    . $this->compileWhereExpr();
                break;
            case self::SELECT_QUERY:
                $qs = $this->compileSelectExpr()
                    . $this->compileFromExpr()
                    . $this->compileJoinExpr()
                    . $this->compileWhereExpr()
                    . $this->compileOrderByExpr()
                    . $this->compileLimitExpr();
                break;
            case self::CUSTOM_QUERY:
                $qs = $this->custom_sql;
                break;
        }
        $this->query_type = 0;
        if ($qs === '')
        {
            throw new \LogicException('Query string is empty.'
                . ' Possible causes: incorrect or passing a query type'
                . ' or custom query text is empty', 500);
        }
        return $this->compileBind($qs);
    }

    /**
     * @return \core\db_drivers\query_results\QueryResult|null
     */
    public function run()
    {
        if (empty($this->db)) {
            throw new \RuntimeException('Db driver not defined', 500);
        }
        return $this->db->query($this->compile());
    }

    /*===============================================================*/
    /*                         C U S T O M                           */
    /*===============================================================*/

    public function custom($sql, $binds)
    {
        $this->query_type = self::CUSTOM_QUERY;
        $this->binds = is_array($binds) ? $binds : [$binds];
        $this->custom_sql = $sql;
        return $this;
    }

    /*===============================================================*/
    /*                         I N S E R T                           */
    /*===============================================================*/

    /**
     * @param string $table_name
     * @param array $data
     * @param string $id - field name of identifier
     * @return SqlBuilder
     */
    public function insert($table_name, $data, $id = '')
    {
        $this->query_type = self::INSERT_QUERY;
        $this->table_name = $table_name;
        $this->ins_upd_data = $data;
        $this->id_field_name = $id;
        return $this;
    }

    /**
     * @return string
     *
     * May be overridden
     */
    protected function insertPattern()
    {
        $fields = implode(',', array_keys($this->ins_upd_data));
        $placeholders = implode(
            ',',
            array_fill(
                0, count($this->ins_upd_data), $this->bind_marker
            )
        );
        return ('INSERT' . ' INTO ' . $this->table_name . ' (' . $fields . ') '
            . ' VALUES (' . $placeholders . ')');
    }

    /*===============================================================*/
    /*                         U P D A T E                           */
    /*===============================================================*/

    /**
     * @param string $table_name
     * @param array $data
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function update($table_name, $data)
    {
        $this->query_type = self::UPDATE_QUERY;
        $this->table_name = $table_name;
        $this->ins_upd_data = $data;
        return $this;
    }

    /**
     * @return string
     */
    protected function updatePattern()
    {
        $fields = implode(
            ',',
            array_map(
                function($field)
                {
                    return ($field . ' = ' . $this->bind_marker);
                },
                array_keys($this->ins_upd_data)
            )
        );
        return 'UPDATE ' . $this->table_name . ' SET ' . $fields;
    }

    /*===============================================================*/
    /*                         D E L E T E                           */
    /*===============================================================*/

    /**
     * @param string $table_name
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function delete($table_name)
    {
        $this->query_type = self::DELETE_QUERY;
        $this->table_name = $table_name;
        return $this;
    }

    /**
     * @return string
     */
    protected function deletePattern()
    {
        return 'DELETE' . ' FROM ' . $this->table_name;
    }

    /*===============================================================*/
    /*                         S E L E C T                           */
    /*===============================================================*/

    /**
     * @param array|string $fields
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function select($fields = [])
    {
        $this->query_type = self::SELECT_QUERY;
        $this->select = array_merge($this->select, (!is_array($fields) ? [$fields] : $fields));
        return $this;
    }

    /**
     * @param string|array $table
     * @return $this
     */
    public function from($table)
    {
        $this->from = array_merge($this->from, (!is_array($table) ? [$table] : $table));
        return $this;
    }

    /**
     * @param string $table
     * @param string $on
     * @param string $type should be INNER|LEFT|RIGHT|LEFT OUTER|RIGHT OUTER
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function join($table, $on, $type = 'INNER')
    {
        $this->join[] = [
            'table' => trim($table),
            'on'    => trim($on),
            'type'  => trim($type)
        ];
        return $this;
    }

    /**
     * @param string $expr
     * @param array $binds
     * @param string $operator
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function where($expr, $binds = [], $operator = 'AND')
    {
        $expr = '(' . $expr . ')';
        $operator = (!empty($operator) ? trim($operator) : 'AND');
        if (!empty($this->where)) $expr = ($operator . ' ') . $expr;
        $this->where[] = $expr;

        if (!is_array($binds))
        {
            $binds = [$binds];
        }
        // Make sure we're using numeric keys
        $binds = array_values($binds);
        $this->binds = array_merge($this->binds, $binds);
        return $this;
    }

    /**
     * @param string $expr
     * @param array $binds
     * @param string $operator
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function whereIn($expr, $binds = [], $operator = 'AND')
    {
        $expr = $expr . ' IN (' . implode(',', array_fill(0, count($binds), '?')) . ')';
        return $this->where($expr, $binds, $operator);
    }

    /**
     * @param array $fields
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    public function orderBy($fields = [])
    {
        $this->order_by = array_merge($this->order_by, is_array($fields) ? $fields : [$fields]);
        return $this;
    }

    /**
     * @param int $limit
     * @param bool|int $offset
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    function limit($limit, $offset = false)
    {
        $this->limit = (int) $limit;
        if ($offset !== false) $this->offset = (int) $offset;
        return $this;
    }

    /**
     * @param int $offset
     * @return \core\db_drivers\sql_builders\SqlBuilder
     */
    function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /*===============================================================*/
    /*                      C O M P I L E R S                        */
    /*===============================================================*/

    /**
     * @return string
     */
    protected function compileSelectExpr()
    {
        return 'SELECT ' . (empty($this->select) ? '*': implode(',', $this->select));
    }

    /**
     * @return string
     */
    protected function compileFromExpr()
    {
        if (empty($this->from)) {
            throw new \LogicException('Table name not specified', 500);
        }
        return ' FROM ' . implode(',', $this->from);
    }

    /**
     * @return string
     */
    protected function compileJoinExpr()
    {
        $result = '';
        foreach($this->join as $item)
        {
            $result .= (!empty($item['type']) ? (' ' . $item['type']) : '')
                . ' JOIN ' . $item['table'] . ' ON ' . $item['on'];
        }
        return $result;
    }

    /**
     * @return string
     */
    protected function compileWhereExpr()
    {
        $result = '';
        if (!empty($this->where))
        {
            $result = ' WHERE ' . implode(' ', $this->where);
        }
        return $result;
    }

    /**
     * @return string
     */
    protected function compileOrderByExpr()
    {
        $result = '';
        if (!empty($this->order_by))
        {
            $result = ' ORDER BY ' . implode(', ', $this->order_by);
        }
        return $result;
    }

    /**
     * @return string
     */
    protected function compileLimitExpr()
    {
        if ($this->limit !== false)
        {
            return ' LIMIT ' . ($this->offset ? $this->offset . ', ' : '') . $this->limit;
        }
        return '';
    }

    /**
     * This function has been copied from the framework "CodeIgniter v.3"
     *
     * "Smart" Escape String
     *
     * Escapes data based on type
     * Sets boolean and null types
     *
     * @param	string $param
     * @return	mixed
     */
    public function escape($param)
    {
        if (is_array($param))
        {
            $param = array_map([&$this, 'escape'], $param);
            return $param;
        }
        elseif (is_string($param) OR (is_object($param) && method_exists($param, '__toString')))
        {
            return "'" . $this->escapeString($param) . "'";
        }
        elseif (is_bool($param))
        {
            return ($param === FALSE) ? 0 : 1;
        }
        elseif ($param === NULL)
        {
            return 'NULL';
        }
        return $param;
    }

    /**
     * This function has been copied from the framework "CodeIgniter v.3"
     *
     * Escape String
     *
     * @param	string	$value
     * @param	bool	$like	Whether or not the string will be used in a LIKE condition
     * @return	string
     */
    public function escapeString($value, $like = false)
    {
        if (is_array($value))
        {
            foreach ($value as $key => $val)
            {
                $value[$key] = $this->escapeString($val, $like);
            }
            return $value;
        }

        $value = $this->escapeApostrophe($value);

        // escape LIKE condition wildcards
        if ($like === true)
        {
            return str_replace(
                [
                    $this->like_escape_chr, '%', '_'
                ], [
                $this->like_escape_chr . $this->like_escape_chr,
                $this->like_escape_chr . '%',
                $this->like_escape_chr . '_'
            ],
                $value
            );
        }

        return $value;
    }

    /**
     * This function has been copied from the framework "CodeIgniter v.3"
     *
     * Platform-dependant string escape
     *
     * @param	string $str
     * @return	string
     */
    protected function escapeApostrophe($str)
    {
        return str_replace("'", "''", Utils::removeInvisibleCharacters($str));
    }

    /**
     * Replaces placeholder with values
     * This function has been copied from the framework "CodeIgniter v.3"
     * @param string $sql
     * @return string
     */
    protected function compileBind($sql)
    {
        if (empty($this->binds)
            OR empty($this->bind_marker)
            OR strpos($sql, $this->bind_marker) === false)
        {
            return $sql;
        }
        $bind_count = count($this->binds);

        // We'll need the marker length later
        $ml = strlen($this->bind_marker);

        // Make sure not to replace a chunk inside a string that happens to match the bind marker
        if ($c = preg_match_all("/'[^']*'/i", $sql, $matches)) {
            $c = preg_match_all('/'.preg_quote($this->bind_marker, '/').'/i',
                str_replace($matches[0],
                    str_replace($this->bind_marker, str_repeat(' ', $ml), $matches[0]),
                    $sql, $c),
                $matches, PREG_OFFSET_CAPTURE);

            // Bind values' count must match the count of markers in the query
            if ($bind_count !== $c) {
                return $sql;
            }
        } elseif (($c = preg_match_all(
                '/' . preg_quote($this->bind_marker, '/') . '/i',
                $sql,
                $matches, PREG_OFFSET_CAPTURE)) !== $bind_count)
        {
            return $sql;
        }
        do {
            $c--;
            $escaped_value = $this->escape($this->binds[$c]);
            if (is_array($escaped_value))
            {
                $escaped_value = '('.implode(',', $escaped_value).')';
            }
            $sql = substr_replace($sql, $escaped_value, $matches[0][$c][1], $ml);
        } while ($c !== 0);

        return $sql;
    }
}
