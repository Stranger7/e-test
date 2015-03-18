<?php

namespace app\cli\migrations;

use app\models\City;
use core\generic\DbDriver;
use core\interfaces\Migration;

class m0001 implements Migration
{
    public function up(DbDriver $db)
    {
        $model = new City();
        $model->createSchema($db);
        $this->fixtures($model);
        return true;
    }

    public function down(DbDriver $db)
    {
        $model = new City();
        $model->dropSchema($db);
        return true;
    }

    private function fixtures(City $model)
    {
        $cities = [
            ['name' => 'Москва',          'is_federal' => true],
            ['name' => 'Санкт­Петербург', 'is_federal' => true],
            ['name' => 'Архангельск',     'is_federal' => false],
            ['name' => 'Барнаул',         'is_federal' => false],
            ['name' => 'Владивосток',     'is_federal' => false],
            ['name' => 'Воронеж',         'is_federal' => false],
            ['name' => 'Иркутск',         'is_federal' => false],
            ['name' => 'Ставрополь',      'is_federal' => false],
            ['name' => 'Краснодар',       'is_federal' => false],
            ['name' => 'Томск',           'is_federal' => false],
            ['name' => 'Новосибирск',     'is_federal' => false],
            ['name' => 'Тула',            'is_federal' => false],
            ['name' => 'Омск',            'is_federal' => false],
            ['name' => 'Ульяновск',       'is_federal' => false],
            ['name' => 'Рязань',          'is_federal' => false],
            ['name' => 'Хабаровск',       'is_federal' => false],
            ['name' => 'Самара',          'is_federal' => false],
            ['name' => 'Ярославль',       'is_federal' => false],
        ];

        foreach($cities as $item)
        {
            $model->id->clear();
            $model->setValues($item);
            $model->create();
        }
    }
}