<?php
// эта константа определяет можно ли пользователю просмотреть подключаемые файлы или нет
define('VG_ACCESS', true);

//говорим браузеру какую кодировку использовать
header('Content-Type: text/html; charset=utf-8');
//стартуем сессию зашедшего пользователя
session_start();

// отключает вывод на страницу предупреждений о ошибках
// включать только, когда проект пойдет в продакшн
//error_reporting(0);

// базовые настройки сайта
require_once 'config.php';
// фундаментальные настройки сайта
require_once 'core/base/settings/internal_settings.php';
// подключаем вспомогательные функции
require_once 'libraries/functions.php';

use core\base\controllers\BaseRouteController;
use core\base\exceptions\RouteException;

try{
    BaseRouteController::routeDirection();
}catch (RouteException $e){
    exit($e->getMessage());
}

