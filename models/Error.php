<?php
namespace app\models;
use yii\base\Model;
class Error extends \app\base\BaseModel{

    public $message;

    public $status = 400;

    public $data = [];

    /**
     * 返回错误消息
     * @author doowan
     * @date   2020-03-26
     * @return string
     */
    public function getMessage(){
        $message = [];
        if($this->message instanceof Model){
            foreach($this->message->getErrors() as $key=>$value){
                $message[] = "{$value[0]}";
            }
        }else if(is_array($this->message)){
            $message = $this->message;
        }else{
            $message = (array)$this->message;
        }
        $this->message = $message;
        $this->format();
        return $this->message;
    }

    protected function format(){
        $this->message = implode('<br>', $this->message);
    }

}