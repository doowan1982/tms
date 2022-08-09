<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'uploadDir' => '/uploads',
    'filePath' => '/files',
    'picPath' => '/images',
    'mediaPath' => '/media',
    'cssFiles' => [
        '<link rel="stylesheet" href="/css/styles.css">',
        '<link href="/js/jquery-ui-1.12.1/jquery-ui.css" rel="stylesheet">',
        '<link href="/js/jquery-ui-timepicker-addon.min.css" rel="stylesheet">',
        '<link href="/css/jquery.multiselect.filter.css" rel="stylesheet">',
        '<link href="/css/jquery.multiselect.css" rel="stylesheet">',
    ],
    'jsFiles' => [
        ['/js/jquery-3.4.1.min.js', ['position' => \yii\web\View::POS_HEAD]],
        ['/js/jquery-ui-1.12.1/jquery-ui.min.js', ['position'=>\yii\web\View::POS_HEAD]],
        ['/js/common.js', ['position'=>\yii\web\View::POS_HEAD]],
        ['/js/jquery-ui-timepicker-addon.min.js', ['position'=>\yii\web\View::POS_HEAD]],
        ['/js/art-template-4.13.2.min.js', ['position'=>\yii\web\View::POS_HEAD]],
        ['/js/jquery.cookie.js', ['position'=>\yii\web\View::POS_HEAD]],
        ['/js/jquery-ui-multiselect.min.js', ['position'=>\yii\web\View::POS_HEAD]],
        
    ],
    
    'admin' => ['admin'],

    'accessControl' => [
        'class' => yii\filters\AccessControl::className(),
        'only' => ['*'],
        'denyCallback' => function($rule, $action){
            if(Yii::$app->request->getIsAjax()){
                Yii::$app->response->statusCode = 401;
                Yii::$app->response->content = '无权限';
                Yii::$app->end();
            }
            throw new \yii\web\UnauthorizedHttpException('无权限');
        },
        'rules' => [
            [
                'actions' => [],
                'roles' => ['@'],
                'allow' => true,
                'matchCallback' => function($rule, $action){
                    return $action->controller->canAccess($action);
                }
            ],
        ],
    ],
];
