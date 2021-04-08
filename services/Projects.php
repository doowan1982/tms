<?php
namespace app\services;

use app\base\AbstractMember;
use app\records\Project;
use app\records\Task;
use app\records\Member;
use app\events\ProjectEvent;
use app\events\SearchEvent;
use app\models\UploadForm;
use app\models\TaskStatistics;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
class Projects extends \app\base\Service{

    const EVENT_BEFORE_SAVE_PROJECT = 'beforeSaveProject';
    const EVENT_AFTER_SAVE_PROJECT = 'afterSaveProject';

    /**
     * 查询前置事件
     */
    const EVENT_BEFORE_SEARCH = 'beforeSearch';

    private $parameters = [];

    /**
     * @inheritdoc
     */
    public function init(){
        $this->parameters = \Yii::$app->request->getBodyParams();
    }

    //根据编号查询项目
    public function findById($id){
        return Project::findOne($id);
    }

    /**
     * 返回项目的列表
     * @param  array $conditions
     * @return array
     */
    public function getProjects($conditions){
        $conditions['is_delete'] = Project::NON_DELETE;
        return Project::find()->andFilterWhere($conditions)->all();
    }

    /**
     * 返回$taskId可以设置的主任务列表
     * @param  Project $project
     * @param  Task $task 
     * @return [type]         [description]
     */
    public function getMainTaskCandidates(Project $project, Task $task, $name=''){
        $tasks = \Yii::$app->get('taskService')->getTasks($task);
        $query = Task::find()
                    ->alias('t')
                    ->joinWith(['publisher p'])
                    ->joinWith(['receiver r'])
                    ->where([
                        'project_id' => $project->id,
                    ]);
        if(count($tasks) > 0){
            $query->andWhere(['not in', 't.id', ArrayHelper::getColumn($tasks, 'id')]);
        }
        if($name){
            $query->andFilterWhere(['like', 't.name', $name]);
        }
        return $query->all();
    }

    /**
     * 项目列表数据
     * @param  array $conditions
     * @param  int $pageSize
     * @return DataProvider
     */
    public function projects($conditions, $pageSize){
        $query = Project::find();
        $this->conditionFilter($query, $conditions, ['timestamp_range', 'create_time', self::RANGE]);
        $this->conditionFilter($query, $conditions, ['name', 'name', self::FUZZY]);
        $conditions['is_delete'] = Project::NON_DELETE;
        $query->andFilterWhere($conditions)
                ->orderBy([
                    'create_time' => SORT_DESC
                ]);
        if($this->hasEventHandlers(self::EVENT_BEFORE_SEARCH)){
            $this->trigger(
                self::EVENT_BEFORE_SEARCH, 
                new SearchEvent([
                    'query' => $query,
                ])
            );
        }
        return $this->getDataProvider($query, $pageSize);
    }

    /**
     * 返回指定用户的项目信息
     * @param AbstractMember $member
     * @return \yii\data\DataProviderInterface
     */
    public function getProjectsByMember(AbstractMember $member){
        $condition = [
            'is_delete' => Project::NON_DELETE,
        ];
        $query = Project::find()->joinWith(
                [
                    'tasks' => function($query) use ($member){
                        $query->where(['receive_user_id' => $member->id]);
                    }
                ]
            )
            ->orderBy(['update_time' => SORT_DESC]);
        if(isset($this->parameters['name']) && empty($this->parameters['name'])){
            $query->andWhere(['like', 'name', $this->parameters['name']]);
        }
        return $this->getDataProvider($query, 20);
    }

    /**
     * 启动项目
     * @return boolean 如果成功返回true
     */
    public function execute(Project $project){
        if($project->status !== Project::DEVELOPING_STATUS){
            $project->status = Project::DEVELOPING_STATUS;
            return $project->update() !== false;
        }
        return true;
    }

    /**
     * 返回指定发布人的任务数据
     * @param  Member $publisher
     * @param  array $conditions
     * @return \yii\data\DataProviderInterface
     */
    public function getTasksByPublisher(Member $publisher, $conditions){
        $conditions['t.publisher_id'] = $publisher->id;
        $conditions['t.is_valid'] = Task::VALID;
        return $this->tasks($conditions);
    }

    /**
     * 返回指定用户的任务信息
     * @param AbstractMember $member
     * @return \yii\data\DataProviderInterface
     */
    public function getTasksByMember(Member $member, $conditions=[]){
        $conditions['t.receive_user_id'] = $member->id;
        $conditions['t.is_valid'] = Task::VALID;
        return $this->tasks($conditions);
    }

    /**
     * 返回指定项目的任务信息
     * @param AbstractMember $member
     * @return \yii\data\DataProviderInterface
     */
    public function getTasksByProject(Project $project, $conditions=[]){
        $conditions['t.project_id'] = $project->id;
        return $this->tasks($conditions);
    }

    /**
     * 返回未领取的任务列表
     * @param  array  $conditions
     * @return array
     */
    public function getPendingTasks($conditions = []){
        $conditions['t.status'] = Task::WAITING_STATUS;
        $conditions['t.is_valid'] = Task::VALID;
        return $this->tasks($conditions);
    }

