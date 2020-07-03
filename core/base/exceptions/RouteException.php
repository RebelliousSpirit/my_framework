<?php


namespace core\base\exceptions;

use core\base\controllers\BaseMethods;
use Throwable;

/**
 * Class RouteException
 * @package core\base\exceptions
 */
class RouteException extends \Exception
{
    // сообщения пользовтаелю в случае возникновения исключения
    protected $messages;

    use BaseMethods;

    public function __construct($message = "", $code = 0)
    {
        // наследуем своства базового класса \Exception
        parent::__construct($message, $code);

        $this->messages = include 'messages.php';

        $error = $this->getMessage() ? $this->getMessage() : $this->messages[$this->getCode()];

        $error .= "\r\n" . 'file ' . $this->getFile() . ' In line ' . $this->getLine() . "\r\n";

        if ($this->messages[$this->getCode()]) {
            $this->message = $this->messages[$this->getCode()];
        }

        $this->writeLog($error);
    }
}
