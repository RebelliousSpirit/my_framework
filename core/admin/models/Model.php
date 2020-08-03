<?php
namespace core\admin\models;

use core\base\controllers\Singleton;
use core\base\models\BaseModel;

class Model extends BaseModel
{
    use Singleton;

    /**
     * Возвращает данные о внешнем ключе таблицы БД(имя поля содержащего внешнии ключи, имя таблицы с которой он связан,
     * имя поля с первичными ключами связанной таблицы)
     * Например: ['parent_id', 'categories', 'id']
     *
     * @param $table - Таблица БД
     * @param bool $key
     * @return array|bool|mixed - массив с данными
     * @throws \core\base\exceptions\DbException
     */
    public function showForeignKeys($table, $key = false){

        $db = DB_NAME;

        if($key) $where = "AND COLUMN_NAME = '$key' LIMIT 1";

        $query = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$table' 
                        AND CONSTRAINT_NAME <> 'PRIMERY' AND REFERENCED_TABLE_NAME is not null $where";

        return $this->query($query);

    }

    /**
     * метод используется в методе updateMenuPosition класса BaseAdmin
     * Метод записывает в поле, отвечающее за позицию в меню(menu_position) указанный порядковый номер.Затем согласно
     * этому изменяет(сдвигает) позиции остальных записей. Если поля в таблице ссылаются не на поля другой таблицы, а
     * на поля своей же таблицы, то при указании порядкового номера в поле menu_position кортежей учитывается их общий
     * родитель.
     *
     * @param string $table - 'table_name' имя таблицы, кортежи которой нужно изменить
     * @param string $row - 'menu_position' поля содержащие позицию в меню (menu_position)
     * @param array $where - ['id' => 2] - придет, если эту запись надо редактировать
     * @param $end_pos - позиция на которой нужно закрепить запись(значение поля menu_position)
     * @param array $update_rows - [] -
     * @return array|bool|mixed|void
     * @throws \core\base\exceptions\DbException
     */
    public function updateMenuPosition($table, $row, $where, $end_pos, $update_rows = []){

        // если измениляется также родитель записи(поле parent_id)
        if ($update_rows && isset($update_rows['where'])){

            $update_rows['operand'] = isset($update_rows['operand']) ? $update_rows['operand'] : ['='];

            if ($where){ // если нужно обновить запись

                // текущие данные записи
                $old_data = $this->get($table, [
                   'fields' => [$update_rows['where'], $row],
                    'where' => [$where]
                ])[0];

                // текущая позиция записи(значение поля menu_position на текущий момент)
                $start_pos = $old_data[$where];

                // если например сменилась категория товара т.е его parent_id
                if ($old_data[$update_rows['where']] !== $_POST[$update_rows['where']]){

                    // подсчитываем сколько записей имеет ту же родительскую категорию, чтобы относительно этого
                    // обновить позиции этих записей
                    $pos = $this->get($table, [
                       'fields' => ['COUNT(*) as count'],
                        'where' => [$update_rows['where'] => $old_data[$update_rows['where']]],
                        'no_concat' => true,
                    ])[0]['count'];

                    // если текущая позиция не последняя среди остальных записей
                    // если последняя то ничего менять не нужно
                    if ($start_pos != pos){

                        $update_where = $this->createWhere([
                            'where' => [$update_rows['where'] => $old_data[$update_rows['where']]],
                            'operand' => $update_rows['operand']
                        ], $table);

                        $query = "UPDATE $table SET $row = $row - 1 $update_where AND $row <= $pos AND $row > $start_pos";

                        $this->query($query, 'u');

                    }

                    // обновляем стартовую позицию(делаем ее последней среди записей имеющих того же родителя(категорию))
                    $start_pos =  $this->get($table, [
                        'fields' => ['COUNT(*) as count'],
                        'where' => [$update_rows['where'] => $_POST[$update_rows['where']]],
                        'no_concat' => true,
                    ])[0]['count'] + 1;

                }

                if(array_key_exists($update_rows['where'], $_POST)) $where_equal = $_POST[$update_rows['where']];
                elseif (isset($old_data[$update_rows['where']])) $where_equal = $old_data[$update_rows['where']];
                else $where_equal = NULL;

                $db_where = $this->createWhere([
                    'where' => [$update_rows['where'] => $where_equal],
                    'operand' => $update_rows['operand']
                ]);

            }else{ // если нужно добавить запись

                // обновляем стартовую позицию(делаем ее последней среди записей имеющих того же родителя(категорию))
                $start_pos =  $this->get($table, [
                    'fields' => ['COUNT(*) as count'],
                    'where' => [$update_rows['where'] => $_POST[$update_rows['where']]],
                    'no_concat' => true,
                ])[0]['count'] + 1;

            }

        }else{

            // если используется метод рекдактирования записи
            if ($where){

                $start_pos = $this->get($table, [
                    'fields' => [$row],
                    'where' => $where
                ])[0][$row];

            }else{// если используется метод добавления записи

                $start_pos = $this->get($table, [
                   'fields' => ['COUNT(*) as count'],
                   'no_concat' => true
                ])[0]['count'] + 1;

            }
        }

        $db_where = isset($db_where) ? $db_where . ' AND' : 'WHERE';

        // меняем значение поля 'menu_position'(или другого поля отвечающего за позицию в меню админке)
        // у кортежей таблицы
        if ($start_pos < $end_pos)
            $query = "UPDATE $table SET $row = $row - 1 $db_where $row <= $end_pos AND $row > $start_pos";
        elseif($start_pos > $end_pos)
            $query = "UPDATE $table SET $row = $row + 1 $db_where $row >= $end_pos AND $row < $start_pos";
        else return;

        return $this->query($query,'u');

    }

}