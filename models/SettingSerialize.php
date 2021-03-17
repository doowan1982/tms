<?php
namespace app\models;

/**
 * 配置参数序列化
 */
class SettingSerialize{

    private $value;

    public function __construct($value){
        $this->setValue($value);
    }

    public function setValue($value){
        if(json_decode($value) !== false){
            $this->value = json_decode($value);
            if(!$this->value){
                $this->value = $value;
            }
        }else{
            $this->value = $value;
        }
    }

    public function getValue(){
        return $this->value;
    }

    public function toString(){
        if(is_string($this->value) || is_numeric($this->value)){
            return $this->value;
        }
        if(is_object($this->value) || is_array($this->value)){
            return json_encode($this->value, JSON_UNESCAPED_UNICODE);
        }
        return $this->value;
    }

}