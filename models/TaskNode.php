<?php
namespace app\models;
use app\helpers\Helper;
use app\records\Task;
class TaskNode extends \app\base\BaseModel{

    /**
     * 任务主键id
     * @var integer
     */
    public $id;

    /**
     * 任务名称
     * @var string
     */
    public $name;

    /**
     * 项目编号
     * @var integer
     */
    public $projectId;

    /**
     * 项目名称
     * @var string
     */
    public $projectName;

    public $nodes = [];

    public $status;

    public $statusName;

    public $type;

    public $typeName;

    /**
     * 查找对应id的TaskNode
     * @param  integer $id
     * @return TaskNode
     */
    public function find($id){
        if($id === $this->id){
            return $this;
        }
        foreach($this->nodes as $node){
            $node = $node->find($id);
            if(is_object($node)){
                return $node;
            }
        }
        return null;
    }

    public function setNode(TaskNode $node){
        $this->nodes[] = $node;
    }

    public function getType(){
        return $this->type;
    }

    public function setType($type){
        $this->type = $type;
    }

    public function getStatus(){
        return $this->status;
    }

    public function setStatus($status){
        $this->status = $status;
    }

    /**
     * 根据$task创建一个TaskNode
     * @param Task $task
     * @return TaskNode
     */
    public static function createTaskNodeByTask(Task $task){
        $service = \Yii::$app->get('taskService');
        $tasks = $service->getAllForkTasks($task);
        //按树形生成任务数据
        $categories = $service->getTaskCategories();
        $root = new static(self::getNodeAttribute($task, $categories));
        while(true){
            foreach($tasks as $k=>$task){
                $n = $root->find($task->task_id);
                if($n != null){
                    $newNode = new static(self::getNodeAttribute($task, $categories));
                    $n->setNode($newNode);
                    unset($tasks[$k]);
                }
            }
            if(count($tasks) === 0){
                break;
            }
        }
        return $root;
    }

    //返回$task的参数属性
    private static function getNodeAttribute(Task $task, $categories){
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