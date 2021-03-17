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
class ProjectController extends BaseController{

    public function actionIndex(){
        $this->setPosition(new Position(['name' => '项目管理']));
        $params = $this->parameters;
        if(!isset($params['start_time'])){
            $params['start_time'] = 0;
        }
        if(!isset($params['end_time'])){
            $params['end_time'] = 0;
        }
        $params['timestamp_range'] = Helper::timestampRange($params['start_time'], $params['end_time']);
        unset($params['start_time'], $params['end_time']);
        $projectService = \Yii::$app->get('projectService');
        $member = $this->getMember();
        $projectService->on(Projects::EVENT_BEFORE_SEARCH, function($event) use($params, $member){
            $focusProjects = Yii::$app->get('userService')->getFocusProjects($member, []);
            if(count($focusProjects) > 0){
                $event->query->andWhere([
                    'id' => ArrayHelper::getColumn($focusProjects, 'id')
                ]);
            }
        });
        return $this->render('index', [
            'status' => Project::getStatusMap(),
            'data' => $projectService->projects($params, 30)
        ]);
    }

    public function actionCreate(){
        $this->setPosition(new Position(['name' => '项目管理', 'jumpUrl' => Constants::PROJECT_HOME]));
        $this->setPosition(new Position(['name' => '编辑项目']));
        $project = \Yii::$app->get('projectService')->findById($this->get('id'));
        if(!$project){
            $project = new Project(['version_number' => 'v1']);
        }
        return $this->render('edit', [
            'status' => Project::getStatusMap(),
            'action' => 'create',
            'project' => $project
        ]);
    }

    public function actionTasks(){
        $service = \Yii::$app->get('projectService');
        $project = $service->findById($this->get('project_id'));
        $this->setPosition(new Position(['name' => '项目管理', 'jumpUrl' => Constants::PROJECT_HOME]));
        $this->setPosition(new Position(['name' => "【{$project->name}】任务列表"]));
        if($project == null){
            return $this->error('项目不存在');
        }
        $startTime = $this->get('start_time');
        $endTime = $this->get('end_time');
        $params['timestamp_range'] = Helper::timestampRange($startTime, $endTime);
        $params['name'] = $this->get('name', null);
        $params['type'] = $this->get('type', null);
        $params['status'] = $this->get('status', null);
        $params['username'] = $this->get('username', null);
        $params['task_id'] = $this->get('task_id', null);
        $params['main_task_id'] = $this->get('main_task_id', null);
        $params['priority'] = $this->get('priority', null);
        $params['receive_user_id'] = $this->get('receive_user_id', null);
        $params['publisher_id'] = $this->get('publisher_id', null);
        return $this->render('tasks', [
                'tasks' => $service->getTasksByProject($project, $params),
                'status' => Task::getTaskStatus(),
                'types' => \Yii::$app->get('taskService')->getTaskCategories(),
                'project' => $project,
                'priorities' => Task::getPriorities(),
            ]);
    }

    public function actionDelete(){
        $id = $this->post('id');
        $service = \Yii::$app->get('projectService');
        $project = $service->findById($id);
        if(!$service->deleteProject($project)){
            return $this->error($service->getErrorString());
        }
        self::i("删除【{$project->name}】项目信息");
        return $this->success('删除成功');
    }

    public function actionDetail(){
        $project = \Yii::$app->get('projectService')->findById($this->get('id'));
        return $this->success('ok', $project->description);
    }

    public function actionSave(){
        $data = $this->post();
        $service = \Yii::$app->get('projectService');
        $project = $service->findById($this->post('id'));
        $message = '修改';
        if(!$project){
            $message = '新增';
            $project = new Project([
                    'project_doc_attachement' => ''
                ]);
        }
        $data['expected_start_time'] = strtotime($data['expected_start_time']);
        $data['expected_end_time'] = strtotime($data['expected_end_time']);
        $project->setAttributes($data);
        if(!$service->saveProject($project)){
            return $this->error($project);
        }
        self::i("{$message}【{$project->name}】项目信息");
        $this->setPosition(new Position(['name' => '项目管理', 'jumpUrl' => Constants::PROJECT_HOME]));
        $this->setPosition(new Position(['name' => '编辑项目']));
        return $this->render('edit', [
                'status' => Project::getStatusMap(),
                'action' => 'create',
                'project' => $project
            ]);
    }

