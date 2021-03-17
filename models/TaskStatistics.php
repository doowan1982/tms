<?php
namespace app\models;

use Yii;
use app\base\BaseModel;
use app\records\Task;
use app\records\Member as MemberAR;
class TaskStatistics extends BaseModel{

    /**
     * 实施人
     * @var array
     */
    public $receiver;

    /**
     * 待实施数量
     * @var integer
     */ 
    public $awaitTasksCount = 0;

    /**
     * 待完成任务数量
     * @var integer
     */
    public $activedTasksCount = 0;

    /**
     * 终止任务数量
     * @var integer
     */
    public $terminateTasksCount = 0;

    /**
     * 未领取的任务数量
     * @var integer
     */
    public static $undoTasksCount = 0;

    private static $service;
    public function init(){
        parent::init();
        self::$service = Yii::$app->get('taskService');
    }

    public function statTaskCount(Task $task){
        if(self::$service->isActived($task)){
            if($task->status === Task::WAITTING_ADVANCE_STATUS || $this->receiver['id'] < 1){
                $this->awaitTasksCount++;
                return;
            }
            $this->activedTasksCount++;
            return;
        }
        $this->terminateTasksCount++;
    }

    //任务统计
    public static function stat(array $data){
        $statistics = [];
        self::$undoTasksCount = 0;
        foreach($data as $task){
            if($task->receiver == null){
                self::$undoTasksCount++;
                continue;
            }
            $statModel = null;
            if(!isset($statistics[$task->receiver->id])){
                $statModel = new static();
                $statModel->receiver = [
                    'id' => $task->receiver->id,
                    'real_name' => $task->receiver->real_name,
                ];
                $statistics[$task->receiver->id] = $statModel;
            }else{
                $statModel = $statistics[$task->receiver->id];
            }
            $statModel->statTaskCount($task);
        }
        return array_values($statistics);
    }

}