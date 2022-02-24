<?php
namespace app\services;

use \Yii;   
use app\records\Member;
use app\records\Task;
use app\records\TaskLifecycle;
use app\records\TaskDetail;
use app\records\TaskLog;
use app\records\TaskCategory;
use app\records\TaskCodeFragment;
use app\records\Project;
use app\events\TaskEvent;
use app\events\ChangeProjectEvent;
use app\events\TaskFinishEvent;
use app\events\TaskProcessingEvent;
use app\events\TaskTerminateOrRestartEvent;
use app\events\ExchangeEvent;
use app\models\TaskProgress;
use app\models\Constants;
use app\models\TaskStatistics;
use yii\helpers\ArrayHelper;
class Tasks extends \app\base\Service{

    /**
     * 任务保存前置事件
     */
    const EVENT_BEFORE_SAVE_TASK = 'beforeSaveTask';
    
    /**
     * 任务保存后置事件
     */
    const EVENT_AFTER_SAVE_TASK = 'afterSaveTask';

    /**
     * 任务分配前置事件
     */
    const EVENT_BEFORE_ALLOCATE_TASK = 'beforeAllocateTask';

    /**
     * 任务分配后置事件
     */
    const EVENT_AFTER_ALLOCATE_TASK = 'afterAllocateTask';

    /**
     * 任务开始前事件
     */
    const EVENT_BEFORE_PROCESS_TASK = 'beforeProcessTask';

    /**
     * 任务开始后置事件
     */
    const EVENT_AFTER_PROCESS_TASK = 'afterProcessTask';

    /**
     * 任务完成前置事件
     */
    const EVENT_BEFORE_FINISHED_TASK = 'beforeFinishTask';

    /**
     * 任务完成后置事件
     */
    const EVENT_AFTER_FINISHED_TASK = 'afterFinishTask';

    /**
     * 任务终止或者重启前置事件
     */
    const EVENT_BEFORE_TERMINATE_OR_RESTART = 'beforeTerminateOrRestart';

    /**
     * 任务终止或者重启后置事件
     */
    const EVENT_AFTER_TERMINATE_OR_RESTART = 'afterTerminateOrRestart';

    /**
     * 子任务变更事件
     */
    const EVENT_EXCHANGE_TASK = 'exchangeTask';

    /**
     * 任务所在项目变更事件
     */
    const EVENT_CHANGE_PROJECT = 'changeProject';

    /**
     * 新增任务到项目事件
     */
    const EVENT_ADD_TASK_TO_PROJECT = 'addTaskToProject';
    
    /**
     * 返回一条任务记录
     * @param  integer $id
     * @return Task
     */
    public function getTaskById($id){
        return Task::find()->alias('t')
                    ->joinWith('project')
                    ->where([
                        't.id' => $id,
                        't.is_delete' => Task::NON_DELETE
                    ])
                    ->one();
    }

    /**
     * 返回指定条件的项目以及项目细节数据
     * @param  array  $conditions
     * @return array
     */
    public function getTaskWithDetails($conditions = []){
        $query = Task::find();
        if(isset($conditions['receive_user_id'])){
            $this->conditionFilter($query, $conditions, 'receive_user_id');
        }
        if(isset($conditions['timestamp_range'])){
            $this->conditionFilter($query, $conditions, ['timestamp_range', 'receive_time', self::RANGE]);
        }
        if(isset($conditions['create_timestamp_range'])){
            $this->conditionFilter($query, $conditions, ['create_timestamp_range', 't.create_time', self::RANGE]);
        }
        if(isset($conditions['completed_timestamp_range'])){
            $this->conditionFilter($query, $conditions, ['completed_timestamp_range', 't.real_finish_time', self::RANGE]);
        }
        $conditions['is_delete'] = Task::NON_DELETE;
        return $query->alias('t')
                    ->joinWith('taskDetails')
                    ->andFilterWhere($conditions)
                    ->orderBy([
                        'project_id' => SORT_ASC,
                        'name' => SORT_ASC,
                    ])
                    ->all();
    }

    /**
     * 返回一条完整关联数据的任务
     * @param  integer $id
     * @return Task
     */
    public function getTask($id){
        return Task::find()->alias('t')
                    ->joinWith('project')
                    ->joinWith(['receiver r'])
                    ->joinWith(['publisher p'])
                    ->joinWith('category')
                    ->where([
                        't.id' => $id,
                        't.is_delete' => Task::NON_DELETE
                    ])
                    ->one();
    }

