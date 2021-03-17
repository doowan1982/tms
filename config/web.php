<?php
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'pms',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'zh-CN',
    'defaultRoute' => 'my/index',
    'timeZone' => 'Asia/Shanghai',
    'components' => [
        'request' => [
            'cookieValidationKey' => '2e4LdNh2KGPHrsvlUIfgMcjd1r2m9ZoU',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Member',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
             'class' => 'yii\swiftmailer\Mailer',
             'transport' => [
                 'class' => 'Swift_SmtpTransport',
                 'host' => 'host',
                 'username' => 'username',
                 'password' => 'password', 
                 'port' => '587',
                 'encryption' => 'tls',
             ],
             'messageConfig'=>[
                 'charset'=>'UTF-8',
                 'from'=>[]
             ],
         ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'app\base\DbTarget',
                    'logTable' => '{{%log}}',
                    'levels' => ['error', 'warning', 'info', 'trace'],
                    'categories' => [
                        'errorLevel',
                        'warningLevel',
                        'infoLevel',
                        'traceLevel',
                    ],
                ],
            ],
        ],
        'db' => $db,
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        
        'userService' => 'app\services\Users',
        'projectService' => 'app\services\Projects',
        'taskService' => 'app\services\Tasks',
        'toolService' => 'app\services\Tools',
    ],
    'params' => $params,
];

if(YII_DEBUG){
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] =  [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['*'],
        'allowedHosts' => [

        ],
    ];
}

return $config;