<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 06.01.2015
 * Time: 22:15
 */

namespace core\rules;

use core\generic\DbDriver;
use core\generic\Property;

/**
 * Class IsUniqueComposite
 * @package core\rules
 */
class IsUniqueComposite extends IsUnique
{
    /**
     * @var array of Property
     */
    protected $properties = [];

    /**
     * @param DbDriver $db
     * @param string $table_name
     * @param Property $id
     * @param array $properties
     */
    public function __construct(DbDriver $db, $table_name, Property $id, $properties)
    {
        parent::__construct($db, $table_name, $id);
        $this->properties = $properties;
        if (empty($properties)) {
            throw new \RuntimeException('Array of fields for composite unique index is empty');
        }
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $qb = $this->db->select('1')->from($this->table_name);
        /** @var Property $property */
        foreach($this->properties as $property)
        {
            $qb->where("{$property->name()} = ?", $property->preparedForDb());
        }
        if (!$this->id->isEmpty())
        {
            $qb->where("{$this->id->name()} <> ?", $this->id->preparedForDb());
        }
        return !$qb->run()->row();
    }

    /**
     * @return string
     */
    public function getFields()
    {
        $fields = [];
        array_walk($this->properties, function(Property $property) use (&$values, &$fields) {
            $fields[] = $property->name();
        });
        return '"' . implode('", "', $fields) . '"';
    }

    /**
     * @return string
     */
    public function getValues()
    {
        $values = [];
        array_walk($this->properties, function(Property $property) use (&$values, &$fields) {
            $values[] = $property->preparedForDb();
        });
        return '"' . implode('", "', $values) . '"';
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return "values {$this->getValues()} is not unique";
    }
}