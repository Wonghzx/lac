<?php
/**
 * Created by PhpStorm.
 * User: wong
 * Date: 2018/12/27
 * Time: 2:22 PM
 */

namespace Lac\Face;

trait TigerBalm
{
    private static $instance;

    static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}