<?php
/**
 * Created by PhpStorm.
 * User: wong
 * Date: 2018/12/27
 * Time: 10:30 AM
 */

namespace Lac\Face\FaceRecognition\Detector;

use CV\CascadeClassifier;
use CV\Size;
use CV\Mat;
use CV\Point;
use CV\Scalar;
use function CV\{
    normalize, imread, cvtColor, equalizeHist, rectangleByRect, imshow, waitKey, putText, imwrite, resize
};
use const CV\{
    NORM_MINMAX, CV_8UC1, CV_8UC3, IMREAD_GRAYSCALE, COLOR_BGR2GRAY, CV_HAAR_SCALE_IMAGE
};

use Lac\Face\TigerBalm;
use Exception;
use function PHPSTORM_META\type;

class FaceDetector
{
    use TigerBalm;

    private $classifierSize;

    private $stages;

    private $image;

    private $width;

    private $height;

    private $foundRects = [];

    public function __construct()
    {
//        $this->initClassifier('./data/lbpcascades/lbpcascade_frontalface.xml');
    }

    /**
     * facedetect + opencv
     * 扫描检测人脸
     * @author  Wong <[842687571@qq.com]>
     * @param string $
     * @created on 2018/12/28 11:47 AM
     * @copyright Copyright (c)
     */
    public function FaceScan($frame = '')
    {
        if (!is_object($frame)) {
            $is_f = true;
            $frame = imread($frame);
        }
        $cascadeClassifier = new CascadeClassifier();
        $cascadeClassifier->load('./models/haarcascades/haarcascade_frontalface_alt2.xml');

        $gray = cvtColor($frame, COLOR_BGR2GRAY);//转为灰度图
        equalizeHist($gray, $gray);

        $faces = null;
        $cascadeClassifier->detectMultiScale($gray, $faces, 1.1, 2, CV_HAAR_SCALE_IMAGE, new Size(50, 50));

        $face = null;
        for ($i = 0; $i < count($faces); $i++) {
            if ($faces[$i]->height > 0 && $faces[$i]->width > 0) {
//                $face = $gray->getImageROI($faces[$i]);
                $textLb = new Point($faces[$i]->x, $faces[$i]->y - 10);
                rectangleByRect($frame, $faces[$i], new Scalar(255, 0, 0), 1, 8, 0);
                putText($frame, 'zhixue', $textLb, 3, 1, new Scalar(0, 0, 255));
            }
        }
        if ($is_f) {
            imshow('test',$frame);
            waitKey(0);
        } else {
            return $frame;

        }
    }

    /**
     * 扫描检测人脸废弃
     * @author  Wong <[842687571@qq.com]>
     * @param string $
     * @created on 2018/12/28 11:47 AM
     * @copyright Copyright (c)
     */
    public function FaceScanDiscard(string $imageFile)
    {
        $imageInfo = getimagesize($imageFile);

        if (!$imageInfo) {
            return "Could not open file: " . $imageFile;
        }

        $this->width = $imageInfo[0];
        $this->height = $imageInfo[1];
        $imageType = $imageInfo[2];

        $this->checkType($imageType, $imageFile);

        $this->foundRects = [];

        $maxScale = min($this->width / $this->classifierSize[0], $this->height / $this->classifierSize[1]);
        $squares = $img = $grayImage = array_fill(0, $this->width, array_fill(0, $this->height, null));

        for ($i = 0; $i < $this->width; $i++) {
            $col = 0;
            $col2 = 0;
            for ($j = 0; $j < $this->height; $j++) {
                $colors = imagecolorsforindex($this->image, imagecolorat($this->image, $i, $j));
                $value = (30 * $colors['red'] + 59 * $colors['green'] + 11 * $colors['blue']) / 100;
                $img[$i][$j] = $value;
                $grayImage[$i][$j] = ($i > 0 ? $grayImage[$i - 1][$j] : 0) + $col + $value;
                $squares[$i][$j] = ($i > 0 ? $squares[$i - 1][$j] : 0) + $col2 + $value * $value;
                $col += $value;
                $col2 += $value * $value;
            }
        }
        $baseScale = 2;
        $scale_inc = 1.25;
        $increment = 0.1;
        $min_neighbors = 3;
        for ($scale = $baseScale; $scale < $maxScale; $scale *= $scale_inc) {
            $step = (int)($scale * 24 * $increment);
            $size = (int)($scale * 24);
            for ($i = 0; $i < $this->width - $size; $i += $step) {
                for ($j = 0; $j < $this->height - $size; $j += $step) {
                    $pass = true;
                    $k = 0;
                    foreach ($this->stages as $s) {

                        if (!$s->pass($grayImage, $squares, $i, $j, $scale)) {
                            $pass = false;
                            //echo $k."\n";
                            break;
                        }
                        $k++;
                    }
                    if ($pass) {
                        $this->foundRects[] = ["x" => $i, "y" => $j, "width" => $size, "height" => $size];
                    }
                }
            }
        }
        return $this;
    }

    public function getFaces(bool $moreConfidence = false)
    {
        return $this->faceMerge($this->foundRects, 2 + intval($moreConfidence));
    }

