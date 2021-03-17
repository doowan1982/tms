<?php
namespace app\models;
use app\helpers\Helper;
class TaskProgress extends \app\base\BaseModel{

    /**
     * 任务
     * @var app\records\Task
     */
    public $task;

    public $now;

    public $startTime;

    const DAY = 60 * 60 * 24;

    public function init(){
        parent::init();
        if(!$this->task){
            $this->exception('未设置任务记录');
        }
        if(count($this->task->taskDetails) == 0){
            $this->exception('任务未开始实施');
        }
        $this->startTime = $this->task->taskDetails[0]->start_time;
    }

    /**
     * 返回总时间
     * @return number
     */
    public function getTotalTime(){
        $full = $this->getExpectedFinishTime() - $this->task->taskDetails[0]->start_time;
        return round($full / self::DAY, 1);

    }

    /**
     * 返回已使用时间
     * @author doowan
     * @date   2020-05-23
     * @return string
     */
    public function getUsedTime(){
        $usedTime = $this->now - $this->startTime;
        return round($usedTime / self::DAY, 1);
    }

    /**
     * 返回剩余时间
     * @author doowan
     * @date   2020-05-23
     * @return string
     */
    public function getRemainTime(){
        $remainTime = $this->getExpectedFinishTime() - $this->now;
        if($remainTime < 0){
            return '已超时';
        }
        return round(abs($remainTime) / self::DAY, 1);
    }

    private function getExpectedFinishTime(){
        //从第一次实施任务的时间开始计算完成时间
        return $this->task->expected_finish_time;
    }

}