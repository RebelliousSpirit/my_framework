<?php


namespace core\admin\controllers;


use core\base\settings\Settings;

class addController extends BaseAdmin
{

    protected $action = 'add';

    /**
     * Загрузка данных
     */
    protected function inputData()
    {
        // если это не пользователь сайта
        if (!$this->userId) $this->execBase();

        $this->createTableData();

        $this->createRadio();

        $this->createForeignData();

        $this->createOutputData();

        $this->createMenuPosition();

        $this->checkPostData();

    }

    /**
     * Учебный метод для демонстрации множественной вставки в таблицу БД
     * Вызвать можно в методе inputData()
     */
    protected function addTeachers()
    {

        $fields = [
            ['name'=>'lena', 'img'=>'1user.png'],
            ['name'=>'vika', 'img'=>'2user.png'],
            ['name'=>'roma', 'img'=>'3user.png'],
        ];

        $this->model->add('teachers', [
            'fields' => $fields,
        ]);

    }

    /**
     * Вспомагательный метод для метода createForeignData
     *
     * @param $arr - массив с данными о внешних ключах текущей таблицы
     * @param $rootItems -  массив с данными о таблицах, которые имеют корневую
     */
    protected function createForeignProperties($arr, $rootItems)
    {

        if (in_array($this->table, $rootItems['tables'])){
            $this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 0;
            $this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];
        }

        // данные о полях таблицы на котороу ссылается текущая таблица
        $columns = $this->model->showColumns($arr['REFERENCED_TABLE_NAME']);

        $name= '';

        if ($columns['name']){
            $name = 'name';
        }else{
            foreach ($columns as $key => $value){
                if (strrpos($key, 'name') !== false){
                    $name = $key . ' as name';
                }
            }

            if (!$name) $name = $columns['id_row'] . ' as name';
        }

        // исключаем случай, когда внешнии ключи таблицы ссылаются на первичные ключи своей же таблицы
        // т.е таблица сслыается связана с собой же
        if ($this->data){
            if ($arr['REFERENCED_TABLE_NAME'] === $this->table){
                $where[$this->columns['id_row']] = $this->data[$this->columns['id_row']];
                $operand[] = '<>';
            }
        }

        $foreign = $this->model->get($arr['REFERENCED_TABLE_NAME'],[
            'fields' => [$arr['REFERENCED_COLUMN_NAME'] . ' as id', $name],
            'where' => $where,
            'operand' => $operand,
        ]);

        if($foreign){

            if ($this->foreignData[$arr['COLUMN_NAME']]){
                foreach ( $foreign as $value){
                    $this->foreignData[$arr['COLUMN_NAME']][] = $value;
                }
            } else {
                $this->foreignData[$arr['COLUMN_NAME']][] = $foreign;
            }

        }

    }

    /**
     * получаем данные о первичных ключах с которыми связаны внешнии ключи текущей таблицы.
     *
     * @param bool $settings - настройки сайта или плагина
     */
    protected function createForeignData($settings = false)
    {

        if (!$settings) $settings = Settings::instance();

        // таблицы имеющие корневую директорию
        $rootItems = $settings->get('rootItems');

        // данные о поле содержащий внешнии ключи текущей таблицы БД
        $keys = $this->model->showForeignKeys($this->table);

        if ($keys){

            foreach ($keys as $item) {
                $this->createForeignProperties($item, $rootItems);
            }

        } elseif ($this->columns['parent_id']){

            $arr['COLUMN_NAME'] = 'parent_id';
            $arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];
            $arr['REFERENCED_TABLE_NAME'] = $this->table;

            $this->createForeignProperties($arr, $rootItems);

        }

        return;

    }

    /**
     * Записывает в $this->foreignData['menu_position'] позиции записей в текущей таблицы
     * Например: если в таблице 2 записи, вернется массив вида
     * [
     *      0 => [id=>1, name=>1],
     *      1 => [id=>2, name=>2],
     *      2 => [id=>3, name=>3],
     * ]
     * Добавляется доп.ячейка т.к эти данные будут использоватся в функционале добавления новой записи и соотвественно
     * доп.ячейка - это и есть добавленая ячейка
     * @param bool $settings настройки сайта или плагина
     */
    protected function createMenuPosition($settings = false)
    {

        if ($this->columns['menu_position']){

            if (!$settings) $settings = Settings::instance();
            $rootItems = $settings::get('rootItems');

            if ($this->columns['parent_id']){

                if (in_array($this->table, $rootItems['tables'])){
                    $where = 'parent_id IS NULL OR parent_id = 0';
                } else {

                    $parent = $this->model->showForeignKeys($this->table, 'parent_id')[0];

                    if($parent){

                        if ($this->table === $parent['REFERENCED_TABLE_NAME']){
                            $where = 'parent_id IS NULL OR parent_id = 0';
                        } else {

                            $columns = $this->model->showColumns($parent['REFERENCED_TABLE_NAME']);

                            if($columns['parent_id']){
                                $order[] = 'parent_id';
                            } else {
                                $order[] = $parent['REFERENCED_COLUMN_NAME'];
                            }

                            $id = $this->model->get($parent['REFERENCED_TABLE_NAME'], [
                                'fields' => [$parent['REFERENCED_COLUMN_NAME']],
                                'order' => $order,
                                'limit' => 1
                            ])[0][$parent['REFERENCED_COLUMN_NAME']];

                            if ($id) $where = ['parent_id' => $id];

                        }

                    } else {
                        $where = 'parent_id IS NULL OR parent_id = 0';
                    }

                }
            }

            $menu_pos = $this->model->get($this->table, [
                'fields' => ['COUNT(*) as count'],
                'where' => $where,
                'no_concat' => true,
            ])[0]['count'] + 1;

            for($i = 1; $i <= $menu_pos; $i++){
                $this->foreignData['menu_position'][$i - 1]['id'] = $i;
                $this->foreignData['menu_position'][$i - 1]['name'] = $i;
            }

        }

        return;

    }

}