<?php
namespace app\records;

use app\helpers\Helper;
class Message extends \app\base\BaseAR{

    //状态
    const UNREAD = 1;

    const READED = 2;

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%message}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'content' => '内容',
            'status' => '状态',
            'receiver_id' => '接收人',
            'sender_id' => '发送人',
            'expire_time' => '过期时间',
            'url' => '详情地址',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['receiver_id', 'status'], 'required'],
            [['content'], 'string'],
            [['url'], 'string', 'max' => 180],
            [['expire_time', 'sender_id'], 'integer'], 
            [['status'], 'in', 'range' => [
                self::UNREAD,
                self::READED,
            ]]

        ];
    }

    /**
     * @inheritdoc
     */
    public function fields(){
        $fields = parent::fields();
        $this->send_time = Helper::convertTime(time() - $this->send_time);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert){
        if($insert){
            $this->send_time = time();
            $this->expire_time = $this->send_time + (int)\Yii::$app->get("toolService")->getCacheSetting('messageExpireTime')->getValue() * 60 * 60;
        }
        return parent::beforeSave($insert);
    }

    public function getReceiver(){
        return $this->hasOne(Member::class, ['id' => 'receiver_id'])->select(['id', 'real_name']);
    } 

    public function getSender(){
        return $this->hasOne(Member::class, ['id' => 'sender_id'])->select(['id', 'real_name']);
    }

}