    public function actionTaskDetail(){
        $task = \Yii::$app->get('taskService')->getTaskById($this->get('id'));
        exit($this->renderFile(Yii::getAlias('@view/project/taskDetail.php'), [
            'data' => $task->description
        ]));
    }

    public function actionSaveTask(){
        $data = $this->post();
        $id = $this->post('id');
        $taskService = \Yii::$app->get('taskService');
        $task = $taskService->getTaskById($id);
        if($task  == null){
            $task = new Task();
        }
        $data['publish_time'] = (int)strtotime($data['publish_time']);
        $data['expected_finish_time'] = (int)strtotime($data['expected_finish_time']);
        $member = null;
        if(isset($data['receive_user_id']) && $data['receive_user_id'] > 0){
            $member = Yii::$app->get('userService')->getMember($data['receive_user_id']);
        }
        unset($data['receive_user_id']); //此处不更新接收人数据，如果指定，则在saveTask中自动分配
        $task->setAttributes($data);

        $currentMember = $this->getMember();

        //记录任务的进程信息
        $taskService->on(Tasks::EVENT_AFTER_SAVE_TASK, function($event) use($taskService, $currentMember){

            $message = '新增任务';
            if(!$event->task->getIsNewRecord()){
                $message = "修改任务";
            }
            $message = "项目【{$event->project->name}】{$message}【{$event->task->name}】<a href='#' class='taskChangeLog' data-id='{$event->task->id}'>变更信息列表</a>";
            
            $event->isValid = $taskService->createTaskLifeCycle($event->task->id, $message, $currentMember->id);
        });

        if(!$taskService->saveTask($task, $member)){
            return $this->error($taskService->getErrorString());
        }
        self::i("修改任务【{$task->name}】的数据");
        $url = Constants::EDIT_TASK."?project_id={$task->project_id}";
        if($id){
            $url .= "&id={$id}";
        }
        return $this->redirect($url);
    }

    public function actionEditTask(){
        $id = $this->get('id');
        $project = \Yii::$app->get('projectService')->findById($this->get('project_id'));
        if(!$project){
            return $this->error('项目不存在');
        }

        $service = \Yii::$app->get('taskService');
        //如果有则查询对应的父级任务
        $taskId = $this->get('task_id', 0);
        $parentTask = null;
        if($taskId > 0){
            $parentTask = $service->getTaskById($taskId);
        }

        $task = $service->getTaskById($id);

        if($task == null){
            $task = new Task([
                'publish_time' => time(),
                'expected_finish_time' => '',
                'task_id' => $parentTask != null ? $parentTask->id : 0,
                'receive_user_id' => 0,
            ]);
        }else if($task->task_id > 0){
            $parentTask = $service->getTaskById($task->task_id);
        }

        if(!$service->isActived($task)){
            return $this->error('任务不在活跃状态');
        }

        $receiver = null;
        if($task->receive_user_id > 0){
            $receiver = Yii::$app->get('userService')->getMember($task->receive_user_id);
        }
        $this->setPosition(new Position(['name' => '项目管理', 'jumpUrl' => Constants::PROJECT_HOME]));
        $this->setPosition(new Position([
                'name' => "【{$project->name}】任务列表",
                'jumpUrl' => Constants::TASKS_ON_PROJECT."?project_id={$project->id}"
            ]));
        $this->setPosition(new Position(['name' => '编辑任务']));
        return $this->render('editTask', [
                'task' => $task,
                'parentTask' => $parentTask,
                'project' => $project,
                'receiver' => $receiver,
                'priorities' => Task::getPriorities(),
                'types' => $service->getTaskCategories(),
            ]);
    }

