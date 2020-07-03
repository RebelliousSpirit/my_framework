<?php


namespace core\base\controllers;

use core\base\exceptions\RouteException;
use core\base\settings\Settings;

/**
 * отвечает за формирование маршрутов(т.е за разбор адресной строки), он должен распознавать URL запросов, в нем
 * содержится логика обработки запросов
 *
 * Здесь происходит реализация паттерна Singleton - класс от которого создается только один объект
 * Мы запрещаем множественное создание и клонирование объектов данного класса
 * Будет создаватся один объект данного
 * Нужнно для сохранения ресурсов системы, БД, исключения утечки памяти
 *
 * Class RouteController
 * @package core\base\controllers
 */
class RouteController extends BaseController
{
    // подключаем трейт с патерном Singleton
    use Singleton;

    // свойство маршруты
    protected $routes;

    /**
     * Когда происходит запрос на страницу в конструктуре класса разбирается строка запроса по типу:
     * /контроллер/входной метод/выходной метод/параметр/значение параметра/параметр/значение параметра/
     *
     *
     * RouteController constructor.
     */
    private function __construct()
    {
        $address_str = $_SERVER['REQUEST_URI'];

        if ($_SERVER['QUERY_STRING']){
            $address_str = substr($address_str, 0, strrpos($address_str, $_SERVER['QUERY_STRING']) - 1);
        }
        //Проверка на наличие слеша в конце строки запроса т.к и проверяем не является ли эта запрос на главную страницу
        // Например запрос 'http://stp.loc/' и 'http://stp.loc' это не одно и тоже
        // Таким образом мы боремся с лишним слешем в конце
        // Если в конце запроса есть "/" и это не корень сайта
        if (strrpos($address_str, '/') === strlen($address_str) - 1 && strrpos($address_str, '/') !== 0)
        {
            //rtrim() - обрезает пробел в конце, и символ указаный в 2 параметр
            $this->redirect(rtrim($address_str, '/'), 301);
        }
        // возвращает директорию основного скрипта
        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));

        // даллее будет идти проверка директории основного скрипта на соответсвие с насттройкой указанной в константе
        // PATH, проверка проводится на случай, если вдруг не правильно будет настроен файл
        if($path === PATH){

            //Проверка на наличие слеша в конце строки запроса т.к и проверяем не является ли эта запрос на главную страницу
            // Например запрос 'http://stp.loc/' и 'http://stp.loc' это не одно и тоже
            // Таким образом мы боремся с лишним слешем в конце
            // Если в конце запроса есть "/" и это не корень сайта
            if (strrpos($address_str, '/') === strlen($address_str) - 1 &&
                strrpos($address_str, '/') !== strlen(PATH) - 1){
                //rtrim() - обрезает пробел в конце, и символ указаный в 2 параметр
                $this->redirect(rtrim($address_str, '/'), 301);
            }

            // получаем настройки путей из файла основных настроек
            $this->routes = Settings::get('routes');
            // проверка на наличие настроек путей
            if (!$this->routes){
                throw new Exception('Отсутствуют маршруты в базовых настройках', 1);
            }

            // список составленый из url запроса
            $url = explode('/', substr($address_str, strlen(PATH)));

            // если в пути указана директория admin
            // то произвести действия для админ. части сайта
            // strpos()- находит первое вхождение подстроки  strrpos()- находит последнее вхождение подстроки в строке
            if($url[0] && $url[0] === $this->routes['admin']['alias']){

                // формируем массив из url - запроса и при этом исключаем из него подстроку "admin/"
                // т.к для формирования маршрута она не нужна
                array_shift($url);

                // проверяем существует ли плагин к которому идет обращение
                // для этого проверяем наличие папки с плагином
                // если это плагин, то произвести действия для плагина, иначе сформировать переменные для админ.части
                if ($url[0] && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0])){

                    // получаем название плагина из url-строки
                    $pluginName = array_shift($url);
                    // получаем путь к настройкам плагина
                    $pluginSettingsPath = $this->routes['settings']['path'].ucfirst($pluginName . 'Settings');

                    // проверяем наличие файла
                    if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . $pluginSettingsPath . ".php")){
                        // Если проверка прошла успешна, то получаем namespace класса плагина
                        $pluginSettingsClass = str_replace('/', '\\', $pluginSettingsPath);
                        // получаем маршруты плагина
                        $this->routes = $pluginSettingsClass::get('routes');
                    }

                    // если стороний разработчик плагина записал в настройках маршрутов слеш или не записал их вовсе,
                    // то может случится, что в итоге в путях м.б появится двойной слеш
                    // Здесь мы исключаем такое повидение и при любом случае маршрут будет сформирован верно
                    $dir = $this->routes['plugins']['dir'] ? '/' . $this->routes['plugin']['dir'] . '/' : '/';

                    $dir = str_replace('//', '/', $dir);

                    $this->controller = $this->routes['plugins']['path'] . $pluginName . $dir;

                    $hrUrl = $this->routes['plugins']['hrUrl'];

                    $route = 'plugins';

                }else{

                    $this->controller = $this->routes['admin']['path'];

                    $hrUrl = $this->routes['admin']['hrUrl'];

                    $route = 'admin';
                }

            }else{// если другое то произвести действия для пользовательской. части сайта

                $hrUrl = $this->routes['user']['hrUrl'];

                $this->controller = $this->routes['user']['path'];

                $route = 'user';

            }
            // создаем маршрут
            $this->createRoute($route, $url);

            //формируем параметры запроса
            //например после подстроки с именем контроллера могут идти други параметры:
            // "http:/mysait.ru/controller/iphone/12/color/red"
            // Ниже приведенные алгоритм формирует из списка ['iphone', '12', 'color', 'red'] формирует массив
            // ['iphone' => '12', 'color' => 'red']
            if ($url[1]){
                $count = count($url);

                $key = '';

                if(!$hrUrl){
                    $i = 1;
                }else{
                    $this->parameters['alias'] = $url[1];
                    $i = 2;
                }

                for ( ; $i < $count; $i++ ){
                    if(!$key){
                        $key = $url[$i];
                        $this->parameters[$key] = '';
                    }else{
                        $this->parameters[$key] = $url[$i];
                        $key = '';
                    }
                }
            }

        }else{
           throw new RouteException('Некоректная директория сайта', 1);
        }
    }

    /**
     * Вспомогательный метод. Формирует маршруты пользовательской и админ. части сайта.
     *
     * @param $var - сюда попадает название части сайта( это либо "admin" или "user")
     * @param $arr - массив составленый из url запроса
     */
    private function createRoute($var, $arr) {

        $route = [];
        // если не пусто значение ячейки массива с url-запросом, содержащее название контроллера
        if(!empty($arr[0])){
            // если есть имя запрашиваемого контроллера в псевдонимах
            if ($this->routes[$var]['routes'][$arr[0]]){
                $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);
                // записываем в свойство controller имя контроллера с большой буквы из массива с псевдонимами
                $this->controller .= ucfirst($route[0]."Controller");
            }else{
                // если нет имени запрашиваемого контроллера в псевдонимах, то записываем в свойство controller
                // имя контроллера с большой буквы из массива самого url-запроса
                $this->controller .= ucfirst($arr[0]."Controller");
            }
        }else{
            // если идет обращение просто к главной странце сайта, то записываем дефолтный котроллер
            $this->controller .= $this->routes['default']['controller'];
        }

        // методы которые вызовутся у контроллера
        $this->inputMethod = $route[1] ? $route[1] : $this->routes['default']['inputMethod'];
        $this->outputMethod = $route[2] ? $route[2] : $this->routes['default']['outputMethod'];

        return;
    }

}