    /**
     * @author  Wong <[842687571@qq.com]>
     * @param null $fileName
     * @param bool $moreConfidence
     * @param bool $showAllRects
     * @created on 2018/12/28 4:01 PM
     * @copyright Copyright (c)
     */
    public function getImage($fileName = null, $moreConfidence = false, $showAllRects = true)
    {
        //创建一个画布
        $canvas = imagecreatetruecolor($this->width, $this->height);

        //重采样拷贝图像整大小
        imagecopyresampled(
            $canvas,
            $this->image,
            0, 0, 0, 0,
            $this->width, $this->height, $this->width, $this->height
        );

        //分配颜色
        $blue = imagecolorallocate($canvas, 0, 0, 255);
        $red = imagecolorallocate($canvas, 255, 0, 0);

        if ($showAllRects) {
            //矩形 x y w h
            foreach ($this->foundRects as $r) {
                imagerectangle($canvas, $r['x'], $r['y'], $r['x'] + $r['w'], $r['y'] + $r['h'], $blue);
            }
        }

//        $rects = $this->faceMerge($this->foundRects, 2 + intval($moreConfidence));
//        foreach ($rects as $r) {
//            imagerectangle($canvas, $r['x'], $r['y'], $r['x'] + $r['width'], $r['y'] + $r['height'], $red);
//        }

        ob_start();
        imagepng($canvas);
        $fileContent = ob_get_contents();
        ob_end_clean();
        return ('data:image/png;base64,' . base64_encode($fileContent));


    }

    private function faceMerge($foundRect, $min_neighbors): array
    {
        $retour = [];
        $ret = [];
        $nb_classes = 0;

        for ($i = 0; $i < count($foundRect); $i++) {
            $found = false;
            for ($j = 0; $j < $i; $j++) {
                if ($this->equals($foundRect[$j], $foundRect[$i])) {
                    $found = true;
                    $ret[$i] = $ret[$j];
                }
            }
            if (!$found) {
                $ret[$i] = $nb_classes;
                $nb_classes++;
            }
        }
        $neighbors = array();
        $rect = array();
        for ($i = 0; $i < $nb_classes; $i++) {
            $neighbors[$i] = 0;
            $rect[$i] = array("x" => 0, "y" => 0, "width" => 0, "height" => 0);
        }

        for ($i = 0; $i < count($foundRect); $i++) {
            $neighbors[$ret[$i]]++;
            $rect[$ret[$i]]['x'] += $foundRect[$i]['x'];
            $rect[$ret[$i]]['y'] += $foundRect[$i]['y'];
            $rect[$ret[$i]]['width'] += $foundRect[$i]['width'];
            $rect[$ret[$i]]['height'] += $foundRect[$i]['height'];
        }

        for ($i = 0; $i < $nb_classes; $i++) {
            $n = $neighbors[$i];
            if ($n >= $min_neighbors) {
                $r = array("x" => 0, "y" => 0, "width" => 0, "height" => 0);
                $r['x'] = ($rect[$i]['x'] * 2 + $n) / (2 * $n);
                $r['y'] = ($rect[$i]['y'] * 2 + $n) / (2 * $n);
                $r['width'] = ($rect[$i]['width'] * 2 + $n) / (2 * $n);
                $r['height'] = ($rect[$i]['height'] * 2 + $n) / (2 * $n);

                $retour[] = $r;
            }
        }
        return $retour;
    }

    private function equals($r1, $r2): bool
    {
        $distance = (int)($r1['width'] * 0.2);
        if ($r2['x'] <= $r1['x'] + $distance &&
            $r2['x'] >= $r1['x'] - $distance &&
            $r2['y'] <= $r1['y'] + $distance &&
            $r2['y'] >= $r1['y'] - $distance &&
            $r2['width'] <= (int)($r1['width'] * 1.2) &&
            (int)($r2['width'] * 1.2) >= $r1['width']) {
            return true;
        }

        if ($r1['x'] >= $r2['x'] &&
            $r1['x'] + $r1['width'] <= $r2['x'] + $r2['width'] &&
            $r1['y'] >= $r2['y'] &&
            $r1['y'] + $r1['height'] <= $r2['y'] + $r2['height']) {
            return true;
        }

        return false;
    }

    private function checkType(int $imageType, string $imageFile)
    {
        try {
            if ($imageType == IMAGETYPE_JPEG) {

                $this->image = imagecreatefromjpeg($imageFile);

            } elseif ($imageType == IMAGETYPE_GIF) {

                $this->image = imagecreatefromgif($imageFile);

            } elseif ($imageType == IMAGETYPE_PNG) {

                $this->image = imagecreatefrompng($imageFile);

            } else {
                throw new Exception("Unknown Fileformat: " . $imageType . ", " . $imageFile);
            }
        } catch (Exception $e) {
            echo "Error：{$e->getMessage()}";
        }
    }

    private function initClassifier($classifierFile)
    {
        $xmls = file_get_contents($classifierFile);
        $xmls = preg_replace("/<!--[\S|\s]*?-->/", "", $xmls);
        $xml = simplexml_load_string($xmls);

        $this->classifierSize = explode(" ", strval($xml->children()->children()->size));
        $this->stages = [];

        $stagesNode = $xml->children()->children()->stages;
        foreach ($stagesNode->children() as $stageNode) {
            $stage = new Stage($stageNode->stage_threshold);

            foreach ($stageNode->trees->children() as $treeNode) {
                $feature = new Feature(floatval($treeNode->_->threshold), floatval($treeNode->_->left_val), floatval($treeNode->_->right_val), $this->classifierSize);

                foreach ($treeNode->_->feature->rects->_ as $r) {
                    $feature->add(Rect::fromString(strval($r)));
                }

                $stage->features[] = $feature;
            }
            $this->stages[] = $stage;
        }
    }


}