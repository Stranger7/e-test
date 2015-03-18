<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 03.01.2015
 * Time: 20:27
 */

namespace core;

use core\generic\Controller;
use core\interfaces\Migration;

class DbUpgrade extends Controller
{
    const MIGRATION_TABLE           = 'migrations';
    const MIGRATION_DIR             = '../app/cli/migrations';
    const MIGRATION_NAMESPACE       = 'app\\cli\\migrations\\';
    const MAX_MIGRATION_COUNT       = 9999;
    const MIGRATION_FILENAME_PREFIX = 'm';

    /**
     * @var string
     */
    protected $table_name = '';

    public function __construct($dsn = '')
    {
        parent::__construct($dsn);
        $this->table_name = Utils::tableNameWithPrefix($this->db()->getTablePrefix(), self::MIGRATION_TABLE);
    }

    public function init()
    {
        echo 'Migration initializing...';
        $this->db()->createTable($this->table_name, [
            'fields' => [
                'id' => [
                    'type'        => 'INTEGER',
                    'unsigned'    => true,
                    'not_null'    => true,
                    'primary_key' => true
                ],
                'date' => [
                    'type'     => 'TIMESTAMP',
                    'not_null' => true,
                    'default'  => 'now()'
                ]
            ]
        ]);
        echo 'success. Table ' . $this->table_name . ' created.' . PHP_EOL;
    }

    /**
     * Applies migrations with ID is less than or equal to specified bound
     *
     * @param int $bound
     */
    public function up($bound = self::MAX_MIGRATION_COUNT)
    {
        $migration_filename_prefix_len = strlen(self::MIGRATION_FILENAME_PREFIX);
        $has_avail_migration = false;
        foreach($this->loadFiles() as $file)
        {
            $migration_id = intval(substr($file, $migration_filename_prefix_len));
            if (($migration_id <= $bound) && (!$this->migrationApplied($migration_id))) {
                $this->apply($migration_id);
                $has_avail_migration = true;
            }
        }
        if (!$has_avail_migration) {
            echo 'There are no available migrations' . PHP_EOL;
        }
    }

    /**
     * Rollback migrations with ID is greater or equal to specified bound
     * If bound equal -1, then do rollback only last migration
     *
     * @param int $bound
     */
    public function down($bound = -1)
    {
        if ($bound == -1) {
            $bound = $this->getLastMigrationId();
        }
        $migration_filename_prefix_len = strlen(self::MIGRATION_FILENAME_PREFIX);
        $files = $this->loadFiles();
        rsort($files);
        $has_avail_migration = false;
        foreach($this->loadFiles() as $file)
        {
            $migration_id = intval(substr($file, $migration_filename_prefix_len));
            if (($migration_id >= $bound) && ($this->migrationApplied($migration_id))) {
                $this->undo($migration_id);
                $has_avail_migration = true;
            }
        }
        if (!$has_avail_migration) {
            echo 'There are no available migrations' . PHP_EOL;
        }
    }

    /**
     * @return array
     */
    private function loadFiles()
    {
        if (is_dir(self::MIGRATION_DIR)) {
            $migration_filename_pattern = '/' . self::MIGRATION_FILENAME_PREFIX
                . '[0-9]{' . strlen(strval(self::MAX_MIGRATION_COUNT)). '}\.php/';
            $files = scandir(self::MIGRATION_DIR);
            for($i=0; $i<count($files); $i++) {
                if (!preg_match($migration_filename_pattern, $files[$i])) {
                    unset($files[$i]);
                }
            }
            sort($files);
            return $files;
        } else {
            throw new \RuntimeException('Directory ' . self::MIGRATION_DIR . ' not found');
        }
    }

    /**
     * @param int $migration_id
     * @return bool
     */
    private function migrationApplied($migration_id)
    {
        return $this->db()
            ->select()
            ->from($this->table_name)
            ->where('id = ?', $migration_id)
            ->run()->row();
    }

    /**
     * Applies migration with specified id
     *
     * @param int $migration_id
     */
    private function apply($migration_id)
    {
        $class_name = self::MIGRATION_NAMESPACE . self::MIGRATION_FILENAME_PREFIX
            . sprintf('%0' . strlen(strval(self::MAX_MIGRATION_COUNT)). 'd', $migration_id);
        echo $class_name . '...';
        /** @var \core\interfaces\Migration $m */
        $m = new $class_name();
        if ($m instanceof Migration) {
            if ($m->up($this->db())) {
                $this->db()->insert($this->table_name, [
                    'id' => $migration_id
                ], 'id');
                echo 'ok' . PHP_EOL;
            }
        } else {
            echo 'Wrong migration file' . PHP_EOL;
        }
    }

    /**
     * Undo migration with specified id
     *
     * @param int $migration_id
     */
    private function undo($migration_id)
    {
        $class_name = self::MIGRATION_NAMESPACE . self::MIGRATION_FILENAME_PREFIX
            . sprintf('%0' . strlen(strval(self::MAX_MIGRATION_COUNT)). 'd', $migration_id);
        echo $class_name . '...';
        /** @var \core\interfaces\Migration $m */
        $m = new $class_name();
        if ($m instanceof Migration) {
            if ($m->down($this->db())) {
                $this->db()->delete($this->table_name)->where('id = ?', $migration_id)->run();
                echo 'ok' . PHP_EOL;
            }
        } else {
            echo 'Wrong migration file' . PHP_EOL;
        }
    }

    /**
     * @return int
     */
    private function getLastMigrationId()
    {
        $row = $this->db()->select('MAX(id) AS max_id')->from($this->table_name)->run()->row();
        return ($row) ? intval($row->max_id) : 0;
    }
}