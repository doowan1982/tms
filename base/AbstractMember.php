<?php
namespace app\base;

use app\records\Project;
use app\models\Table;
abstract class AbstractMember extends \yii\base\Component{

    public $id;

    public $username;

    public $realName;

    public $password;

    public $roleName;

    public $email;

    public $phone;

    public $isLeave;

    /**
     * 根据用户返回一个AbstractUser
     * @param string $username，支持手机号码，邮箱，用户名
     * @return \base\AbstractUser
     */
    public static function findMember($username){
        return null;
    }

    /**
     * 返回当前人参与的项目
     * @return \yii\data\DataProviderInterface
     */
    public function getProjects(){
        $dataProvider = \Yii::$app->get('projectService')->getProjectsByMember($this);
        $table = new Table([
            'pagination' => $dataProvider->getPagination(),
            'columns' => ['编号' => ['width' => '10%'], '名称' => ['width' => '*']],
            'rows' => [
                [1, '测试'],
                [2, '测试1'],
                [13, '测试13'],
            ]
        ]);
        return $table;
    }

    /**
     * 返回当前人参与的任务
     * @return app\models\Table
     */
    public function getTasks(){
        $dataProvider = \Yii::$app->get('projectService')->getTasksByMember($this);
        $columns = [
            '编号' => ['width'=>'30px'],
            '所在项目' => ['width'=>'30px'],
            '任务名称' => ['width'=>'30px'],
            '所在主任务' => ['width'=>'30px'],
            '优先级' => ['width'=>'30px'],
            '状态' => ['width'=>'30px'],
            '复杂度' => ['width'=>'30px'],
            '任务类型' => ['width'=>'30px'],
            '是否有效' => ['width'=>'30px'],
            '发布人' => ['width'=>'30px'],
            '发布时间' => ['width'=>'30px'],
            '接受时间' => ['width'=>'30px'],
            '期望完成时间' => ['width'=>'30px'],
        ];
        $rows = [];
        foreach($dataProvider->getModels() as $model){
            $rows[] = [
                $model->project->name,
                $model->name,
                $model->task_id > 0 ? $model->mainTask->name : '无',
                $model->priority,
                Task::$taskStatus[$model->status],
                $model->difficulty,
                Task::$taskType[$model->type],
                $model->is_invalid ? '有效' : '无效',
                $model->publisher->real_name,
                $model->publisher_time,
                $model->receive_time,
                $model->expected_finish_time,
            ];
        }
        return new Table([
            'pagination' => $dataProvider->getPagination(),
            'columns' => $columns,
            'rows' => $rows
        ]);
    }

    /**
     * 根据编号返回一个AbstractUser
     * @param integer $id
     * @return \base\AbstractUser
     */
    public static function findMemberById($id){
        return null;
    }

    /**
     * 加入指定项目
     * @param integer $id
     * @return boolean
     */
    public function joinProject(Project $project){

    }

    /**
     * 获取任务
     * @param integer $id
     * @return boolean
     */
    public function obtainTask(Task $task){

    }

}