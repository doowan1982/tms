<?php
namespace app\events;
/**
 * 任务处理后置事件
 */
class TaskProcessingEvent extends TaskEvent{
        
    /**
     * TaskDetail
     * @var app\records\TaskDetail
     */
    public $taskDetail;

}