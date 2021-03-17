<?php
namespace app\records;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
class TaskDetail extends \app\base\BaseAR{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%task_details}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['update_time'],
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
            'content' => '工作内容',
            'start_time' => '开始时间',
            'end_time' => '截至时间',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['task_id'], 'required'],
            [['content'], 'string'],
            [['start_time', 'end_time'], 'integer'], 
        ];
    }

}