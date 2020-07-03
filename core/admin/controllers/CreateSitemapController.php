<?php


namespace core\admin\controllers;


use core\base\controllers\BaseMethods;

class CreateSitemapController extends BaseAdmin
{
    use BaseMethods;

    // данные полученные после парсинга сайта
    protected $all_links = [];
    // временные данные парсинга сайта(текущие данные)
    protected $temp_links = [];
    // битые сслыки(например ведущие на 404 страницу)
    protected $bad_links = [];

    // ограничение на сбор ссылок
    protected $maxLinks = 5000;

    // файл с исключениями(Например при ссылки на стр 404)
    protected $parsingLogFile = 'parsingLog.txt';
    // ссылки на файлы, которые нужно исключить
    protected $fileArr = ['jpg', 'mp4', 'png', 'gif', 'jpeg', 'mp3', 'mpeg', 'pdf', 'xls', 'xlsx'];

    // фильтр для отсеивания повторяющихся ссылок, также содержит параметры для фильтрации ссылок
    protected $filterArr = [
        'url' => [],
        'get' => []
    ];

    /**
     * @param $links_counter - кол-во запросов
     * @param $redirect - для работы с асинхронными запросами, если false, то редирект не произойдет(после асинхроного
     * запроса ридерект не в коем случае не должен произойти)
     * @throws \Exception
     */
    public function inputData($links_counter = 1, $redirect = true){

        $links_counter = $this->clearNum($links_counter);

        // если нет библиотеки  CURL
        if (!function_exists('curl_init')){

            $this->cancel(0, 'Library CURL as apsent. Creation of sitemap imposible', '', true);

        }

        // если это не пользователь сайта
        if (!$this->userId) $this->execBase();

        // создаем таблицу для хранения временного и окончательного результата парсинга
        // Это крайне необходимо т.к в случае падения сервера не были утеряны текущие результаты парсинга
        if (!$this->checkParsingTable()){
            $this->cancel(0, 'You have problem with database table parsing_data', '', true);
        }

        // снимаем ограничение на время выполнения скрипта
        set_time_limit(0);

        // получаем резервные данные из таблицы хранения данных парсинга
        $reserve = $this->model->get('parsing_data')[0];

        // временно сохраняем имена полей и их значение
        $table_rows = [];

        foreach ($reserve as $name => $item){

            $table_rows[$name] = '';

            if ($item) $this->$name = json_decode($item);
            // помещаем в временные ссылки, ссылку на сам сайт, чтобы запустился ниже лежащий цикл while
            elseif($name === 'all_links' || $name === 'temp_links') $this->$name = [SITE_URL];

        }

        $this->maxLinks = (int)$this->maxLinks > 1 ? ceil($this->maxLinks/$links_counter) : $this->maxLinks;

        // пока в временных ссылках что-то есть
        while ($this->temp_links){

            $temp_links_count = count($this->temp_links);

            // сохраняем текущее значение временных сслылок
            $links = $this->temp_links;
            // и обнудяем хранилише временных сслыок (для того чтобы цикл не действовал бесконечно)
            $this->temp_links = [];

            if ($temp_links_count > $this->maxLinks){

                // делим массив $links на подмассивы
                // Например:
                //$input_array = array('a', 'b', 'c', 'd', 'e');
                //$input_array = array_chunk($input_array, 2);
                // Получится - [['a', 'b'],['c', 'd'],['e']]

                $links = array_chunk($links, ceil($temp_links_count/$this->maxLinks));

                // кол-во подмассивов в массиве $links
                $count_chunks = count($links);

                // парсим эти ссылки в подмассивах массива $links
                for ($i = 0; $i < $count_chunks; $i++){

                    $this->parsing($links[$i]);
                    unset($links[$i]);

                    // если что-то осталось
                    if ($links){

                        foreach ($table_rows as $name => $item){

                            if ($name === 'temp_links') $table_rows[$name] = json_encode(array_merge(...$links));
                                else $table_rows[$name] = json_encode($this->$name);

                        }

                        $this->model->edit('parsing_data',[
                            'fields' => $table_rows
                        ]);
                    }

                }

            } else {
                $this->parsing($links);
            }

            foreach ($table_rows as $name => $item){
                $table_rows[$name] = json_encode($this->$name);
            }

            $this->model->edit('parsing_data',[
                'fields' => $table_rows
            ]);

        }

        foreach ($table_rows as $name => $item){
           $table_rows[$name] = '';
        }

        // обнуляем данные в таблице парсинга, для того чтобы при последующих вызовах этого метода
        // собирались коректные данные
        $this->model->edit('parsing_data',[
            'fields' => $table_rows
        ]);

        // отфильтровываем данные после парсинга
        if ($this->all_links){

            foreach ($this->all_links as $key => $link){
                if (!$this->filter($link) || in_array($link, $this->bad_links)) unset($this->all_links[$key]);
            }

        }

        $this->createSitemap();


        // для синхроного запроса
        if ($redirect){
            // Если метод parsing выполнился успешно
            !$_SESSION['res']['answer'] && $_SESSION['res']['answer'] = '<div class="success">Sitemap is created</div>';

            // и возвращаем пользователя на исходную страницу
            $this->redirect();

        } else {// для асинхронного запроса
            $this->cancel(1, 'Sitemap is created! ' . count($this->all_links) . ' links', '', true);
        }

    }

