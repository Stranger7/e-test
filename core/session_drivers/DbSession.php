<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 09.12.2014
 * Time: 1:28
 */

namespace core\session_drivers;

use core\App;
use core\Config;
use core\interfaces\CanCreateSchema;
use core\generic\DbDriver;
use core\generic\Session;
use core\Utils;

/**
 * Class DbSession
 * @package core\session_drivers
 */
class DbSession extends Session implements CanCreateSchema
{
    /**
     * @var DbDriver
     */
    protected $db;

    /**
     * Table name for storing of sessions
     * @var string
     */
    protected $table_name = 'sessions';

    /*===============================================================*/
    /*                        M E T H O D S                          */
    /*===============================================================*/

    public function __construct(DbDriver $db)
    {
        $this->db = $db;
        if (!($this->db instanceof DbDriver))
        {
            throw new \RuntimeException('Database not defined', 500);
        }

        $this->table_name = ($item = App::config()->get(Config::SESSION_SECTION, 'table_name'))
            ? $item
            : $this->table_name;
        $this->table_name = Utils::tableNameWithPrefix($this->db->getTablePrefix(), $this->table_name);

        parent::__construct();
    }

    /**
     * Init session
     * @return bool|mixed
     */
    protected function create()
    {
        if (parent::create()) {
            return $this->db->insert($this->table_name, $this->getDataForStore('create'), 'id');
        }
        return false;
    }

    /**
     * Save data to DB
     * @return bool
     */
    protected function save()
    {
        if (parent::save())
        {
            $this->updated = time();
            if ($this->db
                ->update($this->table_name, $this->getDataForStore('update'))
                ->where('id = ?', $this->id)
                ->run())
            {
                App::logger()->debug('Session [' . __CLASS__ . '] saved. id: ' . $this->id);
                return true;
            }
        }
        return false;
    }

    /**
     * Load data from DB & expiration check
     * @return bool
     */
    protected function load()
    {
        if (empty($this->id))
        {
            App::logger()->error('Session [' . __CLASS__ . '] without id not loaded');
            return false;
        }
        $row = $this->db
            ->select()
            ->from($this->table_name)
            ->where('id = ?', $this->id)
            ->run()->row();
        if ($row) {
            App::logger()->debug('Session [' . __CLASS__ . '] with id ' . $this->id .  ' loaded');
            return $this->deployFromStorage($row);
        } else {
            App::logger()->error('Session [' . __CLASS__ . '] with id ' . $this->id .  ' not found in DB');
        }

        return false;
    }

    /**
     * @inherited
     *
     * @param object $data
     * @return bool
     */
    protected function deployFromStorage($data)
    {
        $this->created = strtotime($data->created);
        $this->updated = strtotime($data->updated);
        $this->ip_address = $data->ip_address;
        $this->user_agent = $data->user_agent;
        $this->data = unserialize($data->data);
        return true;
    }

    /**
     * @inherited
     *
     * @param string $operation
     * @return array
     */
    protected function getDataForStore($operation = 'create')
    {
        $a = [
            'updated' => date('Y-m-d H:i:s', $this->updated),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'data' => serialize($this->data)
        ];
        if ($operation == 'create') {
            $a['id'] = $this->id;
            $a['created'] = date('Y-m-d H:i:s', $this->created);
        }
        return $a;
    }

    /**
     * Garbage collection
     *
     * This deletes expired session rows from database
     * if the probability percentage is met
     *
     * @return bool
     */
    protected function gc()
    {
        $probability = ini_get('session.gc_probability');
        $divisor = ini_get('session.gc_divisor');

        if (mt_rand(1, $divisor) <= $probability)
        {
            $expire = time() - $this->expiration;
            $this->db
                ->delete($this->table_name)
                ->where('updated < ?', date('Y-m-d H:i:s', $expire))
                ->run();
            App::logger()->debug('Garbage collector performed');
        }
        return true;
    }

    public function destroy()
    {
        return (
            parent::destroy()
            && $this->db
                ->delete($this->table_name)
                ->where('id = ?', $this->id)
                ->run()
        );
    }

    public function close()
    {
        $this->save();
        $this->gc();
    }

    public function createSchema(DbDriver $db = null)
    {
        $this->db->createTable($this->table_name, [
            'fields' => [
                'id' => [
                    'type'        => 'VARCHAR',
                    'size'        => 32,
                    'not_null'    => true,
                    'primary_key' => true
                ],
                'created' => [
                    'type'        => 'DATETIME',
                    'not_null'    => true,
                ],
                'updated' => [
                    'type'        => 'DATETIME',
                    'not_null'    => true,
                ],
                'data' => [
                    'type'        => 'TEXT'
                ],
                'ip_address' => [
                    'type'        => 'TEXT'
                ],
                'user_agent' => [
                    'type'        => 'TEXT'
                ],
            ]
        ]);
        App::logger()->debug("Table {$this->table_name} created");
    }

    public function dropSchema(DbDriver $db = null)
    {
        $this->db->dropTable($this->table_name);
        App::logger()->debug("Table {$this->table_name} dropped");
    }
}