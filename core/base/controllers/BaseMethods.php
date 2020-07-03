<?php


namespace core\base\controllers;


trait BaseMethods
{
    protected $styles;
    protected $scripts;

    /**
     * Метод проводит инициализацию скриптов и стилей
     * Записывает полный путь до скрипта или стиля в свойсвта  $styles, $scripts
     *
     * @param bool $admin
     */
    protected function init($admin = false){
        // если это пользовтаельская сайта
        if (!$admin){
            // и если есть константа USER_CSS_JS содержащая пути к скриптам и стилям
            // проверка на случай отсуствия этой константы, чтобы интепретатор не выводил ошибку на экран
            if (USER_CSS_JS['styles']) {
                foreach (USER_CSS_JS['styles'] as $item) $this->styles[] = PATH . TEMPLATES . trim($item, '/');
            }
            if (USER_CSS_JS['scripts']) {
                foreach (USER_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . TEMPLATES . trim($item, '/');
            }
        } else {
            if (ADMIN_CSS_JS['styles']) {
                foreach (ADMIN_CSS_JS['styles'] as $item) $this->styles[] = PATH . ADMIN_TEMPLATES
                    . trim($item, '/');
            }
            if (ADMIN_CSS_JS['scripts']) {
                foreach (ADMIN_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . ADMIN_TEMPLATES
                    . trim($item, '/');
            }
        }

    }

    /**
     * Метод очищает строку или значения ключей массива от тегов и пробелов
     *
     * @param $str
     * @return array|string
     */
    protected function clearStr($str){

        if (is_array($str)){
            foreach ($str as $key => $item) $str[$key] = trim(strip_tags($item));
            return $str;
        } else {
            return $str = trim(strip_tags($str));
        }

    }

    /**
     * Метод преобразует числа пришедшие в строковом типе в числовой тип
     *
     * @param $num
     * @return float|int
     */
    protected function clearNum($num){
        return $num * 1;
    }

    /**
     * Вернет true, если данные пришли методом POST
     *
     * @return bool
     */
    protected function isPost(){
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * Вернет true, если данные пришли методом Ajax
     *
     * @return bool - вернет true если запрос передан асинхронно
     */
    protected function  isAjax(){
        // проверяем ячейку 'HTTP_X_REQUESTED_WITH' суперглобального массива $_SERVER т.к она появляется только тогда,
        // когда используется метод Ajax для передачи данных
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    /**
     * Производит редирект
     * Если не указан параметр $code, то в заголовок ответа подставится  код 301
     *
     * Принимает 2 не обязатаельных параметра.Если параметр $http не указан, то произведет редирект на страницу, с
     * которой зашел пользователь, если он находится на главной странице, то редирект на главную
     *
     * @param bool $http - адрес на который должен произойти редирект
     * @param bool $code - код сервера
     */
    protected function redirect($http = false, $code = false){

        if ($code){
            $codes = ['301' => 'HTTP/1.1 301 Move Permanently'];

            if ($codes[$code]) header($codes[$code]);
        }

        if($http){
            $redirect = $http;
        } else {
            $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;
        }

        header("Location: $redirect");

        exit;
    }

    /**
     * выводит текущие файлы стилей в view
     * вызывается в \admin\views\include\header.php
     */
    protected function getStyles(){

        if ($this->styles){
            foreach ($this->styles as $style){
                echo '<link rel="stylesheet" href="' . $style . '">';
            }
        }

    }

    /**
     * выводит текущие файлы  js-скриптов в view
     * вызывается в \admin\views\include\footer.php
     */
    protected function getScripts(){

        if ($this->scripts){
            foreach ($this->scripts as $script){
                echo '<script src="' . $script . '"></script>';
            }
        }

    }

    /**
     * Производит логирование ошибок и исключений
     *
     * @param $message - тело ошибки
     * @param string $file - имя файла куда будет производится запись
     * @param string $event - событие - ошибка или исключеие
     * @throws \Exception
     */
    protected function writeLog($message, $file = 'log.txt', $event = 'Fault'){

        $dateTime = new \DateTime();

        $str = $event . ': ' . $dateTime->format('d-m-Y G:i:s') . ' - ' . $message . "\r\n";

        file_put_contents('log/' . $file, $str, FILE_APPEND);
    }

}