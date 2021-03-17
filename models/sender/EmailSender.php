<?php
namespace app\models\sender;

use Yii;
class EmailSender extends \app\models\MessageSender{

    /**
     * @inheritdoc
     */
    public function send(){
        if($this->member == null){
            self::error("用户不存在或已禁用，无法发送消息【{$this->message}】");
            return true;
        }
        if($this->url){
            $url = Yii::$app->request->getHostName().$this->url;
            $this->message .= "<a href='{$url}' target='_blank'>{$url}</a>";
        }
        return Yii::$app->get('toolService')->sendEmailMessage($this->member->email, "任务信息", $this->message);
    }

}