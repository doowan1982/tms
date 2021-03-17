<?php
namespace app\helpers;

use yii\widgets\LinkPager;
class Helper{

    /**
     * 返回开始到截止的时间戳，如果为给定则返回默认值
     * @param  string|integer $start
     * @param  string|integer $end
     * @param  integer $defStart
     * @param  integer $defEnd
     * @return array
     */
    public static function timestampRange($start, $end, $defStart = null, $defEnd = null){
        if(is_null($defStart)){
            $defStart = 0;
        }
        if(is_null($defEnd)){
            $defEnd = time();
        }
        if(!$start){
            $start = $defStart;
        }
        if(!$end){
            $end = $defEnd;
        }
        if(is_string($start)){
            $start = strtotime($start);
        }
        if(is_string($end)){
            $end = strtotime($end);
        }
        return [$start, $end];
    }

    /**
     * 将指定的时间转换为文字形式
     * @param  integer $interval
     * @return string
     */
    public static function convertTime($interval){
        //秒
        if($interval <= 60){
            return $interval . '秒';
        }
        //分钟
        $interval = ceil(round($interval / 60, 2));
        if($interval <= 60){
            return $interval.'分';
        }
        //小时
        $interval = ceil($interval / 60);
        if($interval <= 60){
            return $interval. '小时';
        }
        //天，最大显示7天
        $interval = ceil($interval / 24);
        if($interval > 7){
            return '很久';
        }
        return $interval.'天';

    }

    /**
     * 分页html
     * @param  DataProviderInterface $dataProvider
     * @return string
     */
    public static function getPaginationHtml(\yii\data\DataProviderInterface $dataProvider){
        $pagination = $dataProvider->getPagination();
        $html = "<ul class='pagination blue'><li>共{$pagination->totalCount}条数据</li></ul>"; 
        if($dataProvider->getTotalCount() < $pagination->getPageSize()){
            return $html;
        }
        return $html ."<ul class='pagination font-gray'><li>&nbsp;|&nbsp;</li></ul>". LinkPager::widget([
            'pagination' => $pagination,
            'disableCurrentPageButton' => true,
            'firstPageLabel' => '首页',
            'lastPageLabel' => '尾页',
            'nextPageLabel' => '下一页',
            'prevPageLabel' => '上一页',
        ]);
    }

}