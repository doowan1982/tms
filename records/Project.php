<?php
namespace app\records;

use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
class Project extends \app\base\BaseAR{
    
    //1：待开发，2：开发中，3：完成开发，4开发终止，5，维护中
    const WAITING_STATUS = 1;
    const DEVELOPING_STATUS = 2;
    const COMPLETE_STATUS = 3;
    const TERMINATE_STATUS = 4;
    const MAINTENANCE_STATUS = 5;

    //状态信息
    public static function getStatusMap(){
        return [
            self::WAITING_STATUS => '待开发',
            self::DEVELOPING_STATUS => '开发中',
            self::COMPLETE_STATUS => '完成开发',
            self::TERMINATE_STATUS => '开发终止',
            self::MAINTENANCE_STATUS => '维护中',
        ];
    }
    
    //是否删除
    const DELETE = 1;
    const NON_DELETE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%projects}}';
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
                    self::EVENT_BEFORE_INSERT => ['create_user_id']
                ]
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'name' => '项目名称',
            'description' => '项目描述',
            'status' => '状态',
            'version_number' => '项目版本',
            'expected_start_time' => '预计开始时间',
            'expected_end_time' => '预计截至时间',
            'real_start_time' => '实际开始时间',
            'real_end_time' => '实际截至时间',
            'project_doc_attachement' => '项目文档附件',
            'create_user_id' => '创建人',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'vcs_host' => '版本控制服务器'
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields(){
        $fields = parent::fields();
        $this->create_time = date('Y-m-d H:i:s', $this->create_time);
        $this->expected_start_time = date('Y-m-d H:i', $this->expected_start_time);
        $this->expected_end_time = date('Y-m-d H:i', $this->expected_end_time);
        $this->real_start_time = date('Y-m-d H:i', $this->real_start_time);
        $this->real_end_time = date('Y-m-d H:i', $this->real_end_time);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['name','description','version_number', 'expected_start_time', 'expected_end_time'], 'required'],
            [['status', 'expected_start_time','expected_end_time','real_start_time','real_end_time', 'create_user_id'], 'integer'],
            [['name'], 'string','max'=>80],
            [['project_doc_attachement', 'vcs_host'], 'string','max'=>255],
            [['version_number'], 'string','max'=>20],
            [['status'], 'in','range' => [
                self::WAITING_STATUS, 
                self::DEVELOPING_STATUS, 
                self::COMPLETE_STATUS, 
                self::TERMINATE_STATUS, 
                self::MAINTENANCE_STATUS
            ]], 
            [['is_delete'], 'in', 'range' => [
                self::DELETE, self::NON_DELETE
            ]],
        ];
    }

    //关联的任务
    public function getTasks(){
        return $this->hasMany(Task::className(), ['project_id'=>'id'])->orderBy(['priority'=>SORT_DESC]);
    }

}