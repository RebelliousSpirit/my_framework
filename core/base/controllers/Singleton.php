<?php


namespace core\base\controllers;

/**
 * Реализует паттерн Singleton
 * Паттерн Singleton
 * У моделей вызывает метод connectDB()
 *
 * Trait Singleton
 * @package core\base\controllers
 */
trait Singleton
{

    static private $_instance;

    private function __clone()
    {
    }

    private function __construct()
    {
    }

    /**
     * При вызове этой функции будет создаваться ОДИН объект
     * Также если у объекта класса, который использует этот трейт, есть метод connectDB, то этот метод автоматически
     * вызовется.
     * @return RouteController
     */
    static public function instance(){

        if (self::$_instance instanceof self){
            return self::$_instance;
        }

        self::$_instance = new self;

        if (method_exists(self::$_instance, 'connectDB')) {
            self::$_instance->connectDB();
        }

        return self::$_instance;

    }

}