    /**
     * Собирает ссылки с сайта
     *
     * @param $urls - сайт, который нужно распарсить
     * @throws \Exception
     */
    protected function parsing($urls){

        // если массив с сслыками не пришел прекращаем скрипт для экономии памяти
        if (!$urls) return;

        $curlMulti = curl_multi_init();

        $curl = [];

        /** проходим по ссылкам в мультипоточной цикле(начало)
            в памяти сохраняются все ответы на эти запросы
         */
        foreach ($urls as $i => $url){

            // инициализируем сианс и возвращаем дескриптор, который будет использвоатся с последующими функциями
            // Например с curl_setopt()
            $curl[$i] = curl_init();

            curl_setopt($curl[$i], CURLOPT_URL, $url); // задаем url, с которым будет производится операция
            curl_setopt($curl[$i], CURLOPT_RETURNTRANSFER, true);// вовзращать просто результат
            curl_setopt($curl[$i], CURLOPT_HEADER, true); // возвращать заголовки
            curl_setopt($curl[$i], CURLOPT_FOLLOWLOCATION, 1); // идти за редиректами
            curl_setopt($curl[$i], CURLOPT_TIMEOUT, 120); // ограничение выполнения запроса
            curl_setopt($curl[$i], CURLOPT_ENCODING, 'gzip,deflate'); // декодировать скжатые страницы

            // передаем дескриптор в мультипоточый curl
            curl_multi_add_handle($curlMulti, $curl[$i]);

        }

        // объяснение конструкции do-while смотреть в readme
        do{

            $status = curl_multi_exec($curlMulti, $active);
            // информация о ошибках
            $info = curl_multi_info_read($curlMulti);

            // формируем тело сообщения
            if (false !== $info){

                if ($info['result'] !== 0){

                    // получаем ключ ошибки
                    $i = array_search($info['handle'], $curl);

                    $error = curl_errno($curl[$i]);
                    $message = curl_error($curl[$i]);
                    $header = curl_getinfo($curl[$i]);

                    if ($error != 0){

                        $this->cancel(0, 'Error loading '. $header['url'] . ' http code '
                            . $header['http_code'] . ' error ' . $error . ' message ' . $message);

                    }

                }

            }

            if ($status > 0){
                $this->cancel(0, curl_multi_strerror($status));
            }


        } while($status === CURLM_CALL_MULTI_PERFORM || $active);
        /** конец */

        // результирующий массив с данными парсинга
        $result = [];

        foreach ($urls as $i => $url){

            // получаем контент
            $result[$i] = curl_multi_getcontent($curl[$i]);
            // удаляем дескрипторы запросов
            curl_multi_remove_handle($curlMulti, $curl[$i]);
            // закрываем текущее соединение(поток)
            curl_close($curl[$i]);

            // если в заголовок Content-type пришел не формат text/html
            if (!preg_match('/Content-type:\s+text\/html/ui', $result[$i])){

                $this->bad_links = $url;

                $this->cancel(0, 'Incorrect content type ' . $url);

                continue;

            }

            // если код ответа сервера выше из 200-ых закончить скрипт
            if (!preg_match('/HTTP\/\d\.?\d?\s+20\d/ui', $result[$i])){

                $this->bad_links = $url;

                $this->cancel(0, 'Incorrect server code ' . $url);

                continue;

            }

            // создаем ссылки
            $this->createLinks($result[$i]);

        }

        // закрываем мультипоточное соединение
        curl_multi_close($curlMulti);

    }

    /**
     * Ищет ссылки в переданом контенте страницы и записывает их в свойства $this->all_links $this->temp_links
     *
     * @param $content - контент страницы, где нужно найти необходимые данные
     */
    protected function createLinks($content){

        if ($content){

            // $links - все найденые ссылки на сайте. Они находятся в 2-ой ячейке массива
            // ищим ссылки в контенте сайта(содержание атрибута href тега a)
            preg_match_all( '/<a\s*?[^>]*?href\s*?=(["\'])(.+?)\1[^>]*?>/ui', $content, $links);

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
                            if (preg_match('/'. $ext .'(\s*?$|\?[^\/]*$)/ui', $link)){
                                continue 2; // возвращаемся в 2 уровню циклов. Уровни начинают считать изнутри - вверх
                            }

                        }

                    }

                    // если это относительная ссылка
                    if (strpos($link, '/') === 0){
                        $link = SITE_URL . $link;
                    }

