<?php
namespace app\models;
use app\helpers\Helper;
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

}