<?php
namespace app\base;

use yii\behaviors\TimestampBehavior;
abstract class BaseAR extends \yii\db\ActiveRecord{
    use CommonTrait;

    public static function sql($query){
        echo $query->createCommand()->getRawSql();
    }

    /**
     * 返回表名，如果$alias不为空，则使用该别名
     * @param  string $alias
     * @return string
     */
    public static function getTableName($alias=''){
        $tableName = static::tableName();
        if(empty($alias))
            return $tableName;
        return $tableName.' '.$alias;
    }
}