<?php

namespace core\base\settings;

use core\base\controllers\Singleton;

/**
 * Класс с основными настройками сайта - содержит пути, методы склеивания массивов с путями из других плагинов
 *
 * Class Settings
 * @package core\base\settings
 *
 */
class Settings
{
    //подключаем трейт с паттерном Singleton
    use Singleton;

    // настройка путей
    private $routes = [
        'admin' => [
            // псевдоним админской части сайта
            // можно будет поменять на другой для безопасности
            'alias' => 'admin',
            // путь до контроллеров
            'path' => 'core/admin/controllers/',
            // ЧПУ
            'hrUrl' => false,
        ],
        'settings'=>[
            'path' => 'core/settings/',
        ],
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            // дополнительная настройка произвольных путей к плагину
            'dir' => false
        ],
        'user' => [
            'path' => 'core/user/controllers/',
            'hrUrl' => true,
            // имена контроллеров
            'routes' => [
                'catalog' => 'site',
            ]
        ],

        // это пути страницы по умолчанию
        'default' => [
            'controller' => 'IndexController',
            // методы вызываемые у контроллера
            'inputMethod' => 'inputData',
            'outputMethod' => 'outputData',
        ]

    ];
    // директория с расширениями админк. части сайта
    private $expansion = 'core/admin/expansion/';
    // имя таблицы БД по умолчанию, данные из которой будут использоватся в админ. части сайта
    // данные этой таблицы по умолчанию будут показыватся первыми в админке
    private $defaultTable = 'goods';

    // место хранение ошибок
    private $messagesPath = 'core/base/messages/';

    // данные для вывода меню в view админк.части сайта
    // 'teachers' - название таблицы
    private $projectTables = [
        'goods' => ['name' => 'продукты', 'img' => 'pages.png'],
        'filters' => ['name' => 'фильтры', 'img' => 'pages.png'],
    ];

    // директория хранения шаблонов форм
    private $formTemplatesPath = PATH . 'core/admin/views/include/form_templates/';

    // данные для формирования шаблонов.
    // Имя ключа. - это название шаблона(директория core/admin/views/include)
    // Например: 'text' - это core/admin/views/include/text/php
    // Массив элементов которые лежат в его ячейке - это поля таблицы БД, при выводе данных которых будут использоваться,
    // шаблон указанный в ключе
    // ИСКЛЮЧЕНИЕ 'checkbox_list' => ['filters'], 'filters' - это таблица, 'checkbox_list' - шаблон для вывода данных для
    // связи многие ко многим. При этом можно указать несколько имен таблиц, при условии что они связаны между собой
    private $templateArr = [
        'text'=>['name', 'phone', 'address'],
        'textarea'=>['content'],
        'radio'=>['visible'],
        'checkbox_list' => ['filters'],
        'select'=>['menu_position', 'parent_id'],
        'img'=>['img'],
        'gallery'=>['gallery_img'],
    ];

    // данные для контентной части view админк.части сайта
    // 'name'(ключ) - соотвествует имени поля в таблице Бд
    //  ['имя', 'не более 100 символов'] - 1 ячейка - перевод, 2 - доп. инфор-я
    private $translate = [
        'name' => ['имя', 'не более 100 символов'],
        'menu_position' => ['позиция в меню', 'выберите позицию в меню'],
        'parent_id' => ['родительская категория', 'выберите родительскую категорию'],
        'gallery_img' => ['галерея', 'добавьте изображение(ния) в Вашу галерею'],
        'img' => ['изображение', 'добавьте изображение'],
        'visible' => ['отображение', 'отображать данную запись или нет'],
    ];

    // настройки переключателей для контентной части view админк.части сайта
    private $radio = [
      'visible' => ['нет', 'да', 'default' => 'да'],
    ];

    // данные для выведения информации о наличии корневой категории в контентной части(раздел добавления)
    // view админк.части сайта
    private $rootItems = [
        'name' => 'корневая',
        // таблицы имеющие корневую категорию
        'tables' => ['products', 'teachers'],
    ];

    // данные для формирования блоков в контентной части () страницы админ.части сайта
    // ключ - это разделы страницы, куда будут выводится блоки(img, content, )
    // значение - это блоки страницы(они соотвествуют именам полей таблицы БД)
    private $blockNeedle = [
      'vg-rows' => [],
      'vg-img' => ['img'],
      'vg-content' =>['content'],
    ];

    // данные для формирования блока контентной части страницы админ.части сайта
    // осуществляющий 'связь многие ко многим'
    // 'goods_filters' - название таблицы хранящая данные связаных таблиц
    // ['goods', 'filters'] - имена связаных таблиц
    // 'type' => 'child' || 'root' - отображать ли из например таблицы 'goods' поля, которые сслаются на корневые поля
    // или показывать сами корневые поля.(случай когда поля таблицы по внещнему ключу сслаются на первичный ключ полей
    // своей же таблицы)
    private $manyToMany = [
        'goods_filters' => ['goods', 'filters']// 'type' => 'child' || 'root'
    ];

    // настройки валидации данных пришедших метод POST(из форм)
    protected $validation = [
        // 'empty' => true - проверяем пустая ли переменная
        'name' => ['empty' => true, 'trim' => true],
        'price' => ['int' => true],
        'login' => ['empty' => true, 'trim' => true],
        // пароли нужно шифровать
        'password' => ['crypt' => true],
        // строку пришедшую из ячейки 'keywords' нужно ограничивать по длине для SEO
        'keywords' => ['count' => 70, 'trim' => true],
        // строку пришедшую из ячейки 'description' нужно ограничивать по длине для SEO
        'description' => ['count' => 160, 'trim' => true],
    ];

    /**
     * Функция возвращщает приватные свойства данного класса
     *
     * @param $property
     * @return mixed
     */
    static function get($property){
        return self::instance()->$property;
    }

    /**
     * Здесь происходит склеивание свойств полученных из плагинов с свойствами класса Settings
     *
     * @param $class
     */
    public function clueProperties($class){

        $baseProperties = [];

        foreach ($this as $name => $item) {
            $property = $class::get($name);

            if(is_array($property) && is_array($item)){
                $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
                continue;
            }

            if (!$property) $baseProperties[$name] = $this->$name;
        }

        return $baseProperties;

    }

    /**
     * Функция склеивания массивов. Особенностью является то что при склеивании массивов исключается повторение
     * их значений.
     *
     * @return mixed
     */
    public function arrayMergeRecursive(){

        $arrays = func_get_args();

        // вырезаем первый элемент массива $arrays и теперь в $arrays нет первого элемента
        $base = array_shift($arrays);

        foreach ($arrays as $array){
            foreach ($array as $key => $value){
                if (is_array($value) && is_array($base[$key])){
                    $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
                }else{
                    if (is_int($key)){
                        // если нет значения $value в масссиве $base, то закинем это значение в массив $base
                        if (!in_array($value, $base)) array_push($base, $value);
                        continue;
                    }
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }

}
