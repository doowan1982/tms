<?php
namespace app\records;

use yii\behaviors\TimestampBehavior;
use app\models\Constants;
class MemberConfig extends \app\base\BaseAR{
    
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%member_config}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'member_id' => '会员',
            'name' => '配置名称',
            'value' => '配置内容',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['member_id', 'name'], 'required'],
            [['member_id'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['value'], 'string', 'max' => 500],
        ];
    }

    public function fields(){
        if($this->name == Constants::SEND_TO_EMAIL){
            $this->value = explode(',', $this->value);
        }
        return parent::fields();
    }

}