    public function actionPendingTasks(){
        $params = $this->get();
        $startTime = $this->get('start_time');
        $endTime = $this->get('end_time');
        $params['publish_timestamp_range'] = Helper::timestampRange($startTime, $endTime);
        unset($params['start_time'], $params['end_time'], $params['page']);
        $this->setPosition(new Position(['name' => '待分发任务']));

        if(!isset($params['project_id'])){
            $params['project_id'] = [];
        }
        //验证个人配置
        $focusProjects = Yii::$app->get('userService')->getFocusProjects($this->getMember(), $params['project_id']);
        if(count($focusProjects) > 0){
            $params['project_id'] = ArrayHelper::getColumn($focusProjects, 'id');
        }

        $projectService = Yii::$app->get('projectService');
        return $this->render('pendingTasks', [
                'tasks' => $projectService->getPendingTasks($params),
                'status' => Task::getTaskStatus(),
                'types' => \Yii::$app->get('taskService')->getTaskCategories(),
                'priorities' => Task::getPriorities(),
            ]);
    }

    public function actionTaskLifecycle(){
        $taskId = $this->get('task_id');
        $taskService = Yii::$app->get('taskService');
        $task = $taskService->getTaskById($taskId);
        if($task == null){
            return $this->error('无效的任务');
        }
        $this->setPosition(new Position([
            'name' => '项目管理', 
            'jumpUrl' => Constants::PROJECT_HOME
        ]));
        $this->setPosition(new Position([
            'name' => "【{$task->project->name}】任务列表", 
            'jumpUrl' => Constants::TASKS_ON_PROJECT."?project_id={$task->project_id}"
        ]));
        $this->setPosition(new Position(['name' => "【{$task->name}】进度信息"]));
        return $this->render('taskLifecycle', [
                'lifecycle' => $taskService->getLifecycleByTask($task),
            ]);
    }

    public function actionSearch(){
        $params['name'] = $this->get('name', null);
        if(!$params['name']){
            unset($params['name']);
        }
        $projectService = \Yii::$app->get('projectService');
        $member = $this->getMember();
        $projectService->on(Projects::EVENT_BEFORE_SEARCH, function($event) use($params, $member){
            $focusProjects = Yii::$app->get('userService')->getFocusProjects($member, []);
            if(count($focusProjects) > 0){
                $event->query->andWhere([
                    'id' => ArrayHelper::getColumn($focusProjects, 'id')
                ]);
            }
        });
        $projects = $projectService->projects($params, 30);
        $list = $projects->getModels();
        foreach($list as $key=>$project){
            $list[$key] = [
                'id' => $project->id,
                'name' => $project->name,
                'version_number' => $project->version_number,
            ];
        }
        $projects->setModels($list);
        return $this->pagination('success', $projects);
    }

    public function actionBatchReceiveTasks(){
        $taskIds = (array)$this->post('task_id');
        if(empty($taskIds)){
            return $this->error('请选择任务');
        }
        $member = $this->getMember();
        $taskService = Yii::$app->get('taskService');
        foreach($taskIds as $key=>$taskId){
            $task = $taskService->getTaskById($taskId);
            if(!$this->receiveTask($taskService, $member, $task)){
                return $this->error($taskService->getErrorString());
            }
            $taskIds[$key] = $task->name;
        }
        $taskIds = implode(',', $taskIds);
        self::i("领取了编号为【{$taskIds}】的任务");
        return $this->success('领取成功');
    }

