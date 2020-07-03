<?php


namespace libraries;


class TextModify
{
    // для транслита букв кирилицы в латинские
    protected $translitArr = [ 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => 'y', 'ы' => 'y',
        'ь' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', ' ' => '-',
    ];

    //
    protected $lowelLetter = ['а', 'е', 'и', 'о', 'у', 'э'];

    /**
     * Транслитирирует строки в из кирилицы в латинские
     *
     * @param $str - строка, которую нужно перевести в латинское
     * @return string
     */
    public function translit($str){

        $str = mb_strtolower($str);
        $temp_arr = [];

        // формируем из строки массив
        for ($i = 0; $i < mb_strlen($str); $i++){
            $temp_arr[] = mb_substr($str, $i, 1);
        }

        $link = '';

        // далее проводим транслитирацию кирилицы в латинские буквы
        if ($temp_arr){

            foreach ($temp_arr as $key => $char){

                if (array_key_exists($char, $this->translitArr)){

                    switch ($char){

                        case 'ъ':
                            // если следующая буква после 'ъ' равна 'е'
                            if ($temp_arr[$i + 1] == 'е') $link .= 'y';
                            break;

                        case 'ы':
                            if ($temp_arr[$i + 1] == 'й') $link .= 'i';
                                else $link .= $this->translitArr[$char];
                            break;

                        case 'ь':
                            // если следующая буква после 'ь' не последняя в строке
                            // и она присутствует в $this->lowelLetter
                            if ($temp_arr[$i + 1] !== count($temp_arr) &&
                                in_array($temp_arr[$i + 1], $this->lowelLetter)){
                                $link .= $this->translitArr[$char];
                            }
                            break;

                        default:
                            $link .= $this->translitArr[$char];
                            break;

                    }

                } else {

                    $link .= $char;

                }

            }

        }

        if ($link){
            // удаляем из строки все симоволы кроме: букв, '_', '-'
            // i - только с сиволами в нижнем регистре
            // u - работаем с юникодом т.е с мультибайтовой кодировкой
            $link = preg_replace('/[^a-z0-9_-]/iu', '', $link);

            // если знаков '_' или '-' в строке более 2, то меняем их на одиночный
            $link = preg_replace('/[-{2,}]/iu', '-', $link);
            $link = preg_replace('/[_{2,}]/iu', '_', $link);
            // удаляем в начале и в конце строки символы '_' или '-'
            // удаляем в начале и в конце строки символы '_' или '-'
            $link = preg_replace('/(^[_-]+)|([_-]+)$/iu', '', $link);
        }

        return $link;

    }

}