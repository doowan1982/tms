<?php
namespace app\models;

//站点位置模型
class Position extends \app\base\BaseModel{
    
    //位置名称
    public $name;
    
    //所在的url
    public $jumpUrl = '';

    //下一个Position，如果为null，则为最后一集位置
    public $nextPosition;

    public function setNextPosition($position){
        if($this->nextPosition != null){
            $this->nextPosition->setNextPosition($position);
        }else{
            $this->nextPosition = $position;
        }
    }

    //数组位置信息
    public function toHtml($tpl = ''){
        if($this->jumpUrl){
            $array = ["<a href='{$this->jumpUrl}'>{$this->name}</a>"];
        }else{
            $array = ["<span class='current-position'>$this->name</span>"];
        }
        if($this->nextPosition){
            $array[] = $this->nextPosition->toHtml();
        }
        return implode('&nbsp;>&nbsp;', $array);
    }

}