    public function actionBatchAllocateTask(){
        $memberId = $this->post('member_id');
        $taskIds = (array)$this->post('task_id');
        if(empty($taskIds)){
            return $this->error('请选择任务');
        }
        $member = Yii::$app->get('userService')->getMember($memberId);
        if($member == null){
            return $this->error('用户数据不存在');
        }
        $taskService = Yii::$app->get('taskService');
        $assigner = $this->getMember();
        foreach($taskIds as $key=>$id){
            $taskService->on(Tasks::EVENT_AFTER_ALLOCATE_TASK,  function($event) use($taskService, $member, $assigner){

                if($member->id !== $assigner->id){
                    $message = "【{$assigner->real_name}】给您分配了【{$event->project->name}】【{$event->task->name}】任务";
                    $sender = Yii::$app->get('toolService')
                                ->getMessageSender([
                                    'task' => $event->task,
                                    'member' => $member,
                                    'message' => $message,
                                    'url' => Constants::PUBLISHED_TASKS."?task_id={$event->task->id}"
                                ]);
                    if(!$sender->send()){
                        $event->isValid = false;
                        $event->setError($sender->getErrorString());
                        return;
                    }
                }

                $message = "【{$assigner->real_name}】分配【{$event->project->name}】【{$event->task->name}】任务给【{$member->real_name}】";
                
                $event->isValid = $taskService->createTaskLifeCycle($event->task->id, $message, $member->id);
                if(!$event->isValid){
                    $event->setError($taskService->getErrorString());
                }
            });
            $task = $taskService->getTaskById($id);
            if(!$taskService->allocateTask($member, $task)){
                return $this->error($taskService->getErrorString());
            }
            $taskIds[$key] = $task->name;
        }
        $taskIds = implode(',', $taskIds);
        self::i("批量分配任务【{$taskIds}】给【{$member->real_name}】");
        return $this->success('任务已分配');
    }

    public function actionReceiveTask(){
        $id = $this->post('id');
        $member = $this->getMember();
        $taskService = Yii::$app->get('taskService');
        $task = $taskService->getTaskById($id);
        //任务领取进程
        if(!$this->receiveTask($taskService, $member, $task)){
            return $this->error($taskService->getErrorString());
        }
        self::i("领取了编号为【{$task->name}】的任务");
        return $this->success('领取成功'); 
    }

    public function actionDeleteTask(){
        $id = $this->post('id');
        $taskService = Yii::$app->get('taskService');
        $task = $taskService->getTaskById($id);
        if($task == null){
            return $this->error('任务不存在');
        }
        if(!$taskService->deleteTask($this->getMember(), $task)){
            return $this->error($taskService->getErrorString());
        }
        self::i("删除了【{$task->name}】的任务");
        return $this->success('删除成功');
    }

    public function actionGetExceptMembers(){
        return $this->success('success', Yii::$app->get('userService')->getExceptMembers($this->getMember()));
    }

    public function actionStat(){
        $projectId = $this->get("id");
        $service = Yii::$app->get('projectService');
        $project = $service->findById($projectId);
        if($project == null){
            return $this->error('项目不存在');
        }
        $params['timestamp_range'] = Helper::timestampRange($this->get('start_time'), $this->get('end_time'));
        $params['receive_user_id'] = $this->get('receive_user_id');
        return $this->success('success', $service->statistics($project, $params));
    }

    public function actionTerminateTask(){
        $id = $this->post('id');
        $result = $this->terminateOrRestartTask($id, Task::IN_VALID);
        if(is_string($result)){
            return $this->error($result);
        }
        return $this->success('操作成功');
    }

    public function actionRestartTask(){
        $id = $this->post('id');
        $result = $this->terminateOrRestartTask($id, Task::VALID);
        if(is_string($result)){
            return $this->error($result);
        }
        return $this->success('操作成功');
    }

    public function actionFinishInfo(){
        $id = $this->get('id');
        $taskService = Yii::$app->get('taskService');
        $task = $taskService->getTaskById($id);
        if($task == null){
            return $this->error('数据不存在');
        }
        if($task->taskCodeFragment == null){
            return $this->success('success', '无信息');
        }
        return $this->success('success', $task->taskCodeFragment->code_fragment);
    }

