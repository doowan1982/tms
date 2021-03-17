<?php
namespace app\models;
use app\base\CommonTrait;
abstract class MessageSender extends \app\base\BaseModel{

    use CommonTrait;
    /**
     * 消息内容
     * @var string
     */
    public $message;

    /**
     * 接收人
     * @var app\record\Member
     */
    public $member;

    /**
     * 涉及的任务
     * @var app\record\Task
     */
    public $task;

    /**
     * 本次发送消息设计的页面url
     * @var string
     */
    public $url;

    /**
     * 发送消息
     * @return boolean
     */
    public abstract function send();

}