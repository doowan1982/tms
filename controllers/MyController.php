<?php

namespace app\controllers;

use Yii;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use app\models\Position;
use app\records\Task;
use app\records\Message;
use app\services\Tasks;
use app\helpers\Helper;
use app\models\Constants;
class MyController extends BaseController{

    public function actionIndex(){
        return $this->render('index', [
                'progresses' => Yii::$app->get('taskService')->getTaskProgress([
                    'receive_user_id' => $this->getMember()->id,
                ]),
                'recentTouchTasks' =>  Yii::$app->get('taskService')->getRecentTouchTasksByMember($this->getMember()),
            ]);
    }

    public function actionTaskProgress(){
        $progress = Yii::$app->get('projectService')->getTaskProgress([
                'receive_user_id' => $this->getMember()->id,
            ]);

        return $this->success($progress);
    }

    public function actionMessages(){
        $provider = Yii::$app->get('toolService')->getMessages($this->getMember());
        $models = $provider->getModels();
        foreach ($models as $key => $value){
            $models[$key] = $value->toArray();
            if($value->sender_id > 0){
                $models[$key]['sender_real_name'] = $value->sender->real_name;
            }
        }
        $provider->setModels($models);
        return $this->pagination('success', $provider);
    }

    public function actionTasks(){
        $this->setPosition(new Position(['name' => '我的任务']));
        $startTime = $this->get('start_time');
        $endTime = $this->get('end_time');
        $params['timestamp_range'] = Helper::timestampRange($startTime, $endTime);
        $params['project_id'] = $this->get('project_id', null);
        $params['task_id'] = $this->get('task_id', null);
        $params['main_task_id'] = $this->get('main_task_id', null);
        $params['name'] = $this->get('name', null);
        $params['status'] = $this->get('status', null);
        $params['priority'] = $this->get('priority', null);
        $params['receive_user_id'] = $this->get('receive_user_id', null);
        $status = Task::getTaskStatus();
        //无需待领取状态
        foreach($status as $key=>$value){
            if($key == Task::WAITING_STATUS){
                unset($status[$key]);
            }
        }
        $projectService = Yii::$app->get('projectService');
        return $this->render(Constants::MY_TASKS, [
            'status' => $status,
            'tasks' => $projectService->getTasksByMember($this->getMember(), $params),
            'types' => \Yii::$app->get('taskService')->getTaskCategories(),
            'priorities' => Task::getPriorities(),
            'me' => $this->getMember(),
        ]);
    }

    public function actionPublishedTasks(){
        $service = \Yii::$app->get('projectService');
        $this->setPosition(new Position([
                'name' => '我的任务', 
                'jumpUrl' => Constants::MY_TASKS
            ]));
        $this->setPosition(new Position(['name' => '我发布的任务']));
        $startTime = $this->get('start_time');
        $endTime = $this->get('end_time');
        $params['timestamp_range'] = Helper::timestampRange($startTime, $endTime);
        $params['name'] = $this->get('name', null);
        $params['project_id'] = $this->get('project_id', null);
        $params['type'] = $this->get('type', null);
        $params['status'] = $this->get('status', null);
        $params['receive_user_id'] = $this->get('receive_user_id', null);
        $params['username'] = $this->get('username', null);
        $params['task_id'] = $this->get('task_id', null);
        $params['priority'] = $this->get('priority', null);
        return $this->render('/my/publishedTasks', [
                'tasks' => $service->getTasksByPublisher($this->getMember(), $params),
                'status' => Task::getTaskStatus(),
                'types' => \Yii::$app->get('taskService')->getTaskCategories(),
                'priorities' => Task::getPriorities(),
            ]);
    }

    public function actionTaskStat(){
        $member = $this->getMember();
        $startTime = $this->get('start_time');
        $endTime = $this->get('end_time');
        $params = [];
        if($startTime || $endTime){
            $params['timestamp_range'] = Helper::timestampRange($startTime, $endTime);
        }
        $service = Yii::$app->get('projectService');
        return $this->success('success', $service->getTaskStatByMembers([$member], $params));
    }

