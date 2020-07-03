<?php


namespace core\base\controllers;


use core\base\settings\Settings;

class BaseAjaxController extends BaseController
{
    /**
     *
     *
     * @return mixed|void
     */
    public function route(){

        $route = Settings::get('routes');

        $controller = $route['user']['path'] . 'AjaxController';

        $data = $this->isPost() ? $_POST : $_GET;

        if (isset($data['ADMIN_MODE'])){

            // уничтожжаем лишний ячейку массиву, содержащую флаг,
            // для экономии памяти
            unset($data['ADMIN_MODE']);

            $controller = $route['admin']['path'] . 'AjaxController';

        }

        $controller = str_replace('/', '\\', $controller);

        $ajax = new $controller;

        $ajax->createAjaxData($data);

        return ($ajax->ajax());

    }

    /**
     * @param $data - текущие данные полученные из асинхронного запроса
     */
    protected function createAjaxData($data){

        $this->data = $data;

    }

}