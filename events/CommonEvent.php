<?php
namespace app\events;

use yii\base\Event;
use app\base\CommonTrait;
class CommonEvent extends Event{

    use CommonTrait;

    /**
     * 是否成功
     * @var boolean
     */
    public $isValid = true;

}