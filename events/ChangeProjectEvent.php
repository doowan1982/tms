<?php
namespace app\events;

/**
 * 任务变更项目事件
 */
class ChangeProjectEvent extends TaskEvent{

    /**
     * 之前所位于的项目
     * @var Project $source
     */
    public $source;
}