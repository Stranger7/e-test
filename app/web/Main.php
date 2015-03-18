<?php
/**
 * Created by PhpStorm.
 * User: Stranger
 * Date: 02.10.2014
 * Time: 22:35
 */

namespace app\web;

use app\models\City;
use core\App;
use core\generic\WebController;

class Main extends WebController
{
    const MIN_COLUMN_COUNT = 1;
    const MAX_COLUMN_COUNT = 10;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        try {
            // Получить число столбцов из конфиг-файла
            $column_count = intval(App::config()->get('main', 'column_count'));
            if (($column_count < self::MIN_COLUMN_COUNT) ||
                ($column_count > self::MAX_COLUMN_COUNT))
            {
                throw new \Exception('Неверное число столбцов в конфиг-файле');
            }

            // Получить данные из БД
            $model = new City();
            $records = $model->entries();
            if (!count($records)) {
                throw new \Exception('Нет данных');
            }

            // Рассчитать число ячеек
            $model->setValues($records[0]);
            $count_with_federal_cell = $model->is_federal->get() ? count($records) + 1 : count($records);
            $cell_count = (int) ceil($count_with_federal_cell / $column_count) * $column_count;
            $row_count = (int) ($cell_count / $column_count);
            $empty_cell_count = (int) ($cell_count - $count_with_federal_cell);

            // Получить список с пустыми ячейками
            $data = [];
            $empty_cell_after_federal = false;
            /** @var \app\models\City $item */
            foreach($model->iterator($records) as $item)
            {
                if (!$item->is_federal->get() && !$empty_cell_after_federal) {
                    $data[] = '';
                    $empty_cell_after_federal = true;
                }
                $data[] = $model->name->get();
                $current_column = (int) floor(count($data) / $row_count);
                if (((count($data) % $row_count) == ($row_count - 1))
                    && $current_column >= ($column_count - $empty_cell_count)) {
                    $data[] = '';
                }
            }

            // загрузить вьюху и отобразить данные
            App::view('table', [
                'column_count' => $column_count,
                'row_count' => $row_count,
                'data' => $data
            ]);
        } catch(\Exception $e) {
            echo $e->getMessage();
        }
    }
}
