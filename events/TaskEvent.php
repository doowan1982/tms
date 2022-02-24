<?php
namespace app\events;

class TaskEvent extends CommonEvent{

    /**
     * 所在项目模型
     * @var app\records\Project
     */
    public $project;

    /**
     * 任务模型
     * @var app\records\Task
     */
    public $task;

}