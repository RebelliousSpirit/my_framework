<?php


namespace core\admin\controllers;


use core\base\controllers\BaseAjaxController;

class AjaxController extends BaseAjaxController
{

    /**
     * @return false|string|void -
     * @throws \Exception
     */
    public function ajax(){

        if (isset($this->data['ajax'])){

            switch ($this->data['ajax']){

                case 'sitemap':
                    return (new CreateSitemapController())->inputData($this->data['links_counter'], false);
                    break;

            }

        }

        return json_encode([
            'success' => 0,
            'message' => 'No ajax variable'
        ]);

    }

}