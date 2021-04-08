<?php
namespace app\models;

use Yii;
use yii\base\Arrayable;
use yii\data\DataProviderInterface;
use yii\base\Model;
class Success extends \app\base\BaseModel{

    public $message;

    public $status = 200;

    public $data = [];

    /**
     * 返回错误消息
     * @author doowan
     * @date   2020-03-26
     * @return string
     */
    public function data(){
        $this->data = \Yii::createObject('yii\rest\Serializer')->serialize($this->data);
        return $this->toArray();
    }

}