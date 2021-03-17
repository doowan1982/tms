<?php
namespace app\records;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
class Log extends \app\base\BaseAR{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%log}}';
    }

    /**
     * @inheritdoc
     */
    public function fields(){
        $fields = parent::fields();
        $this->create_time = date('Y-m-d H:i', $this->create_time);
        return $fields;
    }

}