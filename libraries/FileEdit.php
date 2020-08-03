<?php


namespace libraries;


class FileEdit
{
    // хранится информация о успешности добавления файлов
    protected $imgArr = [];
    // директория хранилища пользовтаельских файлов
    protected $directory;

    /**
     * Добавляет файл
     *
     * @param bool $directory - директория хранилища пользовательских файлов
     * @return array
     */
    public function addFile($directory = false){

        if (!$directory) $this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR;
            else $this->directory = $directory;

        // в массив $_FILES могут прийти как одиночный так и несколько файлов
        foreach ($_FILES as $key => $file){

            if (is_array($file)){

                $file_arr = [];

                // обрабатываем массив $_FILES в более удобное представление
                for ($i = 0; $i < count($file); $i++){
                    if (!empty($file['name'][$i])){
                        $file_arr['name'] = $file['name'][$i];
                        $file_arr['type'] = $file['type'][$i];
                        $file_arr['tmp_name'] = $file['tmp_name'][$i];
                        $file_arr['error'] = $file['error'][$i];
                        $file_arr['size'] = $file['size'][$i];

                        $res_name = $this->createFile($file_arr);

                        if ($res_name) $this->imgArr[$key][] = $res_name;
                    }
                }


            } else {

                if (!empty($file['name'])){

                    $res_name = $this->createFile($file);

                    if ($res_name) $this->imgArr[$key] = $res_name;
                }

            }

        }

        return $this->getFiles();

    }

    /**
     * Перемещает файл в директорию
     *
     * @param $file -
     */
    protected function createFile($file){

        // разбиваем имя файла по разделителю на массив
        $fileNameArr = explode('.', $file['name']);
        // получаем тип файла .Например: 'txt'
        $ext = $fileNameArr[count($fileNameArr) - 1];
        // убираем из названия файла подстроку с типом файла.
        // Например: было - '['file','.txt']', затем станет - ['file']
        unset($fileNameArr[count($fileNameArr) - 1]);

        $fileName = implode('.', $fileNameArr);

        // очищаем от лишних символов(кроме букв, '-', '_')
        $fileName = (new TextModify())->translit($fileName);

        // получаем уникальное имя файла
        $fileName = $this->checkFile($fileName, $ext);

        // получаем путь до файла(куда мы хотим его переместить)
        $fileFullName = $this->directory . $fileName;

        // перемещаем файл в хранилище \userfiles
        if ($this->uploadFile($file['tmp_name'], $fileFullName))
            return $fileName;

        return false;
    }

    /**
     * Создает уникальное имя файла
     *
     * @param $fileName - имя файла
     * @param $ext - тип файла
     * @param string $fileLastName - уникальный модификатор файла
     * @return string - возвращает уникальное имя файла
     */
    protected function checkFile($fileName, $ext, $fileLastName = ''){

        if (!file_exists($this->directory . $fileName . $fileLastName . '.' . $ext));
            return $fileName . $fileLastName . '.' . $ext;

        return $this->checkFile($fileName, $ext, '-' . hash('crc32', time() . mt_rand(0, 1000)));

    }


    /**
     * Перемещает файл(ы) по директориям.
     *
     * @param $tmpName - откуда хотим переместить
     * @param $dest - куда хотим переместить
     * @return bool - успех перемещения
     */
    protected function uploadFile($tmpName, $dest){

        if (move_uploaded_file($tmpName, $dest)) return true;

        return false;

    }

    /**
     * getter
     * @return array
     */
    protected function getFiles(){
        return $this->imgArr;
    }

}