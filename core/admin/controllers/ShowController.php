<?php

namespace core\admin\controllers;
use core\base\settings\Settings;

/**
 * Class ShowController - отвечает за отображение и формирование страницы админ. части сайта
 * @package core\admin\controllers
 */
class ShowController extends BaseAdmin
{
    protected function inputData(){

        if (!$this->userId) $this->execBase();

        $this->createTableData();

        $this->createData();

        // get_defined_vars() - получает текущие свойства
        return $this->addExpansion(get_defined_vars());

    }

    /**
     * Записывает в $this->data данные из таблицы БД(либо берется из таблицы по умолчанию, либо из url-запроса)
     *
     * @param array $arr - параметры запроса к БД
     * @return array
     */
    protected function createData($arr = []){

        $fields = [];
        $order = [];
        $order_direction = [];

        if(!$this->columns['id_row']) return $this->data = [];

        // в возвращаемом массиве из БД поле с первичным ключом будет под пседонимом 'id'
        $fields[] = $this->columns['id_row'] . ' as id';

        if ($this->columns['name']) $fields['name'] = 'name';
        if ($this->columns['img']) $fields['img'] = 'img';

        if (count($fields) < 3){
            foreach ($this->columns as $key => $item){
                if (!$fields['name'] && strrpos($key, 'name') !== false) {
                    $fields['name'] = $key . ' as name';
                }
                if (!$fields['img'] && strrpos($key, 'img') === 0) {
                    $fields['img'] = $key . ' as img';
                }
            }
        }

        if ($arr['fields']){
            if (is_array($arr['fields'])){
                $fields = Settings::instance()->arrayMergeRecursive($fields, $arr['fields']);
            } else {
                $fields[] = $arr['fields'];
            }

        }

        if ($this->columns['parent_id']){
            if (!in_array('parent_id', $fields)) $fields[] = 'parent_id';
            $order[] = 'parent_id';
        }

        if ($this->columns['menu_position']){
            $order[] = 'menu_position';
        } elseif ($this->columns['date']){
            if ($order) $order_direction = ['ASC', 'DESC'];
            else $order_direction[] = 'DESC';

            $order[] = 'date';
        }

        if ($arr['order']){
            if (is_array($arr['order'])){
                $order = Settings::instance()->arrayMergeRecursive($order, $arr['order']);
            } else {
                $order[] = $arr['order'];
            }
        }

        if ($arr['order_direction']){
            if (is_array($arr['order_direction'])) {
                $order_direction = Settings::instance()->arrayMergeRecursive($order_direction, $arr['order_direction']);
            } else {
                $order_direction[] = $arr['order_direction'];
            }
        }

        $this->data = $this->model->get($this->table,[
            'fields' => $fields,
            'order' => $order,
            'order_direction' => $order_direction,
        ]);

    }
}