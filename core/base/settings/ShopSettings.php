<?php
/**
 * Настройки расширения 'Shop'
 * 
 */

namespace core\base\settings;

/**
 * Этот класс реализует паттерн Singleton
 *
 * Это учебный класс для демонстрации способа получения массивов(либо других данных) из
 * другого класса и слияние их со своими массивами
 *
 * Class ShopSettings
 * @package core\settings
 */
class ShopSettings
{

    use BaseSettings;

    private $routes = [
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            // дополнительная настройка произвольных путей к плагину
            'dir' => 'controller',
            //
            'routes' => [
                'routes' => [
                    'product' => 'goods',
                ]
            ],
        ],
    ];

    private $templateArr = [
        'text'=>['price', 'abort', 'name'],
        'textarea'=>['goods_content'],
    ];

}
