<?php
namespace core\user\controllers;

use core\admin\models\Model;
use core\base\controllers\BaseController;

class IndexController extends BaseController
{
    /**
     * Ввод данных
     * для того чтобы сформировать страницу нужно вызывать метод render
     *
     * @return array
     * @throws \ReflectionException
     * @throws \core\base\exceptions\RouteException
     */
    protected function inputData () {

        $model = Model::instance();

        $res = $model->get('teachers', [
            'where' => ['id' => '10,11'],
            'operand' => ['IN'],
            'join' => [
                'student_teachers' => [
                    'on' => ['id', 'teachers']
                ],
                'students' => [
                    'fields' => ['name'],
                    'on' => [ 'students','id']
                ]
            ],
            'join_structure' => true
        ]);

    }

}