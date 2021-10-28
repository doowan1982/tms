<?php
namespace app\base;

use yii\db\BaseActiveRecord;
abstract class Service extends \yii\base\Component{
    
    use CommonTrait;
    
    //错误信息，string|array
    private $errors;

    //保存
    public function save(BaseActiveRecord $record){
        if(!$record->save()){
            return $this->setError($record->getErrorString());
        }
        return true;
    }

    //设置错误信息
    public function setError($message){
        $this->errors[] = $message;
        return false;
    }

    //返回错误信息
    public function getErrors(){
        return $this->errors;
    }

    //返回错误字符串
    public function getErrorString(){
        return implode($this->errors, "\r\n");
    }

    /**
     * 分页
     * @param  QueryInterface  $query
     * @param  integer $pageSize
     * @return DataProvider
     */
    protected function getDataProvider($query, $pageSize = 20){
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSizeLimit' => false,
                'defaultPageSize' => $pageSize
            ]
        ]);
        $dataProvider->getPagination()->totalCount = $dataProvider->totalCount;
        return $dataProvider;
    }

    const EQ = '=';
    const LT = '>';
    const GT = '<';
    const FUZZY = 'like';
    const RANGE = 'between';

    /**
     * 条件过滤，简化查询参数处理
     * @param  QueryInterface $query
     * @param  array &$conditons
     * @param  mixed $name 如果为字符串，则为同名条件，
     *                     如果为数组，存在三个值：参数名，实际查询名，查询逻辑
     * @return void
     */
    protected function conditionFilter($query, &$conditons, $name){
        //字符串作为等于条件
        if(is_string($name)){
            $name = [$name, $name, self::EQ];
        }
        //缺省第三参数默认为等于条件
        if(count($name) == 2){
            $name[] = self::EQ;
        }
        list($name, $field, $logic) = $name;
        if(!isset($conditons[$name])){
            return;
        }
        $value = $conditons[$name];
        if($value === ''){
            return;
        }
        if($logic == self::EQ){
            $where = [$field => $value];
        }else if($logic == self::RANGE){
            $where = [$logic, $field, $value[0], $value[1]]; //$value需要为数组
        }else{
            $where = [$logic, $field, $value];
        }
        $query->andFilterWhere($where);
        unset($conditons[$name]);
    }

}