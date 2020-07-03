<?php


namespace core\base\models;

use core\base\exceptions\DbException;

abstract class BaseModel extends BaseModelMethods
{

    protected $db;

    /**
     * производим подключение к БД
     *
     * BaseModel constructor.
     * @throws DbException
     */
    protected function connectDB()
    {
        // устанавливаем соединение с БД
        $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

        // если произойдет ошибка подключения выкинуть исключение
        if ($this->db->connect_error) {

            throw new DbException('Ошибка подключения к базе данных: ' . $this->db->connect_errno . ' ' .
                $this->db->connect_error);

        }

        $this->db->query("SET NAMES UTF8");

    }

    /**
     * Производит запрос к БД
     *
     * @param $query - тело запроса
     * @param string $crud - 'с' - CREATE / 'r' - SELECT / 'u' - UPDATE / 'd' - DELETE
     * @param bool $return_id - возвращать или нет идентификатор записи
     * @return array|bool|mixed - возвращает массив с данными
     * @throws DbException
     */
    final public function query($query, $crud = 'r', $return_id = false)
    {

        // получаем результат запроса к БД
        $result = $this->db->query($query);

        // если произойдет ошибка запроса выкинуть исключение
        if ($this->db->affected_rows === -1) {
            throw new DbException('Ошибка в SQL - запросе: ' . $query . ' - ' . $this->db->errno . ' ' .
                $this->db->error);
        }

        switch ($crud) {

            case 'r':

                if ($result->num_rows) {

                    $res = [];

                    for ($i = 0; $i < $result->num_rows; $i++) {
                        $res[] = $result->fetch_assoc();
                    }

                    return $res;
                }

                return false;

                break;

            case 'c':
                if ($return_id) return $this->db->insert_id;
                return true;
                break;

            default:
                return true;
                break;

        }

    }

    /**
     * Финальный метод, получает данные из таблицы БД
     *
     * @param $table - таблица из которой получают данные
     * @param array $set - параметры запроса
     * @return array|bool|mixed -
     * @throws DbException
     * [
     *   'fields' => ['id', 'name'],
     *   'where' => ['fio' => 'smirnova', 'name' => 'masha, maria, bob', 'surname' => 'olegovna'],
     *   'no_concat' => true, Если true не присоединять table к полям и where
     *   'operand' => ['=', '<>'], // последний элемент массива автоматически поддставляется в другие отношения
     *   'condition' => ['AND', 'OR'], // последний элемент массива автоматически поддставляется в другие отношения
     *   'order' => ['fio', 'name'],
     *   'order_direction' => ['ASC', 'DESC'], // последний элемент массива автоматически поддставляется в другие отношения
     *   'limit' => '1',
     *   'join' =>[
     *      [
     *          'table' => 'table_name',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'LEFT',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => 'OR',
     *          'on' => ['id', 'parent_id'],
     *          'group_condition' => 'AND',
     *      ],
     *      'join_table' => [
     *          'table' => 'table_name',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'LEFT',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => 'OR',
     *          'on' => ['id', 'parent_id'],
     *          'group_condition' => 'AND',
     *      ],
     *   ]
     *
     * ]
     *
     */
    final public function get($table, $set = [])
    {

        $fields = $this->createFields($set, $table);
        $order = $this->createOrder($set, $table);
        $where = $this->createWhere($set, $table);
        $join_arr = $this->createJoin($set, $table);

        $fields .= $join_arr['fields'];

        $join = $join_arr['join'];

        $where .= $join_arr['where'];

        $fields = rtrim($fields, ',');

        $limit = $set['limit'] ? 'LIMIT ' . $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

        return $this->query($query);

    }

    /**
     * Финальный метод вставки данных в таблицы БД
     *
     * @param $table - таблица для вставки данных
     * @param $set - массив параметров, который м.б пустым, тогда подставятся параметры из массива $_POST
     * fields => [поле => значение]; если не укаазан, то обрабатывается $_POST[поле => значение]
     * разрешена передача например NOW() в качестве функции обычно строкой
     * files =>  [поле => значение]; можно подать массив вида [поле => значение](для одиночных файлов) или [поле =>
     * ['file1', 'file2']] (для нескольких файлов)
     * excerpt =>  [поле => значение]; исключает данные элементы массива из добавления в запрос
     * return_id => true|false - возвращать или нет идентификатор вставленной записи
     * @return array|bool|mixed -
     * @throws DbException
     */
    final public function add($table, $set = [])
    {

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        if (!$set['files'] && !$set['fields']) return false;

        $set['return_id'] = $set['return_id'] ? true : false;
        $set['excerpt'] = (is_array($set['excerpt']) && !empty($set['excerpt'])) ? $set['excerpt'] : false;

        $insert_arr = $this->createInsert($set['fields'], $set['files'], $set['excerpt']);


        //Например: 'INSERT INTO table_name (row_name1, row_name2, date) VALUES (value1, value2, NOW())'
        $query = "INSERT INTO $table {$insert_arr['fields']} VALUES {$insert_arr['values']}";

        return $this->query($query, 'c', $set['return_id']);

    }

