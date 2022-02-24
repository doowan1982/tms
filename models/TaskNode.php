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

    /**
     * 上一级TaskNode
     * @var TaskNode $parent
     */
    private $parent;

    public $nodes = [];

    public $status;

    public $statusName;

    public $type;

    public $typeName;

    public function __construct($parent, $config = []){
        parent::__construct($config);
        $this->parent = $parent;
    }

    /**
     * 查找对应id的TaskNode
     * @param  integer $id
     * @return TaskNode
     */
    public function find($id){
        foreach($this->nodes as $node){
            if($node->find($id)){
                return $node;
            }
        }
        if($id === $this->id){
            return $this;
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
        $root = new static(null, self::getNodeAttribute($task, $categories));
        $nodes = [$root];
        while(true){
            $node = array_shift($nodes);
            foreach($tasks as $k=>$task){
                $n = $node->find($task->task_id);
                if($n != null){
                    $newNode = new static($n, self::getNodeAttribute($task, $categories));
                    $n->setNode($newNode);
                    $nodes[] = $newNode;
                    unset($tasks[$k]);
                }
            }
            if(count($nodes) === 0){
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