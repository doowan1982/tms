<?php
namespace app\records;

class TaskDescription extends \app\base\BaseAR{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%tasks_description}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'description' => '任务描述',
            'task_id' => '所在任务',
        ];
    }

    /**
     * 更新描述信息
     * @param  Task   $task
     * @return boolean
     */
    public function updateDescription(Task $task){
        $this->task_id = $task->id;
        $this->description = $task->description;
        return $this->save() !== false;
    }


}