    public function actionSetTaskBeforeProcess(){
        $id = $this->get('id');
        $service = Yii::$app->get('taskService');
        $task = $service->getTaskById($id);
        if($task == null){
            return $this->error('任务不存在');
        }
        if($task->expected_finish_time < 1){
            $task->expected_finish_time = '';
        }
        return $this->success('success', [
                'expected_finish_time' => $task['expected_finish_time'],
                'difficulty' => $task['difficulty'],
            ]);
    }

    public function actionProcessingTask(){
        $service = Yii::$app->get('taskService');
        $member = $this->getMember();
        //实施通知信息到发布人
        $service->on(Tasks::EVENT_AFTER_PROCESS_TASK, function($event) use ($member, $service){
            //如果发布人为自己，则不再发送通知
            if($member->id != $event->task->publisher_id){
                $sender = Yii::$app->get('toolService')->getMessageSender([
                        'task' => $event->task,
                        'member' => Yii::$app->get('userService')->getMember($event->task->publisher_id),
                        'message' => "项目【{$event->project->name}】任务【{$event->task->name}】已开始实施，实施人【{$member->real_name}】",
                        'url' => Constants::PUBLISHED_TASKS."?task_id={$event->task->id}"
                    ]
                );
                if(!$sender->send()){
                    $event->isValid = false;
                    $event->setError($sender->getErrorString());
                    return;
                }
            }

            //如果项目不在启动状态，则更新
            Yii::$app->get('projectService')->execute($event->project);

            //记录项目进度
            $message = "【{$member->real_name}】将【{$event->project->name}】【{$event->task->name}】任务标记为实施状态";
            
            $event->isValid = $service->createTaskLifeCycle($event->task->id, $message, $event->task->receive_user_id);
        });
        $id = $this->post('id');
        $task = $service->getTaskById($id);
        if($task == null){
            return $this->error("任务不存在");
        }
        $task->expected_finish_time = strtotime($this->post('expected_finish_time'));
        if(!$task->expected_finish_time){
            return $this->error('请设置预期完成时间');
        }
        $task->difficulty = (float)$this->post('difficulty');
        if(!$service->processingTask($member, $task)){
            return $this->error($service->getErrorString());
        }

        Task::i("开始实施【{$task->name}】任务");
        return $this->success('操作成功');
    }

    public function actionTransfer(){
        $taskService = Yii::$app->get('taskService');
        $taskId = $this->get('task_id');
        $task = $taskService->getTaskById($taskId);
        if($task == null){
            return $this->error('任务不存在');
        }
        if(!$taskService->isActived($task)){
            return $this->error('任务不可操作');
        }
        return $this->success('success', Yii::$app->get('userService')->getExceptMembers($this->getMember()));
    }

    public function actionTransferMember(){
        $memberId = $this->post('member_id');
        $userService = Yii::$app->get('userService');
        $member = $userService->getMember($memberId);
        if($member == null){
            return $this->error('成员不存在');
        }
        $taskId = $this->post('task_id');

        $taskService = Yii::$app->get('taskService');
        $task = $taskService->getTaskById($taskId);
        if($task == null){
            return $this->error('任务不存在');
        }
        if(!$taskService->transferMember($task, $member)){
            return $this->error($taskService->getErrorString());
        }
        self::i("转交【{$task->name}[{$task->id}]】任务给【{$member->real_name}】");
        return $this->success('转交成功');
    }

    public function actionCodeFragment(){
        $taskId = $this->get('task_id');
        $taskService = Yii::$app->get('taskService');
        $task = $taskService->getTaskById($taskId);
        if($task ==  null){
            return $this->error('任务不存在');
        }

        $this->setPosition(new Position([
                'name' => '我的任务', 
                'jumpUrl' => Constants::MY_TASKS
            ]));
        $this->setPosition(new Position([
                'name' => "任务完成"
            ]));
        return $this->render('/my/codeFragment', [
                'task' => $task,
            ]);

    }

