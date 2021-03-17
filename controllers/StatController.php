<?php

namespace app\controllers;

use Yii;
use yii\web\Response;
use app\models\Position;
use app\records\Project;
use app\records\Task;
use app\models\UploadForm;
use app\helpers\Helper;
use app\services\Tasks;
use app\services\Projects;
use app\models\Constants;
use yii\helpers\ArrayHelper;
class StatController extends BaseController{

    public function actionIndex(){
        $this->setPosition(new Position(['name' => '统计']));
        return $this->render('index', [
            'projectStatus' => Project::getStatusMap(),
            'taskStatus' => Task::getTaskStatus(),
            'priorities' => Task::getPriorities(),
            'taskTypes' => Yii::$app->get('taskService')->getTaskCategories(),
            'roles' => Yii::$app->get('userService')->getRoles(),
        ]);
    }

    public function actionMembers(){
        $roles = $this->get('role');
        if(count($roles) == 0){
            $roles = null;
        }
        $userService = Yii::$app->get('userService');
        $roles = $userService->getRoles($roles);
        return $this->success('success', $userService->getMembersByRoles($roles));
    }


    public function actionResult(){
        //查询项目
        $projectService = Yii::$app->get('projectService');
        $projects = $projectService->getProjects([
            'status' => $this->get('project_status', null),
        ]);
        foreach($projects as $key=>$project){
            $projects[$key] = $project->id;
        }
        if(count($projects) == 0){
            return $this->success('success', []);
        }

        //查询用户
        $roles = $this->get('role', null);
        $members = $this->get('members', null);
        $userService = Yii::$app->get('userService');
        if($members != null && count($members) > 0){
            $members = $userService->getMembers(['id' => $members]);
        }else if($roles){
            $members = $userService->getMembersByRoles($roles);
        }
        $params = [];
        //统计任务
        $params['project_id'] = $projects;
        $params[$this->get('date_type', 'create_time')] = Helper::timestampRange($this->get('start_time', null), $this->get('end_time', null));
        $params['project_id'] = $projects;
        $params['status'] = $this->get('task_status', null);
        $params['priority'] = $this->get('priority', null);
        $params['type'] = $this->get('task_type', null);

        return $this->success('success', [
                'list' => $projectService->getTaskStatByMembers($members, $params),
                'taskStatus' => Task::getTaskStatus(),
                'priorities' => Task::getPriorities(),
                'taskTypes' => Yii::$app->get('taskService')->getTaskCategories(),
            ]);
    }

}