    /**
     * 任务分配
     * @param  Member $member
     * @param  integer|Task $taskId
     * @param  boolean $reallocation 是否为重新分配
     * @return boolean
     */
    public function allocateTask(Member $member, Task $task, $reallocation=false){
        if($task == null){
            return $this->setError('任务不存在');
        }
        if(!$reallocation && $task->receiver != null){
            return $this->setError('任务已被领取');
        }
        if(!$this->isActived($task)){
            return $this->setError('任务不可操作');
        }

        $event = new TaskEvent([
            'project' => $task->project,
            'task' => $task,
        ]);
        $transaction = Yii::$app->db->beginTransaction();
        try{
            $this->trigger(self::EVENT_BEFORE_ALLOCATE_TASK, $event);
            if(!$event->isValid){
                return $this->setError($event->getErrorString());
            }
            $task->receive_user_id = $member->id;
            $task->receive_time = time();
            $task->status = Task::WAITTING_ADVANCE_STATUS;
            if($task->update() === false){
                return $this->setError($task->getErrorString());
            }
            if($this->hasEventHandlers(self::EVENT_AFTER_ALLOCATE_TASK)){
                $this->trigger(
                    self::EVENT_AFTER_ALLOCATE_TASK, 
                    $event
                );
            }
            $transaction->commit();
        }catch(\Exception $e){
            $transaction->rollBack();
            return $this->setError($e->getMessage());
        }
        
        return $event->isValid;
    }

    /**
     * 返回任务类别
     * @return array
     */
    public function getTaskCategories(){
        return TaskCategory::find()
                ->orderBy(['show_order' => SORT_DESC])
                ->all();
    }

