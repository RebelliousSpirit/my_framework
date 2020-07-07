<?php


namespace core\base\models;


abstract class BaseModelMethods
{
    // используется для проверок полей запроса на наличие функции NOW()
    // в методе insert и update
    protected $sqlFunc = ['NOW()'];

    // поля таблицы.
    protected $tableRows;

    /**
     * Возвращает строку с полями, по которым будет сделана выборка из таблиц БД
     * Так же поддерживает множественные join
     * Например: 'table.id, table_one.name'
     *
     * @param bool $table - имя таблицы
     * @param array $set - поля запроса, которые нужно получить из БД
     * @param array $join - join_structure - флаг структурировать ли ответ или нет
     * @return string - поля запроса
     */
    protected function createFields($set, $table = false, $join = false){

        if (array_key_exists('fields', $set) && $set['fields'] === null) return '';

        $fields = '';

        $concat_table = '';
        $alias_table = $table;

        if (!$set['no_concat']){

            $arr = $this->createTableAlias($table);

            $concat_table = $arr['table'] . '.';

            $alias_table = $arr['alias'];

        }

        // флаг для опции "структурировать ли полученные данные после запроса с join"
        $join_structure = false;

        if (($join || isset($set['join_structure']) && $set['join_structure']) && $table){

            $join_structure = true;

            // устанавливаем текущее свойство tableRows
            $this->showColumns($table);

            if (isset($this->tableRows[$table]['multi_id_row'])) $set['fields'] = [];

        }

        $concat_table = $table && !$set['no_concat'] ? $table . '.' : '';

        // если не пришли параметры с полями для выборки
        if (!isset($set['fields']) || !is_array($set['fields']) || !$set['fields']){

            if (!$join){
                $fields = $concat_table . '*,';
            } else {
                foreach ($this->tableRows[$alias_table] as $key => $item){
                    // если это не служебное название поля
                    if ($key !== 'id_row' && $key !== 'multi_id_row'){
                        // создаем псевдоним поля
                        $fields .= $concat_table . $key . ' as TABLE' . $alias_table . 'TABLE_' . $key . ',';
                    }
                }
            }

        } else { // если пришли параметры с полями для выборки

            $id_field = false;

            foreach ($set['fields'] as $field){

                // если в массиве $this->tableRows название ячейки, содержащей имя таблицы совпадет с полем
                if ($join_structure && !$id_field && $this->tableRows[$alias_table] === $field){
                    $id_field = true;
                }

                if ($field){
                    if ($join && $join_structure){
                        // если в fields указан команда 'применить псевдоним' например 'table.name as name_table'
                        if (preg_match('/^(.+)?\s+as\s+(.+)/i', $field, $matches)){
                            $fields .= $concat_table . $matches[1] . ' as TABLE' . $alias_table . 'TABLE_' . $matches[2] . ',';
                        } else {
                            $fields .= $concat_table . $field . ' as TABLE' . $alias_table . 'TABLE_' . $field . ',';
                        }

                    } else {
                        $fields .= $concat_table . $field . ',';
                    }
                }

            }

            if (!$id_field && $join_structure){

                if ($join){
                    $fields .= $concat_table . $this->tableRows[$alias_table]['id_row'] . ' as TABLE' . $alias_table .'TABLE_' .
                        $this->tableRows[$alias_table]['id_row'] . ',';
                } else {
                    $fields .= $concat_table . $this->tableRows[$alias_table]['id_row'] . ',';
                }

            }

        }

        return $fields;

    }

    /**
     * возвращает строку сформированную из значений ячеек с ключом 'order', 'order_direction' массива $set (параметры
     * запроса переданных в виде массива) массива $set запроса.
     * Например: "ORDER BY table_name.id DESC"
     * Если ячейка с ключом 'order_direction' пуста, то подставится по дефолту инструкция 'ASC'
     *
     * @param bool $table - имя таблицы
     * @param $set - поля запроса, по которым нужно отсортировать возвращаемый результат
     * @return string - 'ORDER BY '
     */
    protected function createOrder($set, $table = false){

        $table = ($table && (!isset($set['no_concat']) || !$set['no_concat'])) ?
            $this->createTableAlias($table)['alias'] . '.' : '';
        $order_by = '';

        if (isset($set['order']) && $set['order']) {

            $set['order'] = (array) $set['order'];

            $set['order_direction'] = (isset($set['order_direction']) && $set['order_direction']) ?
                (array) $set['order_direction'] : ['ASC'];

            $order_by = 'ORDER BY ';
            $direct_count = 0;

            foreach ($set['order'] as $order){

                if ($set['order_direction'][$direct_count]){
                    $order_direction = strtoupper($set['order_direction'][$direct_count]);
                    $direct_count ++;
                } else {
                    $order_direction = strtoupper($set['order_direction'][$direct_count - 1]);
                }

                if (in_array($order, $this->sqlFunc)){
                    $order_by .= $order . ',';
                } elseif (is_int($order)) {
                    $order_by .= $order . ' ' . $order_direction . ',';
                } else {
                    $order_by .= $table . $order . ' ' . $order_direction . ',';
                }

            }

            $order_by = rtrim($order_by, ',');
        }

        return $order_by;
    }

