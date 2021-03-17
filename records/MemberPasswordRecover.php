<?php
namespace app\records;

use yii\behaviors\TimestampBehavior;
class MemberPasswordRecover extends \app\base\BaseAR{
    
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%member_password_recover}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'member_id' => '会员',
            'expired_time' => '过期时间',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['member_id', 'expired_time'], 'required'],
            [['member_id', 'expired_time'], 'integer'],
        ];
    }

}