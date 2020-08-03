<?php
/**
 * Базовый контроллер
 */

namespace core\base\controllers;


use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseController
{
    // подключаем трейт с вспомогательными методами
    use \core\base\controllers\BaseMethods;

    // хранит полностью всю страницу
    protected $page;
    // шапка сайта
    protected $header;
    protected $content;
    protected $footer;


    // хранит ошибки
    protected $errors;
    // имя контролера, которое передается из класса RouteController
    protected $controller;
    // свойство которое собирает данные из БД, проводит вычисления и подключает другие методы контролера
    protected $inputMethod;
    // отвечает вывод данных в views
    protected $outputMethod;
    // параметры запроса(например id, color параметры, по которым будет проводится выборка из БД или подключение тех или
    // иных страниц)
    protected $parameters;

    // шаблоны
    protected $template;
    // стили
    protected $styles;
    // скрипты
    protected $scripts;

    //
    protected $userId;
    // текущие данные (полученые из полей текущей таблицы, либо данные пришедшие методом POST или GET и т.д)
    protected $data;

    /**
     * вызывает у текущего контроллера(который наследуется от данного базового контролера) метод request и передает в него
     * параметры url-запроса (для работы с БД) и методы ввода-вывода данных. Определяет какие методы вызвать у контроллера.
     *
     * @throws RouteException
     */
    public function route(){

        $controller = str_replace('/', '\\', $this->controller);

        try{
            // Предопределенный классс ReflectionMethod из расширения Reflection, которое предоставляет разработчику
            // широкий спектр возможностей: метаданные о классе(его методы, спецификаторы доступов и их изменение,
            // возможность вызватб эти методы у класса, создание исключений)

            // проверяем существование метода 'request' у класса $controller
            $object = new \ReflectionMethod($controller, 'request');

            $args = [
                'parameters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod,
            ];
            // и если есть метод 'request' у класса $controller
            // вызываем метод 'request' у объекты класса переданого в $controller и передаем в него параметры $args
            $object->invoke(new $controller, $args);
        }
        catch (\ReflectionException $e){
            throw new RouteException($e->getMessage());
        }

    }

    /**
     * Метод принимает на вход массив $args.Согласно данным полученным из $args вызывает соответствующие методы output,
     * input у производного класса
     *
     * @param $args - содержит парметры запроса, имя input метода, имя output метода,
     */
    public function request($args){

        $this->parameters = $args['parameters'];
        $inputData = $args['inputMethod'];
        $outputData = $args['outputMethod'];

        // вызываем у производного класса метод $inputData(), который возвращает данные, которые нужно вывести в шаблоне
        $data = $this->$inputData();

        // получаем данные всей страницы у производного класса
        if (method_exists($this,  $outputData))  $this->page = $this->$outputData($data);
            elseif ($data) $this->page = $data;

        // если есть ошибки записываем в логи
        if($this->errors){
            $this->writeLog();
        }

        // выводим данные на страницу браузера
        $this->getPage();
    }

    /**
     * Метод, занимается обработкой шаблонов. Формирует данные из переданого шаблона и текущих переменных
     * Используется в производных классах.
     *
     * @param string $path - директория шаблона. Если она не передана, то путь формируется из namespace и имени класса
     * объекта, метод которого вызвал данный метод. В качестве имени шаблоны берется имя класса и из строки имени класса
     * вырезается подстрока 'Controller'
     * @param array $parameters - переменные, которые будут выводится в шаблоне
     * @return bool
     * @throws RouteException
     * @throws \ReflectionException
     */
    protected function render($path = '', $parameters = []){

        // в текущей символьной таблице помещаем переменные из $parameters
        extract($parameters);

        if (!$path){
            // \ReflectionClass($this) - получаем данные о классе текущего объекта
            $class = new \ReflectionClass($this);
            // Преобразуем Namespace класса в коректный путь
            $space = str_replace('\\', '/', $class->getNamespaceName() . '\\');

            $routes = Settings::get('routes');

            // проверяем с какой частью сайта работаем
            // если это пользовательская часть сайта, то подключать шаблоны из директории user
            // если это админская или плагин, то поключать шаблоны из них
            if ( $space === $routes['user']['path']) $template = TEMPLATES;
                else $template = ADMIN_TEMPLATES;

            // getShortName() - получаем имя класса
            // формируем путь используя имя класса в качестве имени шаблона
            $path = $template . explode('controller', strtolower((new \ReflectionClass($this))
                    ->getShortName()))[0];
        }
        // Ниже стартуем буфер и сохраняем в нем php-файл шаблона и текущие переменные, который мы подключаем,
        // затем мы закрываем буфер и возвращаем находящиеся в нем данные
        ob_start();

        if (!@include_once $path . '.php') throw new RouteException('Отсутствует шаблон - '. $path);

        return ob_get_clean();

    }

    /**
     * выводит данные на страницу браузера
     */
    protected function getPage(){

        if (is_array($this->page)){
            foreach ($this->page as $block) echo $block;
        }else{
            echo $this->page;
        }

        exit;
    }
}