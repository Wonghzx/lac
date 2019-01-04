<?php
/**
 * Created by PhpStorm.
 * User: wong
 * Date: 2018/12/27
 * Time: 9:53 AM
 */

namespace Lac\Face;

use Lac\Face\FaceRecognition\Detector\FaceDetector;
use Lac\Face\FaceRecognition\VideoCapture\VideoCapture;

class FaceQuery
{
    public function test()
    {
        $imgName = './xxxx.jpg';

//        $s = FaceDetector::getInstance()->FaceScan($imgName);//->getImage();
        VideoCapture::getInstance();
//        echo "<img src='{$s}'>";

    }
}
