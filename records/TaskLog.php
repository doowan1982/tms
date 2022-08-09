<?php
namespace app\records;

use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use app\base\BaseAR;
class TaskLog extends Task{

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['log_time', 'update_time'],
                ],
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%tasks_log}}';
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert){
        return BaseAR::beforeSave($insert);
    }

    public function afterSave($insert, $attributes){
        parent::afterSave($insert, $attributes);
        \Yii::$app->get('taskService')->saveTaskDescription($this, new TaskLogDescription());
    }

    public function getTaskDescription(){
        return $this->hasOne(TaskLogDescription::class, ['task_log_id' => 'log_id']);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(){
        return BaseAR::beforeDelete($insert);
    }

    /**
     * @inheritdoc
     */
    public function fields(){
        $fields = parent::fields();
        $this->log_time = self::dataConvertString($this->log_time);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        $attributeLabels = parent::attributeLabels();
        $attributeLabels['log_time'] = '记录时间';
        return $attributeLabels;
    }

}