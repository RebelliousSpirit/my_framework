<?php
defined('VG_ACCESS') or die('access denied');

use core\base\exceptions\RouteException;

//* настройки сайта
// директория шаблонов пользовательской части сайта
// по умолчанию шаблоны хранятся в папке default, в случае редизайна сайта можно
// будет просто поменять константу TEMPLATES
const TEMPLATES = 'templates/default/';
//  директория шаблонов адимнской части сайта
const ADMIN_TEMPLATES = 'core/admin/views/';
// директория файлов пользовательской части сайта
const UPLOAD_DIR = 'userfiles/';

// версия куки, нужна для того чтобы менять куки пользователей сайта Например для того чтобы заставить перелогинится пользователей сайта
// можно будет просто поменять версию куки
const COOKIE_VERSION = '1.0.0';
// ключ шифрования куки(используется в класс core/base/libraries/Crypt)
const CRYPT_KEY = 'n2r5u8x/A?D(G+KbTjWnZr4u7x!A%D*GcQeThWmZq4t7w!z%H+MbQeShVmYq3t6w?D(G+KbPdSgVkYp3x!A%D*G-KaPdRgUk4t7w!z%C*F-JaNdRmYq3t6w9z$C&F)J@';
// устанавливаем время жизни куки, для того чтобы безопасности администрации сайта
const COOKIE_TIME = 60;
// время блокировки пользователя ошишегося при вводе пароля сайта(указана в часах)
const BLOCK_TIME = 3;

// сколько товаров, постов будет показыветь страница
const QTY = 8;
// вид показа товаров на странице
// Например выводить в 3 строки
const QTY_LINES  = 3;

// пути к css и js файлам админки
const ADMIN_CSS_JS = [
    'styles'=>['css/main.css'],
    'scripts'=>['js/framework-functions.js', 'js/scripts.js'],
];

// пути к css и js файлам пользовательской части сайта
const USER_CSS_JS = [
    'styles'=>[],
    'js'=>[],
];

/**
 * функция автозагрузки классов
 * в случае не правильного указанного имени класса, будет генерироватся ошибка
 *
 * @param $class_name - имя класса, который будет загружатся автоматически
 * @throws RouteException
 */
function autoLoadMainClasses($class_name){

    $class_name = str_replace('\\', '/', $class_name);

    if(!@include_once $class_name . '.php'){
        throw new RouteException('Не правильно указано имя класса ' . $class_name);
    }

}

spl_autoload_register('autoLoadMainClasses');
