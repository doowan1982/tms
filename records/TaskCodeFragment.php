<?php
namespace app\records;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
class TaskCodeFragment extends \app\base\BaseAR{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%task_code_fragment}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'task_id' => '所在任务',
            'code_fragment' => '代码片段',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['task_id'], 'required'],
            [['code_fragment'], 'string'],
        ];
    }

}