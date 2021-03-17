<?php
namespace app\records;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
class TaskLifecycle extends \app\base\BaseAR{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%task_lifecycle}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['create_time'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'task_id' => '所在任务',
            'message' => '信息',
            'create_time' => '创建时间',
            'member_id' => '参与成员',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['task_id', 'member_id'], 'required'],
            [['message'], 'string', 'max' => 255],
            [['member_id', 'task_id'], 'integer'], 
        ];
    }

    public function getMember(){
        return $this->hasOne(Member::className(), ['id' => 'member_id']);
    }

}