    public function actionDownloadAttachement(){
        $id = $this->get('id');
        $project = Yii::$app->get('projectService')->findById($id);
        if($project == null){
            return $this->error('项目不存在');
        }
        if(!$project->project_doc_attachement){
            return $this->error('文档不存在');
        }
        $project->project_doc_attachement = Yii::getAlias("@webroot{$project->project_doc_attachement}");
        $info = pathinfo($project->project_doc_attachement);
        $fp = fopen($project->project_doc_attachement, 'r');
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:".filesize($project->project_doc_attachement));
        header('Content-Disposition: attachment; filename='.$info['filename'].'.'.$info['extension']);  
        readfile($project->project_doc_attachement);
        exit;
    }


    private function terminateOrRestartTask($id, $isValid){
        $taskService = Yii::$app->get('taskService');

        $task = $taskService->getTaskById($id);
        if($task == null){
            return $this->setError('任务不存在');
        }

        $member = $this->getMember();

        //后置事件
        $taskService->on(Tasks::EVENT_AFTER_TERMINATE_OR_RESTART, function($event) use($member, $taskService){

            $userService = Yii::$app->get('userService');

            $message = $event->isTerminate ? "终止" : "重新启动";

            //给发布人发送提醒消息
            if($event->task->publisher_id != $member->id){
                $sender = Yii::$app->get('toolService')->getMessageSender([
                        'task' => $event->task,
                        'member' => $userService->getMember($event->task->publisher_id),
                        'message' => "【{$member->real_name}】{$message}了您发布的【{$event->project->name}】【{$event->task->name}】任务",
                        'url' => Constants::PUBLISHED_TASKS."?task_id={$event->task->id}"
                    ]
                );
                if(!$sender->send()){
                    $event->isValid = false;
                    $event->setError($sender->getErrorString());
                    return;
                }
            }

            //给实施人发送消息
            if($event->task->receive_user_id && $event->task->receive_user_id != $member->id){
                $sender = Yii::$app->get('toolService')->getMessageSender([
                        'task' => $event->task,
                        'member' => $userService->getMember($event->task->receive_user_id),
                        'message' => "【{$member->real_name}】{$message}了您的【{$event->project->name}】【{$event->task->name}】任务",
                        'url' => Constants::MY_TASKS."?task_id={$event->task->id}"
                    ]
                );
                if(!$sender->send()){
                    $event->isValid = false;
                    $event->setError($sender->getErrorString());
                    return;
                }
            }

            Tasks::i("{$message}【{$event->project->name}】【{$event->task->name}】任务");

            $message = "【{$member->real_name}】{$message}【{$event->project->name}】【{$event->task->name}】任务";
            
            $event->isValid = $taskService->createTaskLifeCycle($event->task->id, $message, $member->id);

        });
        if(!$taskService->terminateOrRestartTask($task, $isValid)){
            return $taskService->getErrorString();
        }
        return true;
    }

    private function receiveTask($taskService, $member, $task){
        //任务领取进程
        $taskService->on(Tasks::EVENT_AFTER_ALLOCATE_TASK,  function($event) use($taskService, $member){

            //发送通知到发布人
            if($member->id != $event->task->publisher_id){
                $publisher = Yii::$app->get('userService')->getMember($event->task->publisher_id);
                $message = "【{$member->real_name}】接收了您发布的【{$event->project->name}】【{$event->task->name}】任务";
                $sender = Yii::$app->get('toolService')->getMessageSender([
                        'task' => $event->task,
                        'member' => $publisher,
                        'message' => $message,
                        'url' => Constants::PUBLISHED_TASKS."?task_id={$event->task->id}"
                    ]
                );
                if(!$sender->send()){
                    $event->isValid = false;
                    $event->setError($sender->getErrorString());
                    return;
                }
            }

            $message = "【{$member->real_name}】接收了【{$event->project->name}】【{$event->task->name}】任务";
            
            $event->isValid = $taskService->createTaskLifeCycle($event->task->id, $message, $event->task->receive_user_id);
        });
        return $taskService->allocateTask($member, $task);
    }
}
