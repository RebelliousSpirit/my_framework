<?php


namespace core\admin\controllers;


class EditController extends BaseAdmin
{

    protected function inputData()
    {
       if (!$this->userId) $this->execBase();

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