    /**
     * возвращает строку сформированную из значений ячеек с ключом 'where', 'operand', 'condition' массива $set запроса.
     * Например: "WHERE id = '1' AND color <> 'red'"
     *
     * @param bool $table - имя таблицы БД
     * @param $set - поля запроса, по которым нужно провести сравнение для возвращение данных из БД
     * @param string $instruction - иструкция запроса
     * @return bool|string
     */
    protected function createWhere($set, $table = false,  $instruction = 'WHERE'){

        $table = ($table && (!isset($set['no_concat']) || !$set['no_concat'])) ?
            $this->createTableAlias($table)['alias'] . '.' : '';

        $where = '';

        if (is_string($set['where'])){
            return $instruction . ' ' . trim($set['where']);
        }

        if (is_array($set['where']) && !empty($set['where'])) {

            $set['operand'] = (is_array($set['operand']) && !empty($set['operand'])) ?
                $set['operand'] : ['='];
            $set['condition'] = (is_array($set['condition']) && !empty($set['condition'])) ?
                $set['condition'] : ['AND'];

            $where = $instruction;
            $o_count = 0;
            $c_count = 0;

            foreach ($set['where'] as $key => $item){

                $where .= ' ';

                // подставляем операнды в строку запроса
                if ($set['operand'][$o_count]){
                    $operand = $set['operand'][$o_count];
                    $o_count ++;
                } else {
                    $operand = $set['operand'][$o_count - 1];
                }

                if ($set['condition'][$c_count]){
                    $condition = strtoupper($set['condition'][$c_count]);
                    $c_count ++;
                } else {
                    $condition = strtoupper($set['condition'][$c_count - 1]);
                }

                // если операнд равен 'IN' или 'NOT IN')
                if ($operand === 'IN' || $operand === 'NOT IN') {

                    //если в параметре выбора строка и это вложенный запрос
                    if (is_string($item) && strrpos($item, 'SELECT') === 0){
                        $in_str = $item;
                    } else { // во всех остальных случаях

                        if (is_array($item)) $temp_item = $item; // если в параметре выбора массив
                            else $temp_item = explode(',' , $item);// если параметры в строчном виде и разделены запятой

                        $in_str = '';

                        foreach ($temp_item as $var){
                            $in_str .= "'" . addslashes(trim($var)) . "',";
                        }
                    }

                    $where .= $table . $key . ' ' . $operand . ' (' . trim($in_str, ',') . ') ' . $condition;

                } elseif (strrpos($operand, 'LIKE') !== false) { // если пришл оператор 'LIKE'

                    $like_template = explode('%', $operand);

                    // Оператор LIKE языка SQL ищет вхождения подстрок в строках
                    // Если в варианте поиска '%параметр' - то ищем вхождение в конце строки т.е строка должна
                    // заканчиватся искомой подстрокой
                    // Если в варианте поиска 'параметр%' - то ищем вхождение в начале строки т.е строка должны начинатся
                    // с искомой подстроки
                    // Если в варианте поиска '%параметр%' - то ищем вхождение в всей строке

                    // вставляем в вариант поиска знак '%'.Если в параметре 'operand' запроса указать '%LIKE', то в
                    // вариант поиска вставится знак '%' в начале, если 'LIKE%', то соотвественно в конце, если '%LIKE%'
                    // то знак '%' вставится в вариант поиска соотвественно
                    // Например ['%LIKE'] приведет к выводу варианта поиска:WHERE table_name.key_name LIKE '%var_name'
                    foreach ($like_template as $lt_key => $lt_item){
                        if (!$lt_item){
                            if (!$lt_key){
                                $item = '%' . $item;
                            } else {
                                $item .= '%';
                            }
                        }
                    }

                    $where .= $table . $key . ' LIKE ' . "'" . addslashes($item) . "' $condition";
                } else { // в случае если пришли другие операторы(=, <>(не равно))

                    // если это вложенный запрос
                    if (strrpos($item, 'SELECT') === 0){
                        $where .= $table . $key . $operand . '(' . $item . ") $condition";
                    }else{
                        $where .= $table . $key . $operand . "'" . addslashes($item) . "' $condition";
                    }

                }

            }

            // убираем в конце строки подстроку из параметра 'condition'(это может быть AND или OR)
            $where = substr($where, 0, strrpos($where, $condition));

        }

        return $where;
    }

