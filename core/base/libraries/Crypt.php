<?php


namespace core\base\libraries;


use core\base\controllers\Singleton;

/**
 * Class Crypt
 * Осуществляет шифровку и дешифровку данных
 *
 * @package core\base\models
 */
class Crypt
{

    use Singleton;

    // метод шифрования
    private $cryptMethod = 'AES-128-CBC';
    // алгоритм хэширования
    private $hashAlgoritm = 'sha256';
    // длина строки хэша
    private $hashLength = 32;

    /**
     * Шифрует строку
     *
     * @param $str
     * @return string
     */
    public function encrypt($str){

        // Теперь нам нужно создать вектор инициализации (IV, Initialization Vector) -
        // случайные данные, на основе которых будет произведено шифрование.
        // определяем длину iv для выбранного метода шифрования
        // openssl_cipher_iv_length - вовзращает 16 символа
        $ivSize = openssl_cipher_iv_length($this->cryptMethod);

        // генерируем iv необходимой длины
        $iv = openssl_random_pseudo_bytes($ivSize);

        // получаем саму шифрованую строку
        // OPENSSL_RAW_DATA - возвратить двойчный код(по умолчанию возвращает в base64)
        $cipherText = openssl_encrypt($str, $this->cryptMethod, CRYPT_KEY,OPENSSL_RAW_DATA, $ivSize);

        // для последующей коректной дешифровки создаем хэш-код
        // hash_hmac - вовзращает 32 символа
        $hmac = hash_hmac($this->hashAlgoritm, $cipherText, CRYPT_KEY, true);

        // возвращаем в кодировке base64 для того чтобы ее смог понять браузер
        return $this->cryptCombine($cipherText, $iv, $hmac);

    }

    /**
     * Дешифрует переданую закодированую строку
     *
     * @param $str
     * @return bool|string
     */
    public function decrypt($str){

        $ivSize = openssl_cipher_iv_length($this->cryptMethod);

        $cryptData = $this->cryptUnCombine($str, $ivSize);

        $originalPlainText = openssl_decrypt($cryptData['str'], $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $cryptData['iv']);

        $calcmac = hash_hmac($this->hashAlgoritm, $cryptData['str'], CRYPT_KEY, true);

        // если хэши совпадают
        if (hash_equals($cryptData['hmac'], $calcmac)) return $originalPlainText;

        return false;

    }

    /**
     * Перемешивает строки. Делается это для увеличения безопасности шифрования.
     *
     * @param $str - шифрованая строка
     * @param $iv - псевдобайтовая строка
     * @param $hmac - хэш-код
     */
    protected function cryptCombine($str, $iv, $hmac){

        $newStr = '';

        $strLen = strlen($str);

        $counter = (int)ceil(strlen(CRYPT_KEY) / (strlen($str) + $this->hashLength));

        $progress = 1;

        if ($counter >= $strLen) $counter = 1;

        for ($i = 0; $i < $strLen; $i++){

            if ($counter < $strLen){

                if ($counter === $i){

                    $newStr .= substr($iv, $progress - 1, 1);
                    $progress++;
                    $counter+=$progress;

                }

            } else {
                break;
            }

            $newStr .= substr($str, $i, 1);

        }

        // если остался остаток строки в iv и str, то добавляем их в конце
        $newStr .= substr($str, $i);
        $newStr .= substr($iv, $progress - 1);

        $newStrHalf = (int)ceil(strlen($newStr) / 2);

        $newStr = substr($newStr, 0, $newStrHalf) . $hmac . substr($newStr, $newStrHalf);

        return base64_encode($newStr);

    }

    /**
     * Осуществляет разбиение комбинированой строки, после обработки методом cryptCombine
     *
     * @param $str - зашифрованая строка, которую нужно разбить
     * @param $ivSize - длина
     * @return array
     */
    protected function cryptUnCombine($str, $ivSize){

        $cryptData = [];

        $str = base64_decode($str);

        $hashPos = (int) ceil(strlen($str)/2 - $this->hashLength/2);

        $cryptData['hmac'] = substr($str, $hashPos, $this->hashLength);

        $str = str_replace($cryptData['hmac'], '', $str);

        $counter = (int) ceil(strlen(CRYPT_KEY) / (strlen($str) - $ivSize + $this->hashLength));

        $progress = 2;

        $cryptData['iv'] = '';
        $cryptData['str'] = '';

        for ($i = 0; $i < strlen($str); $i++){

            if ($ivSize + strlen($cryptData['str']) < strlen($str)){

                if ($i === $counter){

                    $cryptData['iv'] .= substr($str, $counter, 1);
                    $progress++;
                    $counter += $progress;


                } else {
                    $cryptData['str'] .= substr($str, $i, 1);
                }

            } else { // если остался остаток в строках iv и str, то добавляем их в конце и заканчиваем цикл

                $cryptDataLen = strlen($cryptData['str']);

                $cryptData['str'] .= substr($str, $i, strlen($str) - $ivSize - $cryptDataLen);
                $cryptData['iv'] .= substr($str, $i + (strlen($str) - $ivSize - $cryptDataLen));

                break;

            }

        }

        return $cryptData;

    }

}