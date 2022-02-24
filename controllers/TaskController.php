<?php
namespace app\controllers;

use app\helpers\Helper;
use app\models\Constants;
use app\models\Position;
use app\models\TaskNode;
use app\records\Task;
use Yii;

class TaskController extends BaseController{

    public function actionIndex(){
        $this->setPosition(new Position([
            'name' => '全部任务',
            'jumpUrl' => Constants::TASKS
        ]));
        $taskId = $this->get('main_task_id', 0);
        $hasActive = $this->get('task_active', 1);
        if($taskId < 1){
            $this->setPosition(new Position(['name' => '列表']));
        }else{
            $task = Yii::$app->get('taskService')->getTaskById($taskId);
            $name = "任务【{$task->name}】直接子任务列表";
            if(!$hasActive){
                $name = "任务【{$task->name}】直接活跃子任务列表";
            }
            $this->setPosition(new Position(['name' => $name]));
        }
        $id = $this->get('task_id', null);
        //优先匹配主任务$taskId，次任务$id在主任务为0时仅显示次任务的数据
        if($id > 0 && $taskId < 1){
            $taskId = null;
        }
        $startTime = $this->get('start_time');
        $endTime = $this->get('end_time');
        $params['timestamp_range'] = Helper::timestampRange($startTime, $endTime);
        $params['project_id'] = $this->get('project_id', null);
        $params['task_id'] = $this->get('task_id', null);
        $params['type'] = $this->get('type', null);
        $params['main_task_id'] = $this->get('main_task_id', null);
        $params['name'] = $this->get('name', null);
        if(!$hasActive){
            $params['status'] = [
                Task::WAITING_STATUS,
                Task::WAITTING_ADVANCE_STATUS,
                Task::ADVANCE_STATUS
            ];
        }else{
            $params['status'] = $this->get('status', null);
        }
        $params['priority'] = $this->get('priority', null);
        $params['receive_user_id'] = $this->get('receive_user_id', null);
        $params['publisher_id'] = $this->get('publisher_id', null);
        return $this->render(Constants::TASKS, [
            'status' => Task::getTaskStatus(),
            'tasks' => Yii::$app->get('projectService')->getTasks($params),
            'types' => Yii::$app->get('taskService')->getTaskCategories(),
            'priorities' => Task::getPriorities()
        ]);
    }

    public function actionStat(){
        $taskId = $this->get("id");
        $service = Yii::$app->get('taskService');
        $task = $service->getTaskById($taskId);
        if($task == null){
            return $this->error('任务不存在');
        }
        if($task->project == null){
            return $this->error('项目不存在');
        }
        list($id, $level) = $service->getAllForkTaskId($task);
        $params['id'] = $id;
        $params['timestamp_range'] = Helper::timestampRange($this->get('start_time'), $this->get('end_time'));
        $params['receive_user_id'] = $this->get('receive_user_id');
        return $this->success('success', $service->statistics($task->project, $params, true));
    }

    public function actionGetForkTasks(){
        $service = Yii::$app->get('taskService');
        $taskId = $this->get('id');
        $task = $service->getTaskById($taskId);
        if($task == null){
            return $this->error('任务不存在');
        }
        return $this->success('success', TaskNode::createTaskNodeByTask($task));
    }

}