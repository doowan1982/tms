<?php
namespace app\records;

class TaskLogDescription extends TaskDescription{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%tasks_log_description}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'description' => '任务描述',
            'task_log_id' => '所在任务',
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function updateDescription(Task $task){
        $this->task_log_id = $task->log_id;
        $this->description = $task->description;
        return $this->save() !== false;
    }


}