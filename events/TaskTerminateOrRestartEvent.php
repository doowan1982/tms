<?php
namespace app\events;

class TaskTerminateOrRestartEvent extends TaskEvent{

    /**
     * 是否为终止任务，为false时为重启任务
     * @var boolean
     */
    public $isTerminate;

}