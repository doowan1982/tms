<?php
namespace app\models\sender;

use Yii;
class DbSender extends \app\models\MessageSender{

    /**
     * 消息发送者
     * @var app\models\Member
     */
    public $sender;

    /**
     * @inheritdoc
     */
    public function send(){
        $tool = Yii::$app->get('toolService');

        if($this->member == null){
            self::e("用户不存在或已禁用，无法发送消息【{$this->message}】");
            return true;
        }
        
        if(!$tool->sendMessage($this->member, $this->message, $this->url)){
            return $this->setError($tool->getErrorString());
        }
        return true;
    }

}