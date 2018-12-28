<?php
/**
 * Created by PhpStorm.
 * User: wong
 * Date: 2018/12/28
 * Time: 2:51 PM
 */

namespace Lac\Face\FaceRecognition\Detector;

use Lac\Face\TigerBalm;

class Stage
{
    public $features;

    public $threshold;

    public function __construct($threshold)
    {
        $this->threshold = floatval($threshold);
        $this->features = [];
    }

    public function pass($grayImage, $squares, $i, $j, $scale)
    {
        $sum = 0;
        foreach ($this->features as $f) {
            $sum += $f->getVal($grayImage, $squares, $i, $j, $scale);
        }

        return $sum > $this->threshold;
    }
}