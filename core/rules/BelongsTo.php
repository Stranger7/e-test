<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 17.12.2014
 * Time: 2:03
 */

namespace core\rules;

use core\generic\DbDriver;
use core\generic\Rule;

/**
 * Class BelongsTo
 * @package core\rules
 */
class BelongsTo extends Rule
{
    /**
     * @var DbDriver
     */
    protected $db;

    /**
     * @var string
     */
    protected $referenced_table;

    /**
     * @var string
     */
    protected $referenced_column;

    /**
     * @var string
     */
    protected $on_update = 'RESTRICT';

    /**
     * @var string
     */
    protected $on_delete = 'RESTRICT';

    /**
     * @param DbDriver $db
     * @param string $referenced_table
     * @param string $referenced_column
     * @param string $on_update
     * @param string $on_delete
     */
    public function __construct(DbDriver $db,
                                $referenced_table,
                                $referenced_column,
                                $on_update = 'RESTRICT',
                                $on_delete = 'RESTRICT')
    {
        parent::__construct();
        $this->db = $db;
        $this->referenced_table = $referenced_table;
        $this->referenced_column = $referenced_column;
        $this->on_update = $on_update;
        $this->on_delete = $on_delete;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->db
            ->select()
            ->from($this->referenced_table)
            ->where("{$this->referenced_column} = ?", $this->property->preparedForDb())
            ->run()->row();
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->property->get() . ' does not refer to ' . $this->referenced_table;
    }

    /**
     * @return string
     */
    public function getReferencedTable()
    {
        return $this->referenced_table;
    }

    /**
     * @return string
     */
    public function getReferencedColumn()
    {
        return $this->referenced_column;
    }

    /**
     * @return string
     */
    public function getOnUpdate()
    {
        return $this->on_update;
    }

    /**
     * @return string
     */
    public function getOnDelete()
    {
        return $this->on_delete;
    }
}
