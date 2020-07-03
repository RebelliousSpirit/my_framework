<?php
//Пример 1

/**
 * Настройки расширения 'Shop'
 * Это файл демонстрирует ошибку в разработке расширения. Здесь рнапример разработчику придется реализовывать слишком
 * много методов: setProperties, instance, get
 */

namespace core\base\settings;

use core\base\controllers\Singleton;
use core\base\settings\Settings;

/**
 * Этот класс реализует паттерн Singleton
 *
 * Это учебный класс для демонстрации способа получения массивов(либо других данных) из
 * другого класса и слияние их со своими массивами
 *
 * Class ShopSettings
 * @package core\settings
 */
class test
{
    // ВНИМАНИЕ мы присвоили псевдоним методу instance трейта Singleton
    // для облегчения работы с классами расширений т.к метод instance есть и у трейта Singleton
    // и создание одноименного метода в данном классе-расширении вызовет ошибку
    use Singleton{
        // присвоили псевдоним методу instance трейта Singleton
        instance as traitInstance;
    }

    // объект класса, содержащий базовые настройки
    private $baseSettings;

    private $routes = [
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            // дополнительная настройка произвольных путей к плагину
            'dir' => 'controller',
            //
            'routes' => [
                'routes' => [
                    'product' => 'goods',
                ]
            ],
        ],
    ];

    private $templateArr = [
        'text'=>['price', 'abort', 'name'],
        'textarea'=>['goods_content'],
    ];


    /**
     * Функция возвращщает приватные свойства данного класса
     *
     * @param $property
     * @return mixed
     */
    static function get($property){
        return self::getInstance()->$property;
    }

    /**
     * Склеивает своства текущего класса настроек с свойствами главного класса настроек
     * И возвращает объект с этими объединеными свойствами
     *
     * @return test
     */
    static public function instance(){

        if (self::$_instance instanceof self){
            return self::$_instance;
        }

        // присваеваем baseSettings экземляр класса Settings
        self::traitInstance()->baseSettings = Settings::instance();

        // вызываем у baseSettings метод, который соединяет массивы этого класса с массивами класса Settings
        // в метод clueProperties() передается текущий класс, массивы которого нужно склеить
        $baseProperties = self::$_instance->baseSettings->clueProperties(get_class());

        self::$_instance->setProperties($baseProperties);

        return self::$_instance;

    }

    /**
     * @param $properties
     */
    protected function setProperties($properties){
        if($properties){
            foreach ($properties as $name => $property){
                $this->$name = $property;
            }
        }
    }
}

// Пример 2 Поиск подстроки в строке

$link = '<a class="color" style="color: red" href="http://sdcmcmm" data-id="id"></a>';
$pattern = '/<a\s*?[^>]*?href\s*?=(["\'])(.+?)\1[^>]*?>/ui';// ищем атрибут href с его содержимым

preg_match_all($pattern, $link, $res);
