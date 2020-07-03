<?php

namespace core\admin\controllers;

use core\admin\models\Model;
use core\base\controllers\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use libraries\FileEdit;

/**
 * Базовый контроллер для производных контрллеров админ. части сайта.
 * Формирует статические части страницы админки сайта(шапки и подвала), а также контент
 * ной части по умолчанию, если в производных контроллерах заранее не сформрована контентная часть страницы. *
 * Позволяет подключать расширения через метод addExpansion.Отключает кэширование браузера(при обновлении скриптов и
 * стилей не будет необходимости обновлять кэш браузера).
 *
 * @package core\admin\controllers
 */
abstract class BaseAdmin extends BaseController
{
    // модель
    protected $model;

    // таблица БД
    protected $table;
    // ее поля
    protected $columns;
    // внешнии данные
    protected $foreignData;

    // массив с файлами для добавления в БД
    protected $filesArray;
    // псевдоним записи таблицы БД для формирования ЧПУ
    protected $alias;


    // опция удаления записи в таблице БД
    protected $noDelete;

    // директория админ. части сайта
    protected $adminPath;

    // меню в админке
    protected $menu;
    // название сайта(которое отображается в заголовке закладки страницы в браузере)
    protected $title;


    // массив с сообщениями о ошибках или предупреждениях
    protected $messages;

    // массив с переводом для контентной части страницы админ.части сайта
    protected $translate;

    // данные для формирования блоков в контентной части страницы админ.части сайта
    protected $blocks = [];

    // путь до шаблонов формы
    protected $formTemplatesPath;
    // настройки для формирования шаблонов формы
    protected $templateArr;

    /**
     * Ввод данных.
     */
    protected function inputData(){

        // подключаем css и js
        $this->init(true);
        // определяем текущее название сайта
        $this->title = 'my framework';

        // свойства можно изменить в расширениях, поэтому проводится проверка на наличии свойств
        if (!$this->model) $this->model = Model::instance();
        if (!$this->menu) $this->menu = Settings::get('projectTables');
        if (!$this->adminPath) $this->adminPath = PATH . Settings::get('routes')['admin']['alias'] . '/';
        if (!$this->formTemplatesPath) $this->formTemplatesPath = Settings::get('formTemplatesPath');
        if (!$this->templateArr) $this->templateArr = Settings::get('templateArr');
        if (!$this->messages) $this->messages = include $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messagesPath')
            . 'informationMessages.php';

        $this->sendNoCacheHeaders();

    }

    /**
     * Вывод данных в шаблон.
     *
     * @return bool
     * @throws RouteException
     * @throws \ReflectionException
     */
    protected function outputData(){

        // если в других контролерах не загружен контент
        if (!$this->content){

            $args = func_get_arg(0);
            $vars = $args ? $args : [];

            // строка закоментирована т.к явно указывать путь до View контента не обязательно
            // путь автоматически формируется из названия класса контроллера
            //if (!$this->template) $this->template = ADMIN_TEMPLATES . 'show';

            $this->content = $this->render($this->template, $vars);

        }

        $this->header = $this->render(ADMIN_TEMPLATES . 'include/header');
        $this->footer = $this->render(ADMIN_TEMPLATES . 'include/footer');

        return $this->render(ADMIN_TEMPLATES. 'layout/default');
    }

