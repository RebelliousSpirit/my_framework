<?php
// Пример с однопоточной обработкой ссылок
// Недостатки: время выполнения скрипта слишком долгое, из-за чего сервер может прервать выполнения скрипта и
// выдать ошибку

namespace core\admin\controllers;

use core\base\controllers\BaseMethods;

class CreateSitemapController extends BaseAdmin
{
    use BaseMethods;

    // данные полученные после парсинга сайта
    protected $linkArr = [];
    // файл с исключениями(Например при ссылки на стр 404)
    protected $parsingLogFile = 'parsingLog.txt';
    // ссылки на файлы, которые нужно исключить
    protected $fileArr = ['jpg', 'mp4', 'png', 'gif', 'jpeg', 'mp3', 'mpeg', 'pdf', 'xls', 'xlsx'];

    // фильтр для отсеивания повторяющихся ссылок, также содержит параметры для фильтрации ссылок
    protected $filterArr = [
        'url' => [],
        'get' => []
    ];

    protected function inputData(){

        // если нет библиотеки  CURL
        if (!function_exists('curl_init')){

            $this->writeLog('Отсутствует библиотека CURL');
            $_SESSION['res']['answer'] = '<div class="error">Library CURL as apsent. Creation of sitemap imposible</div>';

            $this->redirect();
        }

        // снимаем ограничение на время выплнения скрипта
        set_time_limit(0);

        // удаляем файл логов из-за присуствия ненужных логов
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile));
        @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile);

        // парсим сайт В будущем этот метод требует доработки.Например: можно будет создать в
        // админке сайта форму и оттуда вводить небходимый url
        $this->parsing(SITE_URL);

        $this->createSitemap();

        // Если метод parsing выполнился успешно
        !$_SESSION['res']['answer'] && $_SESSION['res']['answer'] = '<div class="success">Sitemap is created</div>';

        // и возвращаем пользователя на исходную страницу
        $this->redirect();

    }

    /**
     * Собирает ссылки с сайта
     *
     * @param $url - сайт, который нужно распарсить
     * @param int $index -
     * @throws \Exception
     */
    protected function parsing($url, $index = 0){

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url); // задаем url, с которым будет производится операция
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);// вовзращать просто результат
        curl_setopt($curl, CURLOPT_HEADER, true); // возвращать заголовки
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // идти за редиректами
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // ограничение выполнения запроса
        curl_setopt($curl, CURLOPT_RANGE, 0 - 4194000); // ограничение длины возвращаемых строк

        $res = curl_exec($curl);

        // закрываем соединение и освобождаем память(обязательно для экономии ресурсов)
        curl_close($curl);

        // если в заголовок Content-type пришел не формат text/html закончить скрипт
        if (!preg_match('/Content-type:\s+text\/html/ui', $res)){
            unset($this->linkArr[$index]); // удаляем эту ссылку из хранилища ссылок
            $this->linkArr = array_values($this->linkArr); // отсортировываем обратно массив
            return;
        }

        // если код ответа сервера выше из 200-ых закончить скрипт
        if (!preg_match('/HTTP\/\d\.?\d?\s+20\d/ui', $res)){
            $this->writeLog('Не коректная сслыка при парсинге - ' . $url, $this->parsingLogFile );
            unset($this->linkArr[$index]); // удаляем эту ссылку из хранилища ссылок
            $this->linkArr = array_values($this->linkArr); // отсортировываем обратно массив
            $_SESSION['res']['answer'] = '<div class="error">Incorrect link in parsing - '. $url .
                '<br>Sitemap is created' . '</div>';
            return;
        }

        // $links - все найденые ссылки на сайте. Они находятся в 2-ой ячейке массива
        preg_match_all( '/<a\s*?[^>]*?href\s*?=(["\'])(.+?)\1[^>]*?>/ui', $res, $links);

        // далее обрабатываем эти ссылки
        if ($links[2]){

            foreach ($links[2] as $link){

                // если это главная страница или в конце переданного url-адреса стоит слэш(мы работаем с ссылками в конце
                //, которых отсутствует слэш), то перейти к следующей итерации цикла
                if ($link === '/' || $link === SITE_URL . '/') continue;

                // исключаем из ссылок ссылки на файлы
                foreach ($this->fileArr as $ext){

                    if ($ext){

                        // на всякий случай экранируем знаки ('"\ ) для коректного поиска с помощью
                        // регулярного выражения
                        $ext = addslashes($ext);
                        // экранируем точку
                        $ext = str_replace('.', '\.', $ext);

                        // здесь в регулярювыражении также учитывается пробелы(сколько угодно раз) после $ext(тип файла)
                        // или версии файла например: '?ver1.0'? кроме конечно слэшев
                        if (preg_match('/'. $ext .'\s*?$|\?[^\/]/ui', $link)){
                            continue 2; // возвращаемся в 2 уровню циклов. Уровни начинают считать изнутри - вверх
                        }

                    }

                }

                // если это относительная ссылка
                if (strpos($link, '/') === 0){
                    $link = SITE_URL . $link;
                }

                if (!in_array($link, $this->linkArr) && $link !== '#' && strpos($link, SITE_URL) === 0){

                    if ($this->filter($link)){

                        $this->linkArr[] = $link;
                        $this->parsing($link, count($this->linkArr) - 1);

                    }

                }

            }

        }


    }

    protected function filter($link){

        if ($this->filterArr){

            foreach ($this->fileArr as $type => $values) {

                if ($values){

                    foreach ($values as $item){

                        $item = str_replace('/', '\/', addslashes($item));

                        if ($type == 'url'){
                            if (preg_match('/^[^\?]*'. $item .'/ui', $link)) return false;
                        }

                        // ищем в get-параметрах ссылки
                        if ($type == 'get'){
                            // '|' - означает логическое 'или' в регуляр. выражениях
                            // &amp; - html сущность использующийся в url-запросах
                            // '&' - знак 'и' использующийся в url-запросах
                            if (preg_match('/(\?|&amp;|=|&)'. $item .'(=|&amp;|&|$)/ui', $link)) return false;
                        }

                    }

                }

            }

        }

        return true;
    }

    protected function createSitemap(){

    }
}