<?php
namespace app\records;

use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
class Task extends \app\base\BaseAR{

    //任务状态，1为待领取， 10为待实施（已领取），20为实施中，30为待测试验收，40为完成，50任务终止
    const WAITING_STATUS = 1;
    const WAITTING_ADVANCE_STATUS = 10;
    const ADVANCE_STATUS = 20;
    // const WAITING_TEST_STATUS = 30;
    const COMPLETE_STATUS = 40;
    const TERMINATION_STATUS = 50;

    //任务状态
    public static function getTaskStatus(){
        return [
            self::WAITING_STATUS => '待领取',
            self::WAITTING_ADVANCE_STATUS => '待实施',
            self::ADVANCE_STATUS => '实施中',
            // self::WAITING_TEST_STATUS => '待测试验收',
            self::COMPLETE_STATUS => '完成',
            self::TERMINATION_STATUS => '终止',
        ];
    }

    //是否作废
    const VALID = 1;
    const IN_VALID = 0;

    //任务优先级
    const HIGH_PRIORITY = 1;
    const MID_PRIORITY = 2;
    const LOW_PRIORITY = 3;

    //子任务建立最大级数，包含当前级
    const MAX_COUNT_FORK_TASK = 10;

    public static function getPriorities(){
        return [
            self::HIGH_PRIORITY => '高',
            self::MID_PRIORITY => '中',
            self::LOW_PRIORITY => '低',
        ];
    }
    //是否删除
    const DELETE = 1;
    const NON_DELETE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%tasks}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
                    self::EVENT_BEFORE_UPDATE => ['update_time'],
                ],
            ],
            [
                'class' => BlameableBehavior::className(),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['publisher_id'],
                ]
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields(){
        $fields = parent::fields();
        $this->update_time = date('Y-m-d H:i', $this->update_time);
        $this->create_time = date('Y-m-d H:i', $this->create_time);
        if($this->expected_finish_time){
            $this->expected_finish_time = date('Y-m-d H:i', $this->expected_finish_time);
        }else{
            $this->expected_finish_time = '未设置';
        }
        if($this->receive_user_id > 0){
            $this->receive_time = date('Y-m-d H:i', $this->receive_time);
        }else{
            $this->receive_time = '未领取';
        }
        $this->publish_time = date('Y-m-d H:i', $this->publish_time);
        if($this->real_finish_time){
            $this->real_finish_time = date('Y-m-d H:i', $this->real_finish_time);
        }else{
            $this->real_finish_time = '未完成';
        }
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'project_id' => '所在项目',
            'task_id' => '所在任务',
            'name' => '任务名称',
            'description' => '任务描述',
            'priority' => '优先级',
            'status' => '状态',
            'difficulty' => '复杂度',
            'type' => '任务类型',
            'is_valid' => '是否有效',
            'publisher_id' => '发布人',
            'publish_time' => '发布时间',
            'receive_user_id' => '接受人',
            'receive_time' => '接受时间',
            'expected_finish_time' => '期望完成时间',
            'real_finish_time' => '实际完成时间',
            'fork_activity_count' => '活跃子任务总数',
            'fork_task_count' => '子任务总数'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['project_id', 'name','description', 'priority', 'publish_time', 'type'], 'required'],
            [['name'], 'string','max'=>80],
            [['description'], 'string'],
            [['project_id', 'task_id', 'priority', 'status', 'type', 'is_valid', 'publisher_id', 'publish_time', 'receive_user_id', 'receive_time', 'fork_activity_count', 'fork_task_count', 'create_time', 'update_time'], 'integer'], 
            [['difficulty', 'expected_finish_time'], 'number'],
            [['status'], 'in', 'range' => array_keys(self::getTaskStatus())],
            ['difficulty', 'default', 'value' => 0],
            [['is_valid'], 'in', 'range' => [
                self::VALID, self::IN_VALID
            ]],
            [['is_delete'], 'in', 'range' => [
                self::DELETE, self::NON_DELETE
            ]],
            [['priority'], 'in', 'range' => array_keys(self::getPriorities())],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert){
        if(!$insert){
            $this->addTaskLog();
        }
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(){
        $this->addTaskLog();
        return BaseAR::beforeDelete($insert);
    }

    //关联的任务详情
    public function getTaskDetails(){
        return $this->hasMany(TaskDetail::className(), ['task_id'=>'id'])->orderBy([
                'start_time' => SORT_ASC
            ]);
    }

    /**
     * 添加任务日志
     * @return void
     */
    public function addTaskLog(){
        $log = new TaskLog();
        $log->id = $this->id;
        $log->setAttributes($this->getOldAttributes());
        $log->insert();
    }

    public function getProject(){
        return $this->hasOne(Project::className(), ['id' => 'project_id']);
    }

    public function getPublisher(){
        return $this->hasOne(Member::className(), ['id' => 'publisher_id']);
    }

    public function getReceiver(){
        return $this->hasOne(Member::className(), ['id' => 'receive_user_id']);
    }

    public function getMainTask(){
        return $this->hasOne(Task::className(), ['id' => 'task_id']);
    }

    public function getCategory(){
        return $this->hasOne(TaskCategory::class, ['id' => 'type']);
    }

    public function getTaskCodeFragment(){
        return $this->hasOne(TaskCodeFragment::class, ['task_id' => 'id']);
    }

}