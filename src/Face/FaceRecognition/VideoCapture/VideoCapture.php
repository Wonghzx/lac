<?php
/**
 * Created by PhpStorm.
 * User: wong
 * Date: 2019/1/2
 * Time: 5:10 PM
 */

namespace Lac\Face\FaceRecognition\VideoCapture;

use CV\VideoCapture as video, CV\Face\LBPHFaceRecognizer;
use function CV\{
    imread, cvtColor, equalizeHist, waitKey, resize, imwrite, imshow
};
use Lac\Face\FaceRecognition\Detector\FaceDetector;
use Lac\Face\TigerBalm;

class VideoCapture
{
    use TigerBalm;

    private $key;

    private $frame;

    public function __construct()
    {
        $this->initVideo();
    }


    public function show()
    {
        $frame = $this->FaceScan();
        imshow("Face recognition", $frame);//暂时摄像头捕捉到的图像

    }

    private function FaceScan()
    {
        return FaceDetector::getInstance()->FaceScan($this->frame);
    }

    private function initVideo($is_show = true)
    {

        $capture = new video();//创建视频对象
        $capture->open(0);//打开电脑一个摄像头
        if (!$capture->isOpened()) {
            exit('打开摄像头失败');
        }
        $this->frame = null;
        $number = 1;
        while (true) {
            $capture->read($this->frame);//把当前摄像头的图像捕捉并保存到$frame变量当中
            if ($is_show) {
                $this->show();
            } else {
                return $this->frame;
            }
            $this->key = waitKey(50);//等待用户输入
            if ($this->key != -1) {
                $this->key = chr($this->key);
            }
            switch ($this->key) {
                case'p'://当用户键入'p'则拍照
                    break;
                case 'q'://当用户键入's'，跳出循环
                    break 2;
                default:
                    break;
            }
        }
    }
}