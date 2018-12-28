<?php
/**
 * Created by PhpStorm.
 * User: wong
 * Date: 2018/12/27
 * Time: 9:53 AM
 */

namespace Lac\Face;

use Lac\Face\FaceRecognition\Detector\FaceDetector;

class FaceQuery
{
    public function __construct()
    {
        $s = FaceDetector::getInstance()->FaceScan('./005EneYkly1fosvje9oc4j30gl0hctn9.jpg')->getImage();

    }
}
