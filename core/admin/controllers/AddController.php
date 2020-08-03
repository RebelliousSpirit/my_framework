<?php


namespace core\admin\controllers;


use core\base\settings\Settings;

/**
 * Отвечает за добавление данных в таблицы БД админ. части сайта
 * Class addController
 * @package core\admin\controllers
 */
class addController extends BaseAdmin
{

    // это свойство указывается в форме страницы, указывает в какой контроллер
    // отправлять ее данные
    protected $action = 'add';

    /**
     * Загрузка данных
     * @throws \core\base\exceptions\RouteException
     */
    protected function inputData()
    {
        // если это не пользователь сайта
        if (!$this->userId) $this->execBase();

       // проводим проверку post-данных, затем их добавляем(редактирование.добавление) в таблицу БД
       // записываем в текущую сессию для удобства пользователя
       $this->checkPostData();

       // получаем имя таблицы и ее поля
       $this->createTableData();

       $this->createForeignData();

       $this->createMenuPosition();

       $this->createRadio();

       $this->createOutputData();

       $this->createManyToMany();

       // подключаем расширения
       $this->addExpansion();

    }

}