    /**
     * Формирует массив с данными для формирования строки SQL-запроса для соединения данных из нескольких таблицы БД
     *  т.е для выполнения инструкции JOIN
     * 'join' => [
     *   'table_name' => [// имя текущей таблицы
     *          'table' => 'join_name', // имя текущей таблицы, если в качестве ключа указана имя текущей таблицы
     *                                  // то этот параметр можно не указывать
     *          'fields' => ['id', 'name'],
     *          'type' => 'LEFT', // необзятельный параметр по умолчанию будет применятся инструкция LEFT
     *          'where' =>  ['name' => 'masha',],
     *          'operand' => ['='],
     *          'condition' => ['AND'],
     *          // параметры присоединения т.е к какой табл. присоеденять и по каким параметрам
     *          'on' => [
     *              'table' => 'join_table2', // явно указываем с какой таблицей(левой) будет стыковать текущую(правая)
     *              'fields' => ['id', 'parent_id'], // 1 параметром указываем поле левой таблицы, 2 параметром поле
     *                                           // правой таблицы(текущей)
     *          ]
     *          //альтернативная запись ключа 'on'
     *          'on' => ['id', 'parent_id']
     *   ]
     *
     * @param $table - таблица, с данными которой по умолчанию будут соединятся данные из другой таблицы
     * @param $set - парметры запроса
     * @param bool $new_where
     * @return array
     */
    protected function createJoin($set, $table, $new_where = false){

        $fields = '';
        $join = '';
        $where = '';

        // если есть ячейка с параметрами для инструкции JOIN
        if($set['join']){

            // табл. с которой нужно объеденить
            $join_table = $table;

            foreach ($set['join'] as $key => $item) {

                // если в качестве ключа не пришло имя таблицы
                if (is_int($key)) {
                    // если не пришло имя таблицы в ячейке 'table'
                    if(!$item['table']) continue;
                    else $key = $item['table'];
                }

                $concat_table = $this->createTableAlias($key)['alias'];

                if ($join) $join .= ' ';

                // если есть ячейка с параметрами 2-ой табл. по которым будем присоединять
                if (isset($item['on']) && $item['on']) {

                    if (isset($item['on']['fields']) && is_array($item['on']['fields'])
                        && count($item['on']['fields']) === 2){
                        $join_fields = $item['on']['fields'];
                    } elseif (count($item['on']) === 2){
                        $join_fields = $item['on'];
                    } else {
                        continue;
                    }

                    // если не пришел тип присоединения, то по умолчанию будет использоватся инструкция LEFT
                    if (!$item['type']) $join .= 'LEFT JOIN ';
                        else $join .= trim(strtoupper($item['type'])) . ' JOIN ';

                    $join .= $key . ' ON ';

                    if($item['on']['table']) $join_temp_table = $item['on']['table'];
                        else $join_temp_table = $join_table;

                    $join .= $this->createTableAlias($join_temp_table)['alias'];

                    $join .= '.' . $join_fields[0] . '=' . $concat_table . '.' . $join_fields[1];

                    $join_table = $key;

                    if($new_where){
                        if ($item['where']){
                            $new_where = false;
                        }

                        $group_condition = 'WHERE';
                    } else {
                        $group_condition = $item['group_condition'] ? strtoupper($item['group_condition']) : 'AND';
                    }

                    $fields .= $this->createFields($item, $key, $set['join_structure']);
                    $where .= $this->createWhere($item, $key, $group_condition);

                }
            }
        }

        return compact('fields', 'join', 'where');
    }

