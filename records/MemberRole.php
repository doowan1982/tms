<?php
namespace app\records;

use yii\behaviors\TimestampBehavior;
class MemberRole extends \app\base\BaseAR{
    
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%member_role}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'name' => '用户名',
            'show_order' => '显示顺序',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['name'], 'required'],
            [['name'], 'unique'],
            [['name'], 'string','max'=>50],
        ];
    }

}