<?php
namespace app\records;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
class TaskCategory extends \app\base\BaseAR{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%task_category}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'name' => '分类名称',
            'show_order' => '显示顺序',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['name'], 'required'],
            [['name'], 'string','max'=>100],
            [['show_order'], 'integer'], 
        ];
    }

}