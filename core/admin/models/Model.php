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

}