<?php


namespace core\base\controllers;


class BaseRouteController
{

    use Singleton, BaseMethods;

    /**
     * Отвечает за определение путей.
     *
     * @throws \core\base\exceptions\RouteException
     */
    public static function routeDirection(){

        if (self::instance()->isAjax()){
            exit((new BaseAjaxController())->route());
        }

        RouteController::instance()->route();

    }

}