    /**
     * Отключает кэширование браузера, для того чтобы постоянно подгружались новые файлы
     */
    protected function sendNoCacheHeaders(){

        header('Last-Modified: ' . gmdate('D, d m Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Cache-Control: max-age=0');
        // Для IE
        header('Cache-Control: post-check=0, pre-check=0');
    }

    /**
     * вызывает собственный метод inputData.Т.к в классах плагинов, которые будут наследоватся от потомков класса
     * BaseAdmin будет реализовыватся другой принцип вызова методов
     */
    protected function execBase(){
        self::inputData();
    }

    /**
     * Записывает в $this->table - имя запрашиваемой таблицы БД
     * в $this->columns - поля запрашиваемой таблицы БД
     * @param object $settings - настройки плагина
     *
     * Имя запрашиваемой таблицы берется из url-запроса:
     * Например: http://my_sait.loc/admin/show/teachers/5
     * в нем 'teachers' - имя таблицы
     * '5' - id поля таблицы
     */
    protected function createTableData($settings = false){

        if (!$this->table){
            // Например: table = 'teachers'
            if ($this->parameters) $this->table = array_keys($this->parameters)[0];
                else{
                    if (!$settings) $settings = Settings::instance();
                    $this->table = $settings->get('defaultTable');
                }
        }

        $this->columns = $this->model->showColumns($this->table);

        if (!$this->columns) new RouteException('Не найдены поля в таблице' . $this->table, 2);

    }

    /**
     * Подключает класс(расширения) из директории admin/expansion/ и передает в его область видимости переменных или
     * записывает в его своства ссылки на своства базовго класса(BaseAdmin).ВНИМАНИЕ ради экономии памяти в случае когда
     * нужно изменить всего несколько текущих своств(своства объекта класса baseAdmin и его рдителя BaseController), то
     * в файлах расширения не обязательно использовать ООП, нужно использовать процедурный подход программирования.
     *
     * Имя подключаемого класса формируется из имени таблицы с которой в данный момент идет работа
     *
     * @param array $args - текущие свойства класса BaseAdmin
     */
    protected function addExpansion($args = [], $settings = false){

        $filename = explode('/', $this->table);
        $className = '';

        foreach ($filename as $item) $className .= ucfirst($item);

        if (!$settings){
            $path = Settings::get('expansion');
        } elseif (is_object($settings)) {
            $path = $settings::get('expansion');
        } else {
            $path = $settings;
        }

        $class = $path . $className . 'Expansion';


        if (is_readable($_SERVER['DOCUMENT_ROOT'] . PATH . $class . '.php')){

            $class = str_replace('/', '\\', $class);

            $exp = $class::instance();

            // Перебираем в цикле свойства объекта класса BaseAdmin и его родителя BaseController
            // Записываем их ссылки в объект класса расширения(ссылки на своства объекта позволяют их перезаписывать в
            // другом объекте)
            foreach ($this as $name => $value){
                $exp->$name = &$this->$name;
            }

            return $exp->expansion($args);

        } else {

            $file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';

            extract($args);

            if(is_readable($file)) return include $file;

        }

        return false;

    }

    /**
     * Формирует данные вывода в блоках контентной части страницы админки.
     *
     * @param bool $settings - настройки текущего сайта или его расширения
     */
    protected function createOutputData($settings = false){

        if (!$settings) $settings = Settings::instance();

        $blocks = $settings::get('blockNeedle');
        $this->translate = $settings::get('translate');

        // если в настройках нет данных для формирования блоков
        if (!$blocks || !is_array($blocks)){

            // далее заполняем данные переводов и блоков согласно именам полей таблицы БД
            // а ключами будут числа
            foreach ($this->columns as $name => $item) {
                if ($name === 'id_row') continue;

                if (!$this->translate[$name]) $this->translate[$name][] = $name;
                $this->blocks[0][] = $name;
            }

            return;
        }

        $default = array_keys($blocks)[0];
        // заполяем данные для формирования блоков
        foreach ($this->columns as $name => $item) {
            if ($name === 'id_row') continue;

            $insert = false;

            foreach ($blocks as $block => $value) {

                if (!array_key_exists($block, $this->blocks)) $this->blocks[$block] = [];

                if (in_array($name, $value)){
                    $this->blocks[$block][] = $name;
                    $insert = true;
                    break;
                }

            }

            if (!$insert) $this->blocks[$default][] = $name;
        }

        return;

    }

    /**
     * Добавляет настройки для радиокнопок
     * Если у текущей таблицы есть поле с именем 'visible', то добавятся доп.настройки радиокнопок
     *
     * @param bool $settings - настройки сайта или плагина
     */
    protected function createRadio($settings = false){

        if (!$settings) $settings = Settings::instance();

        $radio = $settings::get('radio');

        if ($radio){

            foreach ($this->columns as $name => $item){
                if ($radio[$name]){
                    $this->foreignData[$name] = $radio[$name];
                }
            }
        }
    }

    /**
     * Проверяет данные пришедшие методом POST
     *
     * @param bool $settings
     */
    protected function checkPostData($settings = false){

        if ($this->isPost()){

            $this->clearPostFields($settings);
            $this->table = $this->clearStr($_POST['table']);
            // убираем переменную table из-за ненадомности
            unset($_POST['table']);

            if ($this->table){
                $this->createTableData($settings);
                $this->editData();
            }

        }
    }

    /**
     * Добавляет в текущую сессию данные(по умолчанию post-данные)
     * и редиректит обратно на исходную страницу
     *
     * @param array $arr - данные для последующего заполнения формы
     */
    protected function addSessionData($arr = []){

        if (!$arr) $arr = $_POST;

        foreach ($arr as $key => $value){
            $_SESSION['res'][$key] = $value;
        }

        $this->redirect();

    }

    /**
     * Вспомогательный метод метода clearPostFields
     * Проверка кол-ва символов в строке.
     *
     * @param $str - строка переданная из post-данных или каких-либо других
     * @param $count - кол-во допустимых символов
     * @param $answer - наименование поля ввода в форме
     * @param $arr - данные для последующего заполнения формы(для того чтобы пользователь снова не вводил данные в поля
     *  формы)
     */
    protected function checkCountChar($str, $count, $answer, $arr){
        // ВНИМАНИЕ за подсчет кол-ва символов в кирилице есть специальная функция
        // для подсчета мультибайтовых символов mb_strlen()
        if (mb_strlen($str) > $count){

            $str_res = mb_str_replace('$1', $answer, $this->messages['count']);
            $str_res = mb_str_replace('$2', $count, $str_res);

            $_SESSION['res']['answer'] = '<div class="error">' . $str_res . ' ' . $answer . '</div>';
            $this->addSessionData($arr);

        }
    }

    /**
     * Вспомогательный метод метода clearPostFields
     * @param $str - переменная, которую нужно проверить на пустоту
     * @param $answer - имя поля формы, которые не заполнил пользователь
     * @param array $arr
     */
    protected function emptyField($str, $answer, $arr = []){

        if (empty($str)) {
            $_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty'] . ' ' . $answer . '</div>';
            // добавляем текущие данные в сессию для того чтобы пользователь лишний раз не перезаписывал данные в форме
            $this->addSessionData($arr);
        }

    }

    /**
     * Вспомогательный метод метода checkPostData
     *
     * Проводит валидацию данных. Если не передать массив  с данными, то по умолчанию возьмет данные из суперглобального
     * массива POST. Числа пришедшие в строковом типе приводит к числовому типу, обрезает пробелы, шифрует пароли,
     * проверяет пустые ли переменные. Какие методы проверки пременить к данным можно указать в файле настроек
     * Settings.php.
     *
     * @param $settings - настройки сайта или его расширения
     * @param array $arr - массив с данными, которые пройдут валидацию
     * @return bool
     */
    protected function clearPostFields($settings, &$arr = []){

        // ссылка на какую-либо переменную дает возможность при изменении самой ссылки менять
        // и переменную на которую она ссылается
        if (!$arr) $arr = &$_POST;
        if (!$settings) $settings = Settings::instance();
        $validate = $settings::get('validation');
        if (!$this->translate) $this->translate = $settings::get('translate');
        $id = $_POST[$this->columns['id_row']] ?: false; // имя поля содержащие первичные ключи таблицы

        foreach ($arr as $key => $value){
            // если пришел массив данных(например галерея изображений)
            if (is_array($value)){
                $this->clearPostFields($settings, $value);
            } else {
                // если это число
                if (is_numeric($value)){
                    $arr[$key] = $this->clearNum($value);
                }

                // если есть настройки валидации
                if($validate){

                    // если есть в натройках валидации параметры для проверки текущего поля
                    if ($validate[$key]){

                        if ($this->translate[$key]){
                            $answer = $this->translate[$key][0];
                        } else {
                            $answer = $key;
                        }

                        // если это пароль
                        if ($validate[$key]['crypt']){
                            if ($id){
                                // если пароль пришел пустым, то удалить соотвествующую ячейку и перейти к другой итерации
                                // цикла
                                if (empty($value)){
                                    unset($arr[$key]);
                                    continue;
                                }
                                // иначе хэшируем(шифруем) пароль
                                $arr[$key] = md5($value);
                            }
                        }

                        // если надо проверить содержание переменной на пустую строку или NULL
                        if ($validate[$key]['empty']) $this->emptyField($value, $answer, $arr);

                        // если нужно удалить концевые пробелы
                        if ($validate[$key]['trim']) $arr[$key] = trim($value);

                        // если нужно привести к числовому типу
                        if ($validate[$key]['int']) $arr[$key] = $this->clearNum($value);

                        // если нужно проверить на кол-во символов
                        if ($validate[$key]['count']) $this->checkCountChar($value, $validate[$key]['count'], $answer, $arr);

                    }
                }
            }
        }

        return true;

    }

    /**
     * Вспомогательный метод метода checkPostData
     *
     * Метод редавтирования/добавления данных в текущей таблице БД
     *
     * @param bool $returnId - возвращать или не возвращать id записи. id записи записывается в post-данные
     * @return mixed
     */
    protected function editData($returnId = false){

        $id = false;
        // по умолчанию метод в режиме добавления данных
        $method = 'add';

        // если в post-данных придет id записи, то это означает что ее нужно редактировать
        // и $method станет равным 'edit', т.е применятся метод редактирования записи
        if ($_POST[$this->columns['id_row']]){
            // приводим к числовому типу если id в таблице БД в виде числа,
            // очищаем от тегов и крайних пробелов если id в таблице БД в виде числового-строкового вида(Например: 'a1')
            $id = is_numeric($_POST[$this->columns['id_row']]) ? $this->clearNum($_POST[$this->columns['id_row']])
                : $this->clearStr($_POST[$this->columns['id_row']]);
            if ($id){
                $where = [$this->columns['id_row'] => $id];
                $method = 'edit';
            }
        }

        foreach ($this->columns as $key => $item){
            if ($key === 'id_row' ) continue;

            if ($item['Type'] === 'date' || $item['Type'] === 'datetime'){
                !$_POST[$key] && $_POST[$key] = 'NOW()';
            }
        }

        $this->createFile();

        $this->createAlias($id);

        $this->updateMenuPosition();

        $except = $this->checkExceptFields();

        $res_id = $this->model->$method($this->table, [
            'files' => $this->filesArray,
            'where' => $where,
            'return_id' => true,
            'except' => $except
        ]);

        // формируем тело сообщения о ошибке или успехе добавления/редактирования данных
        // если добаляем данные в БД
        if (!$id && $method == 'add'){
            $_POST[$this->columns['id_row']] = $res_id;
            $answerSuccess =  $this->messages['addSuccess'];
            $answerFail =  $this->messages['addFail'];
        } else {// если редактируем данные в БД
            $answerSuccess =  $this->messages['editSuccess'];
            $answerFail =  $this->messages['editFail'];
        }

        // передать в расширения текущие переменные
        $this->addExpansion(get_defined_vars());

        //
        $result = $this->checkAlias($_POST[$this->columns['id_row']]);

        // выводим тело служебное сообщение на страницу
        if ($res_id){ // если запрос удался
            $_SESSION['res']['answer'] = '<div class="success">' . $answerSuccess . '</div>';

            if (!$returnId) $this->redirect();

            return $_POST[$this->columns['id_row']];
        } else { // если запрос не удался
            $_SESSION['res']['answer'] = '<div class="error">' . $answerFail . '</div>';

            if (!$returnId) $this->redirect();
        }

    }

    /**
     * Создает файл
     */
    protected function createFile(){

        $fileEdit = new FileEdit();
        $this->filesArray = $fileEdit->addFile();

    }

    /**
     * Создает alias текущей записи
     *
     * @param bool $id - первичный ключ записи
     */
    protected function createAlias($id = false){

        // записываем в псевдоним значение ячейки массива $_POST с ключом 'name'
        // если же нет ячейки с ключом 'name', то ищем похожие и проделываем ту же
        // операцию
        if ($this->columns['alias']){

            if (!$_POST['alias']){

                if ($_POST['name']){
                    $alias_str = $this->clearStr($_POST['name']);
                } else {
                    foreach ($_POST as $key => $item){
                        if (strrpos($key, 'name') !== false && $item){
                            $alias_str = $this->clearStr($item);
                            break;
                        }
                    }
                }

            } else {
                // заодно в массиве $_POST записываем текущий alias
                $alias_str = $_POST['alias'] = $this->clearStr($_POST['alias']);
            }

            $textModify = new \libraries\TextModify();
            $alias = $textModify->translit($alias_str);

            //* далее проверяем есть ли такой же alias в таблице БД
            //
            $where['alias'] = $alias;
            $operand[] = '=';

            // нужно исключить поиск в текущей записи
            if ($id){
                $where[$this->columns['id_row']] = $id;
                $operand[] = '<>';
            }

            $res_alias = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => $where,
                'operand' => $operand,
                'limit' => '1'
            ])[0];

            if (!$res_alias){
                $_POST['alias'] = $alias;
            } else {
                $this->alias = $alias;
                $_POST['alias'] = '';
            }

            // если это операция редактирования записи
            if ($_POST['alias'] && $id){
                // если в текущем объекте есть метод checkOldAlias, то вызвать его
                method_exists($this, 'checkOldAlias') && $this->checkOldAlias($id);
            }

        }
    }

    protected function updateMenuPosition(){

    }

    /**
     * Вспомогтаельный метод метода editData
     * Проверяет ключи в $arr. Если в таблице БД отсуствует поле(я) соотвествующие ключю(ам) из массива $arr,
     * то этот(и) ключ(и) добавляются в исключение $except.
     *
     * @param array $arr - массив с данными, которые нужно добавить в таблицу БД
     * @return array - массив с именами полей, которые нужно исключить
     */
    protected function checkExceptFields($arr = []){
        if (!$arr) $arr = $_POST;

        $except = [];

        if ($arr){
            foreach ($arr as $key => $item){
                if (!$this->columns[$key]) $except[] = $key;
            }
        }

        return $except;
    }

    /**
     * Вспомогательный метод метода editData
     *
     * Формирует оригинальный alias и записывает его в текущую запись в таблице БД
     *
     * @param $id - id записи в таблице БД
     * @return bool - если
     */
    protected function checkAlias($id){

        if ($id){
            if ($this->alias){

                $this->alias .= '-' .$id;

                $this->model->edit($this->table,[
                    'fields' => ['alias' => $this->alias],
                    'where' => [$this->columns['id_row'] => $id]
                ]);

                return true;

            }
        }

        return false;

    }
}