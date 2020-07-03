<?php


namespace core\base\settings;


use core\base\controllers\Singleton;
use core\base\settings\Settings;

trait BaseSettings
{
    // ВНИМАНИЕ мы присвоили псевдоним методу instance трейта Singleton
    // для облегчения работы с классами расширений т.к метод instance есть и у трейта Singleton
    // и создание одноименного метода в данном классе-расширении вызовет ошибку
    use Singleton{
        // присвоили псевдоним методу instance трейта Singleton
        instance as SingletonInstance;
    }

    // объект класса, содержащий базовые настройки
    private $baseSettings;

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
        self::SingletonInstance()->baseSettings = Settings::instance();

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