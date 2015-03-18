<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 16.12.2014
 * Time: 22:43
 */

namespace core\rules;

use core\generic\DbDriver;
use core\generic\Property;
use core\generic\Rule;

/**
 * Class IsUnique
 * @package core\rules
 */
class IsUnique extends Rule
{
    /**
     * @var DbDriver
     */
    protected $db;

    /**
     * @var string
     */
    protected $table_name;

    /**
     * @var
     */
    protected $id;

    /**
     * @param DbDriver $db
     * @param string $table_name
     * @param Property $id
     */
    public function __construct(DbDriver $db, $table_name, Property $id)
    {
        parent::__construct();
        $this->db = $db;
        $this->table_name = $table_name;
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $qb = $this->db
            ->select('1')
            ->from($this->table_name)
            ->where("{$this->property->name()} = ?", $this->property->preparedForDb());
        if (!$this->id->isEmpty())
        {
            $qb->where("{$this->id->name()} <> ?", $this->id->preparedForDb());
        }
        return !$qb->run()->row();
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return "value '{$this->property->get()}' is not unique for field `{$this->property->name()}`";
    }
}