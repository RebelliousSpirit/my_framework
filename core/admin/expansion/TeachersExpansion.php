<?php
/**
 * ВНИМАНИЕ ради экономии памяти в случае когда
 * нужно изменить всего несколько текущих своств(своства объекта класса baseAdmin и его рдителя BaseController), то
 * в файлах расширения не обязательно использовать ООП, нужно использовать процедурный подход программирования.
 */

namespace core\admin\expansion;


use core\base\controllers\Singleton;

class TeachersExpansion
{
    use Singleton;

    public function expansion($args = []){

        $this->title = 'la laa la';

    }
}