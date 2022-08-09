<?php
namespace app\records;

class TaskParticipants extends \app\base\BaseAR{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%task_participants}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'task_id' => '所在任务',
            'participant' => '参与人',
        ];
    }
}