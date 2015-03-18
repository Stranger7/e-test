<?php

namespace app\models;

use core\generic\Model;
use core\rules\IsRequired;

/**
 * Class EventTypes
 * @package app\models
 * @property    \core\property_types\String    name
 * @property    \core\property_types\Bool      is_federal
 */
class City extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->setTableName('cities');
        $this->identifier('id');
        $this->property('name', 'String')
            ->title('Город')
            ->rule(new IsRequired());
        $this->property('is_federal', 'Bool')->title('Федерального значения')->useAsDefault(false);
    }

    public function entries()
    {
        return $this->db
            ->select()
            ->from($this->getTableName())
            ->orderBy(['is_federal DESC, name'])
            ->run()->result();
    }
}
