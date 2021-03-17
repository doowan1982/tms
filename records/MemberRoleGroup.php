<?php
namespace app\records;

use yii\behaviors\TimestampBehavior;
class MemberRoleGroup extends \app\base\BaseAR{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%member_role_groups}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'member_id' => '成员',
            'role_id' => '角色',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['member_id', 'role_id'], 'required'],
            [['member_id', 'role_id'], 'integer'],
        ];
    }
    
    public function getRole(){
        return $this->hasOne(MemberRole::class, ['id' => 'role_id']);
    }

}