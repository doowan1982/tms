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
use app\events\TaskEvent;
use app\events\TaskFinishEvent;
use app\events\TaskProcessingEvent;
use app\events\TaskTerminateOrRestartEvent;
use app\models\TaskProgress;
use app\models\Constants;
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
        }

        if(!$this->isActived($task)){
            return $this->setError('任务不可操作');
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
            if(!$task->save()){
                return $this->setError('编辑失败');
            }
            //新增时，则记录主任务
            //注意，此处增加活跃数对应的将在$this->isActived()为false，进行递减
            if($isNewRecord && $mainTask != null && !$this->updateForkActivityCount($mainTask, $task)){
                return $this->setError('主任务活跃数更新失败');
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
     * 更新主任务的子任务活跃数量
     * @author doowan
     * @date   2020-11-21
     * @param  Task       $task 主任务
     * @param  Task       $fork 子任务
     * @return boolean
     */
    public function updateForkActivityCount(Task $task, Task $fork){
        if($this->isActived($fork)){
            $task->fork_activity_count++;
        }else{
            $task->fork_activity_count--;
        }
        return $this->save($task) !== false;
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

        if(!$this->isDeleted($task) || !$this->isActived($task)){
            return $this->setError('任务已开始，无法被删除');
        }

        if($task->fork_activity_count > 0){
            return $this->setError('该任务存在子任务');
        }

        //任务删除同时将状态改为终止
        $task->status = Task::TERMINATION_STATUS;
        $task->is_delete = Task::DELETE;

        $mainTask = null;
        if($task->task_id > 0){
            $mainTask = Task::findOne($task->task_id);
        }
        if($mainTask != null){
            $this->updateForkActivityCount($mainTask, $task);
        }

        return $task->update() !== false;
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
        return TaskLoG::findOne($id);
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

            if($task->task_id > 0){
                $mainTask = Task::findOne($task->task_id);
                if(!$this->updateForkActivityCount($mainTask, $task)){
                    return $this->setError('主任务活跃数更新失败');
                }
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
        $log = TaskLog::find()->where(['id' => $task->id])
                    ->orderBy([
                        'log_id' => SORT_DESC
                    ])
                    ->offset($sequence-1)
                    ->limit($sequence)
                    ->one();
        return $log;
    }

    //日志查询
    private function taskLogs($where){
        $query = TaskLog::find()->alias('t')
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


}