    /**
     * 保存任务
     * @param  Task   $task
     * @param  Member $member 任务接收人，如果未指定则为null
     * @return boolean
     */
    public function saveTask(Task $task, Member $member = null){
        $project = $task->project;
        if($project == null){
            return $this->setError("无法创建任务，原因：项目不存在【{$task->project_id}】");
        }else if($project->id != $task->project_id){
            $project = Project::findOne($task->project_id);
        }
        if(!$this->isActived($task)){
            return $this->nonActivedTaskSave($task, $project);
        }
        if($task->expected_finish_time > 0 && $task->expected_finish_time < time()){
            return $this->setError('预期完成时间需大于当前时间');
        }
        $isNewRecord = $task->getIsNewRecord();
        if(!$isNewRecord && $task->is_valid == Task::IN_VALID){
            return $this->setError("该任务已终止，不能再修改");
        }

        $mainTask = null;
        if($task->task_id > 0){
            $mainTask = Task::findOne($task->task_id);
            if($mainTask->id === $task->id){
                return $this->setError('主任务设置重名');
            }
        }

        $event = new TaskEvent([
            'project' => $project,
            'task' => $task,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE_TASK, $event);
        if(!$event->isValid){
            return $this->setError($event->getErrorString());
        }
        if(!$task->validate()){
            return $this->setError($task->getErrorString());
        }

        $isAutoAllocate = ($member && $member->id != $task->getOldAttribute('receive_user_id'));

        $transaction = Yii::$app->db->beginTransaction();
        try{
            //记录原始主任务编号
            $oldMainTaskId = $task->getOldAttribute('task_id');
            //主任务更新操作
            if($mainTask != null || $oldMainTaskId > 0){
                //新增时，则记录主任务
                //注意，此处增加活跃数对应的将在$this->isActived()为false，进行递减
                if($isNewRecord){
                    $this->changeForkTaskCount($mainTask);
                    if(!$this->updateForkActivityCount($mainTask, $task)){
                        return $this->setError('主任务活跃数更新失败');
                    }
                }else if(!$isNewRecord && !$this->exchangeTask($task, $this->getTaskById($oldMainTaskId), $mainTask)){ //如果主任务发生改变，则更新原主任务和新主任的活跃数量
                    return false;
                }
            }
            if(!$this->_saveTask($task, $project)){
                return $this->setError('编辑失败');
            }
            //与上一个实施人不同，则自动领取
            if($isAutoAllocate){
                $taskService = $this;
                $this->on(Tasks::EVENT_AFTER_ALLOCATE_TASK,  function($event) use($taskService, $member){

                    if($member == null){
                        return;
                    }

                    $publisher = Yii::$app->get('userService')->getMember($event->task->publisher_id);
                    $message = "【{$publisher->real_name}】发布了【{$event->project->name}】【{$event->task->name}】任务给【{$member->real_name}】";

                    $event->isValid = $taskService->createTaskLifeCycle($event->task->id, $message, $member->id);
                });
                if(!$this->allocateTask($member, $task)){
                    return $this->setError('任务自动分配失败');
                }
            }
            $transaction->commit();
        }catch(\Exception $e){
            $transaction->rollBack();
            return $this->setError($e->getMessage());
        }

        if($this->hasEventHandlers(self::EVENT_AFTER_SAVE_TASK)){
            $this->trigger(
                self::EVENT_AFTER_SAVE_TASK, 
                $event
            );
        }
        return $event->isValid;
    }

    /**
     * $task不再活跃状态时的数据更新
     * 仅支持项目和主任务的变更
     * @param Task   $task
     * @param Project $project
     * @return boolean
     */
    public function nonActivedTaskSave(Task $task, Project $project){
        //不活跃的任务仅可更新所在项目以及主任务
        $mainTask = null;
        if($task->task_id > 0){
            $mainTask = $task->mainTask; //当前的主任务
        }
        $task->setAttributes($task->getOldAttributes());
        $prevTask = $mainTask; //默认为同一任务
        //$mainTask为null则从$prevTask中删除了对应的父级任务
        if($mainTask === null || $mainTask != null && $task->task_id !== $mainTask->id){
            $prevTask = $this->getTaskById($task->task_id);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try{
            //$mainTask为null则从$prevTask中删除了对应的父级任务
            if(($mainTask === null || $mainTask !== $prevTask) && !$this->exchangeTask($task, $prevTask, $mainTask)){
                return false;
            }
            if(!$this->_saveTask($task, $project)){
                return $this->setError('非活跃任务保存失败');
            }
            $transaction->commit();
        }catch(\Exception $e){
            $transaction->rollBack();
            return $this->setError($e->getMessage());
        }
        return true;
    }

    /**
     * 将$task从$from移至$to中
     * $from的活跃子任务数量-1，同时$to的活跃子任务数量+1
     * @param  Task $task 
     * @param  Task $from 移动之前的任务
     * @param  Task $to 移动之后的任务
     * @return boolean
     */
    public function exchangeTask(Task $task, $from, $to){
        //同一任务无需交换
        if($from != null && $to != null && $from->id === $to->id){
            return true;
        }

        $task->task_id = ($to === null ? 0 : $to->id); //如果为null，则不存在上级任务，设置为0
        list($forkTaskId, $level) = $this->getAllForkTaskId($task);
        if($level >= Task::MAX_COUNT_FORK_TASK){
            return $this->setError('层级嵌套超出['.Task::MAX_COUNT_FORK_TASK.']，无法设置子任务。');
        }
        //检查$task所有子集以确保$to不为环形依赖
        //比如：$task->a->b->c，然后c->$task
        if(in_array($task->task_id, $forkTaskId)){
            return $this->setError('环形依赖，无法设置子任务');
        }
        $transaction = Yii::$app->db->beginTransaction();
        try{
            if($to != null){
                $this->changeForkTaskCount($to);
                if($this->isActived($task)){
                    $to->fork_activity_count++;                      
                }
                if($to->update() === false){
                    return $this->setError('任务更换失败');
                }  
            }
            if($from != null){
                $this->changeForkTaskCount($from, false);
                if($this->isActived($task)){
                    $from->fork_activity_count--;
                }
                if($from->update() === false){
                    return $this->setError('任务更换失败');
                }
            }
            $this->trigger(self::EVENT_EXCHANGE_TASK, new ExchangeEvent([
                    'task' => $task,
                    'from' => $from,
                    'to' => $to
                ]));
            $transaction->commit(); 
        }catch(\Exception $e){
            $transaction->rollBack();
            return $this->setError($e->getMessage());
        }
        return true;
    }

    /**
     * 实施任务
     * @param  Member $member 实施人
     * @param  Task   $task
     * @return boolean
     */
    public function processingTask(Member $member, Task $task){
        //创建一个任务详情
        $taskDetail = new TaskDetail([
            'task_id' => $task->id,
            'start_time' => time(),
            'end_time' => 0, //未指定完成时间
        ]);
        if(!$taskDetail->validate()){
            return $this->setError($taskDetail->getErrorString());
        }

        $transaction = Yii::$app->db->beginTransaction();
        try{
            $event = new TaskProcessingEvent([
                'project' => $task->project,
                'task' => $task,
                'taskDetail' => $taskDetail,
            ]);

            if($this->hasEventHandlers(self::EVENT_BEFORE_PROCESS_TASK)){
                $this->trigger(self::EVENT_BEFORE_PROCESS_TASK, $event);
            }
            if(!$event->isValid){
                return $this->setError($event->getErrorString());
            }

            $task->status = Task::ADVANCE_STATUS;
            if($task->update() === false || $taskDetail->insert() === false){
                return false;
            }

            if($this->hasEventHandlers(self::EVENT_AFTER_PROCESS_TASK)){
                $this->trigger(self::EVENT_AFTER_PROCESS_TASK, $event);
            }
            if(!$event->isValid){
                return $this->setError($event->getErrorString());
            }

            $transaction->commit();
        }catch(\Exception $exception){
            $transaction->rollBack();
            return $this->setError($exception->getMessage());
        }
        return true;
    }

    /**
     * $member删除指定编号的日志信息
     * @param  Membuer $member
     * @param  Task  $task
     * @return boolean
     */
    public function deleteTask(Member $member, Task $task){
        if($task->publisher_id != $member->id){
            return $this->setError('仅能对自己创建的任务做删除操作');
        }

        if($task->fork_activity_count > 0){
            return $this->setError('该任务存在子任务');
        }

        $task->is_delete = Task::DELETE;

        $mainTask = null;
        if($task->task_id > 0){
            $mainTask = Task::findOne($task->task_id);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try{
            if($mainTask != null){
                $this->changeForkTaskCount($mainTask, false);
                //不为终止状态时可更新活跃任务数
                if($task->status != Task::TERMINATION_STATUS){
                    if(!$this->updateForkActivityCount($mainTask, $task)){
                        return $this->setError('主任务活跃数更新失败');
                    }
                }else if(!$mainTask->save()){ //否则仅更新任务数
                    return $this->setError('主任务更新任务数失败');
                }
            }
            if(!$task->update()){
                return $this->setError('删除失败');
            };            
            $transaction->commit();
        }catch(\Exception $exception){
            $transaction->rollBack();
            return $this->setError($exception->getMessage());
        }
        return true;
    }

    /**
     * 终止或重启任务
     * @param  Task    $task
     * @param  integer $isValid 如果为1则为终止任务
     * @return boolean
     */
    public function terminateOrRestartTask(Task $task, $isValid){
        if((int)$isValid === Task::IN_VALID && !$this->isActived($task)){
            return $this->setError('任务不在活跃状态');
        }

        if($task->is_valid === $isValid){
            return $this->setError('任务有效状态冲突');
        }

        if($task->fork_activity_count > 0){
            return $this->setError('该任务存在活跃子任务');
        }

        $event = new TaskTerminateOrRestartEvent([
            'task' => $task,
            'project' => $task->project,
            'isTerminate' => $isValid === Task::IN_VALID,
        ]);
        if($this->hasEventHandlers(self::EVENT_BEFORE_TERMINATE_OR_RESTART)){
            $this->trigger(self::EVENT_BEFORE_TERMINATE_OR_RESTART, $event);
        }
        if(!$event->isValid){
            return $this->setError($event->getErrorString());
        }
        $task->is_valid = $isValid;
        if($isValid){
            //有效任务，则恢复上一次设置为无效时的状态
            $log = $this->getSequenceLogByTask($task);
            //无更新记录，则指定为待领取状态
            if($log == null){
                $task->status = Task::WAITING_STATUS;
            }else{
                $task->status = $log->status;
            }
        }else{
            $task->status = Task::TERMINATION_STATUS;
        }
        $mainTask = $task->mainTask;
        if($mainTask != null && !$this->updateForkActivityCount($mainTask, $task)){
            return $this->setError('主任务活跃数更新失败');
        }
        if(!$task->save()){
            return $this->setError($task->getErrorString());
        }
        if($this->hasEventHandlers(self::EVENT_AFTER_TERMINATE_OR_RESTART)){
            $this->trigger(self::EVENT_AFTER_TERMINATE_OR_RESTART, $event);
        }
        if(!$event->isValid){
            return $this->setError($event->getErrorString());
        }
        return true;
    }

    /**
     * 任务进度
     * @author doowan
     * @date   2020-05-22
     * @param  array      $condition
     * @return array
     */
    public function getTaskProgress($condition=[]){
        $condition['t.status'] = Task::ADVANCE_STATUS;
        $tasks = Task::find()->alias('t')
                    ->joinWith('project')
                    ->joinWith('taskDetails')
                    ->andWhere($condition)
                    ->orderBy([
                        'receive_time' => SORT_DESC
                    ])
                    ->all();
        $progresses = [];
        $now = time();
        foreach($tasks as $task){
            $progresses[] = new TaskProgress([
                    'task' => $task,
                    'now' => $now,
                ]);
        }
        return $progresses;
    }

    /**
     * 任务是否在活跃状态，除完成（status=40）、终止（status=50）状态均属于活跃状态
     * @author doowan
     * @date   2020-11-10
     * @param  Task $task
     * @return boolean
     */
    public function isActived($task){
        return !in_array($task->status, [
            Task::COMPLETE_STATUS, Task::TERMINATION_STATUS
        ]);
    }

    /**
     * 任务是否可删除
     * @param  Task  $task
     * @return boolean
     */
    public function isDeleted($task){
        return !in_array($task->status, [
            Task::ADVANCE_STATUS,
        ]);
    }

    /**
     * 任务是否已领取
     * @return boolean
     */
    public function isRecevied($task){
        return in_array($task->status, [
            Task::ADVANCE_STATUS,
            Task::WAITTING_ADVANCE_STATUS,
        ]);
    }

    /**
     * 是否运行创建子任务
     * @return boolean
     */
    public function isAllowCreateTask($task){
        return in_array($task->status, [
            Task::COMPLETE_STATUS,
        ]);
    }

    /**
     * 新增一个任务进度信息
     * @param  integer $taskId
     * @param  string $message
     * @param  integer $memberId
     * @return boolean
     */
    public function createTaskLifeCycle($taskId, $message, $memberId){
        $lifecycle = new TaskLifecycle();
        $lifecycle->message = $message;
        $lifecycle->task_id = $taskId;
        $lifecycle->member_id = $memberId;
        return $this->save($lifecycle) !== false;
    }

    /**
     * 返回指定任务的周期进度
     * @param  Task   $task
     * @return array
     */
    public function getLifecycleByTask(Task $task){
        return TaskLifecycle::find()->joinWith('member')
                                ->where(['task_id' => $task->id])
                                ->orderBy(['create_time' => SORT_DESC])
                                ->all();
    }

    /**
     * 返回指定任务的变更记录
     * @param  int $taskId
     * @param  array $conditions
     * @return ActiveQuery
     */
    public  function getChangeLogByTaskId($taskId, $conditions=[]){
        $conditions['task_id'] = $taskId;
        return $this->taskLogs($conditions);
    }

    /**
     * 返回一条记录
     * @param  integer $id
     * @return TaskLog
     */
    public function getChangeLogById($id){
        return TaskLog::findOne($id);
    }

    /**
     * 转交$task给指定的$member
     * @param  Task   $task
     * @param  Member $member
     * @return boolean
     */
    public function transferMember(Task $task, Member $member){
        if(!$this->isRecevied($task)){
            return $this->setError('任务未领取');
        }
        $currentMember = Yii::$app->get('userService')->getMember($task->receive_user_id);
        $this->on(Tasks::EVENT_AFTER_ALLOCATE_TASK, function($event) use ($member, $currentMember){
            $sender = Yii::$app->get('toolService')->getMessageSender([
                    'task' => $event->task,
                    'member' => $member,
                    'message' => "【{$currentMember->real_name}】给您转交项目【{$event->project->name}】任务【{$event->task->name}】",
                    'url' => Constants::MY_TASKS."?task_id={$event->task->id}"
                ]
            );
            if(!$sender->send()){
                $event->isValid = false;
                $event->setError($sender->getErrorString());
                return;
            }

            //记录项目进度
            $message = "【{$currentMember->real_name}】将项目【{$event->project->name}】任务【{$event->task->name}】转交给【{$member->real_name}】";
            
            $event->isValid = $event->sender->createTaskLifeCycle($event->task->id, $message, $event->task->receive_user_id);

        });

        return $this->allocateTask($member, $task, true);
    }

    /**
     * 完成任务
     * @param  Task  $task
     * @return boolean
     */
    public function finishTask(Task $task){
        if(!$this->isActived($task) || $task->status != Task::ADVANCE_STATUS){
            return $this->setError('任务不可操作');
        }
        if(!$task->real_finish_time){
            return $this->setError('请设置有效的完成时间');
        }
        $transaction = Yii::$app->db->beginTransaction();
        try{
            $event = new TaskFinishEvent([
                'project' => $task->project,
                'task' => $task,
            ]);

            if($this->hasEventHandlers(self::EVENT_BEFORE_FINISHED_TASK)){
                $this->trigger(self::EVENT_BEFORE_FINISHED_TASK, $event);
            }
            if(!$event->isValid){
                return $this->setError($event->getErrorString());
            }

            $task->status = Task::COMPLETE_STATUS;
            if($task->update() === false){
                return $this->setError($task->getErrorString());
            }

            $mainTask = $task->mainTask;
            if($mainTask != null && !$this->updateForkActivityCount($mainTask, $task)){
                return $this->setError('主任务活跃数更新失败');
            }

            if($this->hasEventHandlers(self::EVENT_AFTER_FINISHED_TASK)){
                $this->trigger(self::EVENT_AFTER_FINISHED_TASK, $event);
            }

            if(!$event->isValid){
                return $this->setError($event->getErrorString());
            }

            $transaction->commit();
        }catch(\Exception $exception){
            $transaction->rollBack();
            return $this->setError($exception->getMessage());
        }
        return true;
    }

    /**
     * 返回指定项目的任务统计数据
     * @param  Project $project
     * @param  Task $task
     * @param  array $params 查询参数
     * @return array
     */
    public function statistics(Project $project, $params=[], $ignoreProject=false){
        $query = Task::find()->alias('t')
                        ->joinWith('receiver')
                        ->andFilterWhere([
                            'project_id' => $ignoreProject ? null : $project->id,
                            'is_delete' => Task::NON_DELETE,
                        ]);
        $this->conditionFilter($query, $params, ['task_id', 't.task_id']);
        $this->conditionFilter($query, $params, ['id', 't.id']);
        $this->conditionFilter($query, $params, ['timestamp_range', 't.create_time', self::RANGE]);
        $this->conditionFilter($query, $params, ['receive_user_id', 't.receive_user_id']);

        $tasks = $query->orderBy(['receive_user_id' => SORT_ASC, 't.status' => SORT_ASC])
            ->all();
        return [
            'list' => TaskStatistics::stat($tasks),
            'undo_count' => TaskStatistics::$undoTasksCount,
        ];
    }

    /**
     * 保存任务的代码段
     * @param  Task   $task
     * @param  string $fragment
     * @return boolean
     */
    public function saveTaskCodeFragment(Task $task, $codeFragment){
        $taskCodeFragment = new TaskCodeFragment();
        $taskCodeFragment->task_id = $task->id;
        $taskCodeFragment->code_fragment = $codeFragment;
        return $taskCodeFragment->save() !== false;
    }

    /**
     * 返回指定$task的执行$sequence的日志信息，
     * @param  Task    $task
     * @param  integer $sequence 该值为反向取值，1为最新一个日志记录
     * @return TaskLog 如果不存在则返回null
     */
    public function getSequenceLogByTask(Task $task, $sequence = 1){
        $log = TaskLog::find()->where([
                        'id' => $task->id,
                        //此处仅有效状态数据
                        'status' => [
                            Task::WAITING_STATUS,
                            Task::WAITTING_ADVANCE_STATUS,
                            Task::ADVANCE_STATUS,
                        ]
                    ])
                    ->orderBy([
                        'log_id' => SORT_DESC
                    ])
                    ->offset($sequence-1)
                    ->limit($sequence)
                    ->one();
        return $log;
    }

    /**
     * 返回$task所有的子任务id
     * @param  Task   $task
     * @return array 第一个值为id数组，第二个值为具有的层级
     */
    public function getAllForkTaskId(Task $task){
        $limit = Task::MAX_COUNT_FORK_TASK;
        $id = $task->id;
        $idArray = [];
        //子任务建立当前仅限制为10级，大于1则不包含$task级
        while(true){
            $id = Task::find()->select('id')
                        ->where([
                            'task_id' => $id,
                            'is_delete' => Task::NON_DELETE
                        ])
                        ->asArray()
                        ->all();
            $id = ArrayHelper::getColumn($id, 'id');
            if(empty($id) || --$limit <= 1){
                break;
            }
            $idArray = array_merge($idArray, $id);
        }
        return [$idArray, Task::MAX_COUNT_FORK_TASK - $limit];
    }

    /**
     * 返回多层级所有任务的数组
     * @param  Task   $task
     * @return array
     */
    public function getAllForkTasks(Task $task){
        list($id, $level) = $this->getAllForkTaskId($task);
        return Task::find()->alias('t')
                            ->joinWith('project')
                            ->where([
                                't.is_delete' => Task::NON_DELETE,
                                't.id' => $id,
                            ])
                            ->all();
    }

    /**
     * 返回指定Task的子任务数组
     * @return array Task
     */
    public function getTasks(Task $task){
        return $this->_getTasks($task->project_id, [$task->id]);
    }

    /**
     * 返回指定用户最近$num条操作的任务数据
     * @param Member $member
     * @param int $num
     * @return array TaskLifecycle
     */
    public function getRecentTouchTasksByMember(Member $member, $num=20){
        $query = TaskLifecycle::find()
                        ->select('max(id) as id')
                        ->where(['member_id' => $member->id])
                        ->groupBy('task_id')
                        ->orderBy(['id' => SORT_DESC]);
        return TaskLifecycle::find()->alias('tl')
                    ->joinWith('task')
                    ->limit($num)
                    ->where(['tl.id' => $query])
                    ->orderBy([
                        'tl.create_time' => SORT_DESC,
                        'tl.id' => SORT_DESC
                    ])
                    ->all();
    }

    private function _getTasks($projectId, array $id){
        $tasks = Task::findAll([
                'task_id' => $id,
                'project_id' => $projectId,
                'is_delete' => Task::NON_DELETE
            ]);
        $result = $tasks;
        $id = [];
        foreach($tasks as $task){
            $id[] = $task->id;
        }
        if(count($id) === 0){
            return $result;
        }
        return array_merge($result, $this->_getTasks($projectId, $id));
    }

    //日志查询
    private function taskLogs($where){
        $query = TaskLog::find()->alias('t')
                    ->joinWith(['project pro'])
                    ->joinWith(['publisher p'])
                    ->joinWith(['mainTask m'])
                    ->joinWith(['receiver r'])
                    ->joinWith('category')
                    ->orderBy([
                        'log_time' => SORT_DESC,
                    ]);
        $this->conditionFilter($query, $where, ['main_task_id', 't.task_id']);
        $this->conditionFilter($query, $where, ['task_id', 't.id']);
        $this->conditionFilter($query, $where, ['status', 't.status']);
        $this->conditionFilter($query, $where, ['name', 't.name', self::FUZZY]);
        $this->conditionFilter($query, $where, ['username', 'r.username']);
        $this->conditionFilter($query, $where, ['log_time_range', 't.log_time', self::RANGE]);
        $this->conditionFilter($query, $where, ['type', 't.type']);
        $where['t.is_delete'] = Task::NON_DELETE;
        return $query->andFilterWhere($where);
    }

    /**
     * 返回修改后的任务数量
     * @param  Task    $task
     * @return integer
     */
    private function changeForkTaskCount(Task $task, $add = true){
        return $add ? $task->fork_task_count++ : $task->fork_task_count--;
    }

    /**
     * 更新主任务的子任务活跃数量
     * @author doowan
     * @date   2020-11-21
     * @param  Task       $task 主任务
     * @param  Task       $fork 子任务
     * @return boolean
     */
    private function updateForkActivityCount(Task $task, Task $fork){
        if($this->isActived($fork)){
            $task->fork_activity_count++;
        }else{
            $task->fork_activity_count--;
        }
        return $this->save($task) !== false;
    }

    /**
     * 保存任务
     * @param Task $task
     * @param Project $project
     * @return boolean
     */
    private function _saveTask(Task $task, Project $project){
        //任务变更
        if($project->id != $task->project->id){
            $this->trigger(self::EVENT_CHANGE_PROJECT, new ChangeProjectEvent([
                    'task' => $task,
                    'project' => $project,
                    'source' => $task->project,
                ]));
        }else if($task->getIsNewRecord()){
            $this->trigger(self::EVENT_ADD_TASK_TO_PROJECT, new TaskEvent([
                    'task' => $task,
                    'project' => $project,
                ]));
        }
        $task->project_id = $project->id;
        return $task->save() !== false;
    }


}