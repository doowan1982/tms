<?php
namespace app\models;

class Constants{
    /**
     * 页面路由主链接地址
     */
    const PROJECT_HOME = '/project';
    const TASKS_ON_PROJECT = '/project/tasks';
    const EDIT_TASK = '/project/edit-task';
    const PUBLISHED_TASKS = '/my/published-tasks';
    const MY_TASKS = '/my/tasks';
    const TASK_CHANGE_LOG = '/task-change-log/index';
    const MEMBER_INDEX = '/member/index';
    const MEMBER_EDIT = '/member/edit';
    const RESET_PASSWORD = '/site/reset-password';
    const LOGIN = '/site/login';

    /**
     * 消息发送类型
     */
    const DB_MESSAGE = 'db'; //记录数据库
    const EMAIL_MESSAGE = 'email'; //发送至邮箱
    const SHORT_MESSAGE = 'message'; //发送至短信
    

    /**
     * 自定义日志级别
     */
    const ERROR_LOG = 'errorLevel';
    const WARNING_LOG = 'warningLevel';
    const INFO_LOG = 'infoLevel';
    const TRACE_LOG = 'traceLevel';


    /**
     * 配置参数
     */
    const SEND_TO_EMAIL = 'whichTasksOfPrioritySendToEmail';
    const FOCUS_PROJECT = 'focusProject';
}