    /**
     * формирует массив с параметрами для SQL-запроса добавление данных в таблицы БД
     * поддерживает как множестенную так и одиночную вставку в таблицу БД
     *
     * @param $fields - fields => ['поле'=>'значение'] - одиночная вставка,
     * fields => [['поле'=>'значение', 'поле'=>'значение'],['поле'=>'значение','поле'=>'значение']] - множественная
     * вставка. Если в первом массиве подать больше пар поле=>значение, то в результирующем массиве
     *  $insert_arr['values'] запишутся пустые строки соотвествующие парам поле=>значение других массивов
     * @param $files - files => ['поле'=>'значение']
     * @param $excerpt - excerpt => ['поле таблицы', 'поле таблицы'] - поля , которые нужно исключить
     * @return array Например: ['fields' => '(id, name)', 'values' => '(1, 'Vika')']
     */
    protected function createInsert($fields, $files, $excerpt){

        // данные для формирования SQL-запроса
        $insert_arr = [];

        $insert_arr['fields'] = '(';

        $array_type = array_keys($fields)[0];

        // если ключ массива числовой т.е это множественная вставка
        if (is_int($array_type)){

            $check_fields = false; // флаг для проверки пройден ли до конца в цикле подмассив массива $fields
            $count_fields = 0; // кол-во ячеек в подмассиве массива $fields

            foreach ($fields as $i => $item){

                $insert_arr['values'] .= '(';

                if (!$count_fields) $count_fields = count($fields[$i]);

                $j = 0; // кол-во ячеек в подмассиве массива $fields

                foreach ($item as $row => $value){

                    if ($excerpt && in_array($row, $excerpt)) continue;

                    if (!$check_fields) $insert_arr['fields'] .= $row . ',';

                    // если передали фунцию 'NOW()'
                    if (in_array($value, $this->sqlFunc)){
                        $insert_arr['values'] .= $value . ',';
                    } elseif ($value == 'NULL' || $value === NULL){
                        // нужно указывать в двойных ковычках, если передать в оинарных, то sql-сервер распознает
                        // ее как строку
                        $insert_arr['values'] .= "NULL";
                    } else {
                        $insert_arr['values'] .= "'" . $value . "',";
                    }

                    $j++;

                    if ($j === $count_fields) break;

                }

                // если в первом поле таблицы мы захотим заполнить больше столбцов, чем в следующих, то
                // столбцы следующих полей в таблице заполнятся пустой строкой
                if ($j < $count_fields){
                    $insert_arr['values'] .= "NULL" . ',';
                }

                $insert_arr['values'] = rtrim($insert_arr['values'], ',') . '),';

                if (!$check_fields) $check_fields = true;

            }
        } else {

            $insert_arr['values'] = '(';

            if ($fields){
                foreach ($fields as $row => $value){

                    if ($excerpt && in_array($row, $excerpt)) continue;

                    $insert_arr['fields'] .= $row . ',';

                    // если передали фунцию 'NOW()'
                    if (in_array($value, $this->sqlFunc)){
                        $insert_arr['values'] .= $value . ',';
                    } elseif ($value == 'NULL' || $value === NULL){
                        // нужно указывать в двойных ковычках, если передать в оинарных, то sql-сервер распознает
                        // ее как строку
                        $insert_arr['values'] .= "NULL";
                    } else {
                        $insert_arr['values'] .= "'" . $value . "',";
                    }

                }
            }

            if ($files){
                foreach ($files as $row => $file){

                    $insert_arr['fields'] .= $row . ',';

                    if (is_array($file)) $insert_arr['values'] .= "'" . addslashes(json_encode($file)) . "',";
                        else $insert_arr['values'] .= "'" . addslashes($file) . "',";
                }
            }

            $insert_arr['values'] = rtrim($insert_arr['values'], ',') . ')';

        }

        $insert_arr['fields'] = rtrim($insert_arr['fields'], ',') . ')';
        $insert_arr['values'] = rtrim($insert_arr['values'], ',');

        return $insert_arr;
    }

    /**
     * @param $fields /fields => ['table_row'=>'value']
     * @param $files /files => ['table_row'=>'value'] или files => ['table_row'=>['value', 'value1']]
     * @param $excerpt /excerpt => ['поле таблицы', 'поле таблицы'] - поля , которые нужно исключить
     * @return string - например 'name = 'name', file = 'filename.txt''
     */
    protected function createUpdate($fields, $files, $excerpt){

        $update = '';

        if ($fields) {
            foreach ($fields as $row => $value){

                // если поле есть в массиве исключений, то пропуститть его
                if ($excerpt && in_array($row, $excerpt)) continue;

                $update .= $row . '=';

                if (in_array($value, $this->sqlFunc)){
                    $update .= $value . ',';
                } elseif ($value === NULL){
                    // ВНИМАНИЕ если в параметрах пришел NULL, то его нужно обернуть в двойные ковычки
                    // для коректной обработки в БД
                    $update .= "NULL" . ',';
                }
                else {
                    $update .= "'" . addslashes($value) . "',";
                }
            }
        }

        if ($files){
            foreach ($files as $row => $file){

                $update .= $row .'=';

                // если это массив с файлами типа ['1.png', '2.png']
                if (is_array($file)){
                    // values => ["...,'['1.png', '2.png']',"]
                    $update .= "'" . addslashes(json_encode($file)) . "',";
                } else {
                    // values => ["...,'1.png',"]
                    $update .=  "'" . addslashes($file) . "',";
                }

            }
        }

        return rtrim($update, ',');

    }

    /**
     * Преобразует строку вида 'table_name  table_alias' в массив вида ['table' => 'table_name',
     * 'alias' => 'table_alias']
     *
     * @param string $table - имя таблицы
     * @return array
     */
    protected function createTableAlias($table){

        $arr = [];

        if (preg_match('/\s/i', $table)){

            $table = preg_replace('/\s{2+}/i', ' ', $table);

            $table_arr = explode(' ', $table);

            $arr['table'] = trim($table_arr[0]);

            $arr['alias'] = trim($table_arr[1]);

        } else {

            $arr['alias'] = $arr['table'] = $table;

        }

        return $arr;

    }

}