    public function actionFinish(){
        $taskId = $this->post('task_id');
        //消息发送
        $taskService = Yii::$app->get('taskService');
        $member = $this->getMember();
        $taskService->on(Tasks::EVENT_AFTER_FINISHED_TASK, function($event) use($member){
            //如果发布人为自己，则不再发送通知
            if($member->id != $event->task->publisher_id){
                $sender = Yii::$app->get('toolService')->getMessageSender([
                        'task' => $event->task,
                        'member' => Yii::$app->get('userService')->getMember($event->task->publisher_id),
                        'message' => "项目【{$event->project->name}】任务【{$event->task->name}】已完成，实施人【{$member->real_name}】",
                        'url' => Constants::PUBLISHED_TASKS."?task_id={$event->task->id}"
                    ]
                );
                if(!$sender->send()){
                    $event->isValid = false;
                    $event->setError($sender->getErrorString());
                    return;
                }
            }
            //记录项目进度
            $message = "【{$member->real_name}】将【{$event->project->name}】【{$event->task->name}】任务标记为已完成";
            
            $result = $event->sender->createTaskLifeCycle($event->task->id, $message, $event->task->receive_user_id);
            if(!$result){
                $event->setError($event->sender->getErrorString());
            }
            $event->isValid = $result;
        });

        $task = $taskService->getTaskById($taskId);

        if($task == null){
            return $this->error('任务不存在');
        }
        $task->real_finish_time = strtotime($this->post('real_finish_time', 0));
        //保存代码片段
        $fragment = $this->post('fragment', '');
        if($fragment){
            $taskService->saveTaskCodeFragment($task, $fragment);
        }

        if(!$taskService->finishTask($task)){
            return $this->error($taskService->getErrorString());
        }
        self::i("完成【{$task->name}[{$task->id}]】任务");
        return $this->success('操作成功');
    }

    public function actionInfo(){
        $this->setPosition(new Position([
            'name' => '个人信息',
        ]));
        $service = Yii::$app->get('userService');
        $member = $this->getMember();
        $configs = [];
        foreach($service->getMemberAllConfig($member) as $config){
            $config = $config->toArray();
            $configs[$config['name']] = $config['value'];
        }
        return $this->render('/my/info', [
            'member' => $member,
            'priorities' => Task::getPriorities(),
            'config' => $configs,
        ]);
    }

    public function actionSaveInfo(){
        $data = $this->post();

        $userService = Yii::$app->get('userService');
        $member = $userService->getMember($data['id']);
        if($data['password'] !== ''){
            if(!Yii::$app->user->getIdentity()->validatePassword($data['old_password'])){
                return $this->error('旧密码验证失败');
            }
            $member->password = $userService->generatePasswordHash($data['password']);
            unset($data['old_password']);
        }
        $member->setAttributes($data);
        if(!$userService->save($member)){
            return $this->error($userService->getErrorString());
        }
        self::i('更新个人信息');
        return $this->success('保存成功');
    }

    public function actionRead(){
        $toolService = Yii::$app->get('toolService');
        $messageId = $this->get('message_id');
        $message = $toolService->getMessageById($messageId);
        if($message == null){
            return $this->error('消息不存在');
        }
        if(!$toolService->read($message)){
            return $this->error($toolService->getErrorString());
        }
        return $this->success('已读');
    }

    public function actionGetMessageCount(){
        return $this->success($this->getUnreadMessages());
    }

    public function actionLogs(){
        $startTime = $this->get('start_time');
        $endTime = $this->get('end_time');
        $params['create_time'] = Helper::timestampRange($startTime, $endTime);
        return $this->pagination('success', $this->toolService->getLogsByMember($this->getMember(), $params));
    }

    public  function actionTaskDetail(){
        exit('此功能用于每日的工作内容，基于任务，来生成个人的报告，待完成');
    }
}
