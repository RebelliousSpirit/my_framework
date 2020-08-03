<?php


namespace core\admin\controllers;


class EditController extends BaseAdmin
{
    // это свойство указывается в форме страницы, указывает в какой контроллер
    // отправлять ее данные
    protected $action = 'add';

    protected function inputData()
    {
        if (!$this->userId) $this->execBase();

        // проводим проверку post-данных, затем их добавляем(редактирование.добавление) в таблицу БД
        // записываем в текущую сессию для удобства пользователя
        $this->checkPostData();

        // получаем имя таблицы и ее поля
        $this->createTableData();

        $this->createForeignData();

        $this->createMenuPosition();

        $this->createRadio();

        $this->createOutputData();

        // указываем какой шаблон подключить. Для контроллеров EditController и AddController
        // используется один и тот же шаблон core/admin/views/add.php
        $this->template = ADMIN_TEMPLATES . 'add';

        $this->createManyToMany();


    }

    /**
     * Проверяет наличие похожих alias в таблице old_alias(содержит данные о старых alias) и если таковые имеются
     * удаляет их и записывает старый alias
     *
     * @param $id - первичный ключ текущей записи
     */
    protected function checkOldAlias($id){

        $tables = $this->model->showTables();

        if (in_array('old_alias', $tables)){

            $old_alias = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => [$this->columns['id_row'] => $id]
            ])[0]['alias'];

            if ($old_alias && $old_alias !== $_POST['alias']){

                // на всякий случай удаляем такие псевдонимы если он(и) конечно есть
                $this->model->delete('old_alias', [
                    'where' => ['alias' => $old_alias, 'table_name' => $this->table]
                ]);

                // на всякий случай удаляем такие псевдонимы если он(и) конечно есть
                $this->model->delete('old_alias', [
                    'where' => ['alias' => $_POST['alias'], 'table_name' => $this->table]
                ]);

                // и добавляем новый
                $this->model->add('old_alias', [
                    'where' => ['alias' => $old_alias, 'table_name' => $this->table, 'table_id' => $id]
                ]);

            }

        }
    }
}