                    $site_url = mb_str_replace('.', '\.',
                        mb_str_replace('/', '\/', SITE_URL));

                    // проверка не является ли сслыка битой
                    // проверка не является ли ссылка якорем
                    // в начале ссылки должен быть адрес сайта
                    // исключаем также повторение ссылки(последняя проверка будет продолжительная т.к предется
                    // проверить большой массив )
                    if (!in_array($link, $this->bad_links)
                        && !preg_match('/^('. $site_url .')?\/?#[^\/]*?$/ui', $link)
                        && strpos($link, SITE_URL) === 0
                        && !in_array($link, $this->all_links)){

                            $this->all_links[] = $link;
                            $this->temp_links[] = $link;

                    }

                }

            }

        }

    }

    /**
     * Фильтрует ссылку $link согласно настройкам $this->filterArr
     *
     * @param $link - ссылка, которую нужно проверить
     * @return bool - успех проверки
     */
    protected function filter($link){

        if ($this->filterArr){

            foreach ($this->filterArr as $type => $values) {

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

    /**
     * Проверяет наличии таблицы 'parsing_table' в БД и е сли ее нет, то создает ее и делает в ней 1 пустую запись
     *
     * @return bool - успех проверки на наличии таблицы 'parsing_data' или ее создания
     */
    protected function checkParsingTable(){

        $tables = $this->model->showTables();

        if (!in_array('parsing_data', $tables)){

            $query = 'CREATE TABLE parsing_data (all_links longtext, temp_links longtext, bad_links longtext)';

            // добавляем в таблицу 1 пустую запись
            if (!$this->model->query($query, 'c') ||
                !$this->model->add('parsing_data', ['fields' => ['all_links'=>'', 'temp_links'=>'', 'bad_links' => '']])
            ) return false;

        }

        return true;

    }

    /**
     * Создает тело служебных сообщений, отображающиеся в в результате ошибки или успешности парсинга.
     * Тело сообщения передается js-скрипту, который затем отобразит это сообщение пользователю.
     * Если в $success передать 0, то запишет также сообщение в логи.
     *
     * @param int $success -
     * @param string $message - сообщение пользователю
     * @param string $log_message - запись в лог
     * @param bool $exit - завершить ли работу скрипта
     * @throws \Exception
     */
    protected function cancel($success = 0, $message = '', $log_message = '', $exit = false){

        $exitArr = [];

        $exitArr['success'] = $success;
        $exitArr['message'] = $message ? $message : 'ERROR PARSING';
        $exitArr['log_message'] = $log_message ? $log_message : $exitArr['message'];

        $class = 'success';

        if (!$exitArr['success']){
            $class = 'error';
            $this->writeLog($log_message, 'parsing_log.txt');
        }

        if ($exit){
            $exitArr['message'] = '<div class="' . $class . '">' . $exitArr['message'] . '</div>';
            exit(json_encode($exitArr));
        }

    }

    /**
     * Создает xml-файл 'sitemap.xml'- в котором записывает данные собранные после парсинга сайта
     *
     * @throws \Exception
     */
    protected function createSitemap(){

        $dom = new \domDocument('1.0', 'utf-8');
        // включаем форматирование выходных данных
        $dom->formatOutput = true;

        $root = $dom->createElement('urlset');
        $root->setAttribute('xmlns', 'http://www.sitemap.org/schemas/sitemap/0.9');
        $root->setAttribute('xmlns:xls', 'http://w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:schemaLocation', 'http://www.sitemap.org/schemas/sitemap/0.9 http://http://www.sitemap.org/schemas/sitemap/0.9/sitemap.xsd');

        $dom->appendChild($root);

        $sxe = simplexml_import_dom($dom);

        if ($this->all_links){

            $date = (new \DateTime());
            $lastMod = $date->format('Y-m-d') . 'T' . $date->format('H:i:s');

            foreach ($this->all_links as $item){

                $elem = trim(substr($item, mb_strlen(SITE_URL)), '/');
                $elem = explode('/', $elem);

                // определяем уровень вложенности
                // Например:
                // если ссылка будет: http://site.com/dir/site/dir - это будет соотвествовать 0.8
                $count = '0.' . (count($elem) - 1);
                $priority = 1 - (float)$count;
                if ($priority == 1) $priority = '1.0';

                $urlMain = $sxe->addChild('url');

                // htmlspecialchars($item) - превращаем знаки '&<>' в html-сущности т.к
                // XML выдаст ошибку
                $urlMain->addChild('loc', htmlspecialchars($item)); // сама ссылка

                $urlMain->addChild('loc', $lastMod); // дата модификации
                $urlMain->addChild('changefreq', 'weekly'); // обновлять еженедельно
                $urlMain->addChild('priority', $priority); // уровень вложености

            }

        }

        $dom->save($_SERVER['DOCUMENT_ROOT'] . PATH . 'sitemap.xml');
        // TODO доработать метод стр 131-137
    }
}