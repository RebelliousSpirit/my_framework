<?php

class testController
{
    /**
     * Учебный метод для демонстрации множественной вставки в таблицу БД
     * Вызвать можно в методе inputData()
     */
    protected function addTeachers()
    {

        $fields = [
            ['name'=>'lena', 'img'=>'1user.png'],
            ['name'=>'vika', 'img'=>'2user.png'],
            ['name'=>'roma', 'img'=>'3user.png'],
        ];

        $this->model->add('teachers', [
            'fields' => $fields,
        ]);

    }
}