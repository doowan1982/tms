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
            'name' => '项目管理',
            'jumpUrl' => Constants::PROJECT_HOME
        ]));
        $this->setPosition(new Position(['name' => '所有任务']));
        $startTime = $this->get('start_time');
        $endTime = $this->get('end_time');
        $params['timestamp_range'] = Helper::timestampRange($startTime, $endTime);
        $params['project_id'] = $this->get('project_id', null);
        $params['task_id'] = $this->get('task_id', null);
        $params['type'] = $this->get('type', null);
        $params['main_task_id'] = $this->get('main_task_id', 0);
        $params['name'] = $this->get('name', null);
        $params['status'] = $this->get('status', null);
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
        $tasks = $service->getAllForkTasks($task);
        //按树形生成任务数据
        $categories = Yii::$app->get('taskService')->getTaskCategories();
        $root = new TaskNode(null, $this->getNodeAttribute($task, $categories));
        $nodes = [$root];
        while(true){
            $node = array_shift($nodes);
            foreach($tasks as $k=>$task){
                $n = $node->find($task->task_id);
                if($n != null){
                    $newNode = new TaskNode($n, $this->getNodeAttribute($task, $categories));
                    $n->setNode($newNode);
                    $nodes[] = $newNode;
                    unset($tasks[$k]);
                }
            }
            if(count($nodes) === 0){
                break;
            }
        }
        return $this->success('success', $root);
    }

    private function getNodeAttribute(Task $task, $categories){
        $status = Task::getTaskStatus();
        $typeName = '';
        foreach($categories as $category){
            if($category->id == $task->type){
                $typeName = $category->name;
                break;
            }
        }
        return [
            'id' => $task->id,
            'name' => $task->name,
            'status' => $task->status,
            'type' => $task->type,
            'projectId' => $task->project->id,
            'projectName' => $task->project->name,
            'statusName' => $status[$task->status],
            'typeName' => $typeName,
        ];
    }

}