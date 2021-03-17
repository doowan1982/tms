<?php
namespace app\events;

/**
 * 查询事件
 */
class SearchEvent extends CommonEvent{
    
    /**
     * @var QueryInterface
     */
    public $query;

    /**
     * 查询参数
     * @var array
     */
    public $params = [];

    /**
     * 排序
     * @var array
     */
    public $orders = [];

    /**
     * 分组
     * @var array
     */
    public $groups = [];

}