    /**
     * Финальный метод редактирования таблиц БД
     *
     * @param $table - таблица, поля которой нужно редактировать
     * @param array $set - параметры для редактирования Например: ['fields' => ['id' => '1', 'name' => 'lena'], 'files'=>
     * '1figure.png'].Если нужно редактировать определенный поля, то в $set добавляется доп.параметр 'where'
     * @return array|bool|mixed - результат операции редактирования false/true
     * @throws DbException
     */
    final public function edit($table, $set = [])
    {

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        if (!$set['files'] && !$set['fields']) return false;

        $set['excerpt'] = (is_array($set['excerpt']) && !empty($set['excerpt'])) ? $set['excerpt'] : false;

        if (!$set['all_rows']) {

            if ($set['where']) {
                $where = $this->createWhere($set);
            } else {
                $columns = $this->showColumns($table);

                if (!$columns) return false;

                if ($columns['id_row'] && $set['fields'][$columns['id_row']]) {
                    $where = 'WHERE ' . $columns['id_row'] . '=' . $set['fields'][$columns['id_row']];
                    unset($set['fields'][$columns['id_row']]);
                }
            }

        }

        $update = $this->createUpdate($set['fields'], $set['files'], $set['excerpt']);

        $query = "UPDATE $table SET $update $where";

        return $this->query($query, 'u');
    }

    /**
     * Финальный метод, который обнуляет или удаляет данные полей таблиц БД
     *
     * @param $table - таблица в которой нужно удалить или обнулить данные полей
     * @param array $set - параметры запроса
     * @return array|bool|mixed -
     * @throws DbException
     * [
     *   'fields' => ['id', 'name'],
     *   'where' => ['fio' => 'smirnova', 'name' => 'masha, maria, bob', 'surname' => 'olegovna'],
     *   'operand' => ['=', '<>'], // последний элемент массива автоматически поддставляется в другие отношения
     *   'condition' => ['AND', 'OR'], // последний элемент массива автоматически поддставляется в другие отношения
     *   'order' => ['fio', 'name'],
     *   'join' =>[ // нужно для того чтобы удалять данные из связанных таблиц
     *      [
     *          'table' => 'table_name',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'LEFT',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => 'OR',
     *          'on' => ['id', 'parent_id'],
     *          'group_condition' => 'AND',
     *      ],
     *      'join_table' => [
     *          'table' => 'table_name',
     *          'fields' => ['id as j_id', 'name as j_name'],
     *          'type' => 'LEFT',
     *          'where' => ['name' => 'sasha'],
     *          'operand' => ['='],
     *          'condition' => 'OR',
     *          'on' => ['id', 'parent_id'],
     *          'group_condition' => 'AND',
     *      ],
     *   ]
     *
     * ]
     *
     */
    final public function delete($table, $set = [])
    {

        $table = trim($table);

        $where = $this->createWhere($set, $table);

        // если вообще полей в таблице БД нет, то остановить скрипт и вернуть false
        $columns = $this->showColumns($table);
        if (!$columns) return false;

        if (is_array($set['fields']) && !empty($set['fields'])) {

            if ($columns['id_row']) {
                $key = array_search($columns['id_row'], $set['fields']);
                if ($key !== false) unset($set['fields'][$key]);
            }

            $fields = [];

            foreach ($set['fields'] as $field) {
                $fields[$field] = $columns[$field]['Default'];
            }

            $update = $this->createUpdate($fields, false, false);

            $query = "UPDATE $table SET $update $where";

        } else {

            $join_arr = $this->createJoin($table, $set);
            $join = $join_arr['join'];
            $join_tables = $join_arr['tables'];

            $query = 'DELETE ' . $table . $join_tables . ' FROM ' . $table . ' ' . $join . ' ' . $where;
        }

        return $this->query($query);

    }

    /**
     * Возвращает инфо о полях таблицы
     * Возвращает массив вида
     * [
     *      row_name => [],
     *      row_name => [],
     *}
     * @param $table - таблица, информацию о полях которой нужно плучить
     * @return array
     * @throws DbException
     */
    final public function showColumns($table)
    {

        $query = "SHOW COLUMNS FROM $table";
        $res = $this->query($query);

        $columns = [];


        if ($res) {
            foreach ($res as $row) {
                // заменяем числовые имена ключей массива на имена полей
                $columns[$row['Field']] = $row;
                // если есть первичный ключ, то вернуть имя этого поля
                // например $columns['id_row'] = 'id';
                if ($row['Key'] === 'PRI') $columns['id_row'] = $row['Field'];

            }
        }

        return $columns;

    }

    /**
     * возвращает данные о всех таблицах в текущей БД
     *
     * @return array - вовзращает массив вида ['table_name1', 'table_name2', 'table_name3']
     * @throws DbException
     */
    final public function showTables()
    {

        $query = 'SHOW TABLES';

        $tables = $this->query($query);

        $table_arr = [];

        if ($tables) {
            foreach ($tables as $table) {
                // получаем первый элемент массива $table т.е имя таблицы
                $table_arr[] = reset($table);
            }
        }

        return $table_arr;
    }
}
