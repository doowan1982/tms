<?php
namespace app\records;

class Setting extends \app\base\BaseAR{
    
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%setting}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'name' => '参数名称',
            'value' => '参数值',
            'comment' => '注释',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['name', 'value'], 'required'],
            [['name'], 'unique'],
            [['name'], 'string','max'=>50],
            [['comment'], 'string','max'=>255],
            [['value'], 'string','max'=>2000],
        ];
    }

}