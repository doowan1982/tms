<?php
namespace app\base;
use Yii;
use app\models\Constants;
trait CommonTrait{
    /**
     * 返回错误信息
     * @return string
     */
    public function getErrorString(){
        $errors = [];
        if($this instanceof \yii\base\Model){
            foreach($this->getErrors() as $name=>$value){
                //提取Model中的第一条错误信息
                $errors[] = current($value);
            }
        }
        return implode("\r\n", $errors);
    }

    //异常处理
    public function exception($error, $statusCode = 400){
        throw new \yii\web\HttpException($statusCode, $error);
    }

    public static function e($message, $category=Constants::ERROR_LOG){
        Yii::error($message, $category);
    }

    public static function w($message, $category=Constants::WARNING_LOG){
        Yii::warning($message, $category);
    }

    public static function i($message, $category=Constants::INFO_LOG){
        Yii::info($message, $category);
    }

    public static function t($message, $category=Constants::TRACE_LOG){
        Yii::trace($message, $category);
    }

}