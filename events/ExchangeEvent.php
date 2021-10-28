<?php
namespace app\events;

class ExchangeEvent extends CommonEvent{
    
    /**
     * 交换的任务
     * @var Task $task
     */
    public $task;

    /**
     * 原始主任务，可能为null
     * @var Task $from
     */
    public $from;

    /**
     * 所在新的主任务
     * @var Task $to
     */
    public $to;

}