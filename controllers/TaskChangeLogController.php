<?php

namespace app\controllers;

use Yii;
use yii\web\Response;
use app\models\Position;
use app\records\Task;
use app\services\Tasks;
use app\helpers\Helper;
class TaskChangeLogController extends BaseController{

    public function actionIndex(){
        $taskId = $this->get('task_id');
        $taskService = Yii::$app->get('taskService');
        $logs = $taskService->getChangeLogByTaskId($taskId)->all();
        $task = $taskService->getTask($taskId);
        array_unshift($logs, $task);
        $status = Task::getTaskStatus();
        $types = $taskService->getTaskCategories();
        $priorities = Task::getPriorities();
        $array = [];
        foreach($logs as $key=>$value){
            $log = $value->toArray(['log_id', 'project_id', 'log_time', 'task_id', 'publisher_id', 'receive_user_id', 'receive_time', 'name', 'priority', 'difficulty', 'type', 'status', 'expected_finish_time', 'real_finish_time','publish_time'], ['receiver']);
            $log['priority'] = $priorities[$value['priority']];
            $log['status'] = $status[$value['status']];
            foreach($types as $type){
                if($type['id'] == $value['type']){
                    $log['type'] = $type['name'];
                }
            }
            if($key === 0){
                $log['log_time'] = '-';
            }
            $array[] = $log;
            unset($logs[$key]);
        }
        return $this->success('success', $array);
    }

    public function actionContent(){
        $logId = $this->get('id');
        $taskService = Yii::$app->get('taskService');
        $log = $taskService->getChangeLogById($logId);
        if($log == null){
            return $this->error('数据不存在');
        }
        return $this->success('success', $log->description);
    }

}