    /**
     * 删除项目
     * @param  Porject $project
     * @return boolean
     */
    public function deleteProject(Project $project){
        if(!$project){
            return $this->setError('数据不存在');
        }
        $task = Task::findOne(['project_id' => $project->id]);
        if($task){
            return $this->setError('项目下存在任务');
        }
        $project->is_delete = Project::DELETE;
        return $project->update() !== false;
    }

    /**
     * 保存项目数据
     * @param  Project $porject
     * @return boolean
     */
    public function saveProject(Project $project){
        $event = new ProjectEvent();
        $this->trigger(self::EVENT_BEFORE_SAVE_PROJECT, $event);
        if(!$event->isValid){
            return $this->setError($event->getErrorString());
        }

        $model = UploadForm::upload(
            'project_doc_attachement', 
            '', 
            date('y/m/d'), 
            \Yii::getAlias('@webroot').\Yii::$app->params['uploadDir']
        );
        if($model->hasErrors()){
            return $this->setError($model->getErrorString());
        }
        $fileName = $model->getFileName();
        if($fileName){
            $project->project_doc_attachement = $fileName;
        }

        if(!$project->validate() || !$project->save()){
            return $this->setError($project->getErrorString());
        }

        if($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PROJECT)){
            $this->trigger(
                self::EVENT_AFTER_SAVE_PROJECT, 
                $event
            );
        }
        if(!$event->isValid){
            return $this->setError($event->getErrorString());
        }
        return true;
    }

    /**
     * 返回指定项目的任务统计数据
     * @param  Project $project
     * @param  array $params 查询参数
     * @return array
     */
    public function statistics(Project $project, $params=[]){
        $query = Task::find()->alias('t')
                        ->joinWith('receiver')
                        ->where([
                            'project_id' => $project->id,
                            'is_delete' => Task::NON_DELETE,
                        ]);

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
     * 返回该用户所参与的项目
     * @param  Member $member
     * @return array
     */
    public function getMemberJoinProjects(Member $member){
        $taskQuery = Task::find()->select('project_id')
                            ->andWhere([
                                'or', 
                                ['publisher_id' => $member->id],
                                ['receive_user_id' => $member->id],
                            ])
                            ->groupBy('project_id');

        return Project::find()->where([
                'id' => $taskQuery
            ])->all();
    }

    /**
     * 返回指定用户的任务统计
     * @param  array $member 多个成员
     * @param  array $conditions
     * @return array
     */
    public function getTaskStatByMembers($members, $conditions = []){
        $that = $this;
        if(!empty($members)){
            $conditions['receive_user_id'] = ArrayHelper::getColumn($members, 'id');
        }

        //查询任务
        $tasks = \Yii::$app->get('taskService')->getTaskWithDetails($conditions);
        $data = [];
        foreach($tasks as $task){
            if(!isset($data[$task->project_id])){
                $data[$task->project_id] = [];
            }
            $task->description = ''; //详情数据不返回
            $data[$task->project_id][] = $task;
        }

        //查询出对应的项目
        $projectId = array_keys($data);
        if(count($projectId) == 0){
            return [];
        }

        $result = [];
        $projects = $this->getProjects(['id' => $projectId]);
        foreach($projects as $project){
            $result[] = [
                'id' => $project->id,
                'name' => $project->name,
                'tasks' => $data[$project->id],
            ];
            unset($data[$project->id]);
        }
        return $result;
    }

    private function tasks($where){
        $query = Task::find()->alias('t')
                    ->joinWith('project')
                    ->joinWith(['publisher p'])
                    ->joinWith(['mainTask m'])
                    ->joinWith(['receiver r'])
                    ->joinWith('category')
                    ->orderBy([
                        'status' => SORT_ASC,
                        'priority' => SORT_ASC,
                        'update_time'=>SORT_DESC
                    ]);
        $this->conditionFilter($query, $where, ['priority', 't.priority']);
        $this->conditionFilter($query, $where, ['main_task_id', 't.task_id']);
        $this->conditionFilter($query, $where, ['task_id', 't.id']);
        $this->conditionFilter($query, $where, ['status', 't.status']);
        $this->conditionFilter($query, $where, ['name', 't.name', self::FUZZY]);
        $this->conditionFilter($query, $where, ['project_id', 't.project_id']);
        $this->conditionFilter($query, $where, ['receive_user_id', 't.receive_user_id']);
        $this->conditionFilter($query, $where, ['publisher_id', 't.publisher_id']);
        $this->conditionFilter($query, $where, ['username', 'r.username']);
        $this->conditionFilter($query, $where, ['timestamp_range', 't.receive_time', self::RANGE]);
        $this->conditionFilter($query, $where, ['publish_timestamp_range', 't.publish_time', self::RANGE]);
        $this->conditionFilter($query, $where, ['type', 't.type']);
        $where['t.is_delete'] = Task::NON_DELETE;
        $query->andFilterWhere($where);
        return $this->getDataProvider($query, 30);
    }

}